package Command::Clan;

use v5.10;
use strict;
use warnings;

use Exporter qw(import);
our @EXPORT_OK = qw(cmd_clan);

use Mojo::Discord;
use Bot::TaeJa;
use Component::Db;

use Encode;
use MIME::Base64;
use experimental 'smartmatch';
use JSON;

use utf8;
binmode(STDOUT, ':utf8');
use open ':std', ':encoding(UTF-8)';
use Data::Dumper;

###########################################################################################
# Command Info
my $command = "Clan";
my $access = 0; # Public
my $description = "Search up members of a clan by clan tag";
my $pattern = '^(clan) ?(.*)$';
my $function = \&cmd_clan;
my $usage = <<EOF;
```~clan ENCE```
    Shows list of players in the specified clan tag

```~clan ENCE [terran, zerg, protoss, random]```
    Shows list of players in the specified clan tag ranked in a specified race

```~clan ENCE [grandmaster, master, diamond, platinum...]```
    Shows list of players in the specified clan tag ranked in a specified league

```~clan ENCE [na, kr, eu]```
    Shows list of players in the specified clan tag on a specified server

```~clan ENCE count [league, race]```
    Shows the count of players in specified clantag by league or race

```~clan ENCE export```
    Creates a downloadable excel file of all players in the specified clan tag
EOF
###########################################################################################

sub new
{
    my ($class, %params) = @_;
    my $self = {};
    bless $self, $class;
     
    # Setting up this command module requires the Discord connection 
    $self->{'bot'} = $params{'bot'};
    $self->{'discord'} = $self->{'bot'}->discord;
    $self->{'pattern'} = $pattern;

    # Register our command with the bot
    $self->{'bot'}->add_command(
        'command'       => $command,
        'access'        => $access,
        'description'   => $description,
        'usage'         => $usage,
        'pattern'       => $pattern,
        'function'      => $function,
        'object'        => $self,
    );
    
    return $self;
}

sub cmd_clan
{
    my ($self, $channel, $author, $msg) = @_;

    my $args = $msg;
    my $pattern = $self->{'pattern'};
    $args =~ s/$pattern/$2/i;

    my $discord = $self->{'discord'};
    my $replyto = '<@' . $author->{'id'} . '>';

    my @parameters = (lc($args) =~ m/([\S]+)/g);

    my $map = map_parameters(\@parameters);

    if (ref($map) ne 'HASH') {
        $discord->send_message($channel, $map);
        return;
    }

    my $result = search_map($map, $self);

    if ($result eq "\n") {
        $result = "No results";
    }

    eval {
        my $json = decode_json($result);
        $discord->send_message($channel, $json);
    };
    if ($@) {
        $discord->send_message($channel, $result);
    }
}

sub search_map
{
    my ($map, $self) = @_;

    my $conn = create_connection($self);

    my $select_base = 'SELECT * FROM `everyone` WHERE `clan_tag`';
    my $count_base = 'SELECT COUNT(*) FROM `everyone` WHERE `clan_tag`';

    if (!defined $map->{'offset'}) {
        $map->{'offset'} = 0;
    }

    if (defined $map->{'export'}) {
        return "Functionality not yet implemented";
    }

    if (defined $map->{'count_type'}) {
        return "Functionality not yet implemented";
    }

    my $where = "";
    foreach my $param (keys %{$map}) {
        if (defined ($map->{$param})) {
            if ($param ne 'name' && $param ne 'offset' && $param ne 'export' && $param ne 'count_type' && $param ne 'distinct') {
                $where .= " AND `" . $param . "` = \"" . $map->{$param} . "\" ";
            }
        }
    }

    my $select_query = $select_base . " LIKE \"\%" . $map->{'name'} . "\%\" " . $where . "ORDER BY `mmr` DESC";
    my $count_query = $count_base . " LIKE \"\%" . $map->{'name'} . "\%\" " . $where;
    
    my $amount;
    
    if (defined $map->{'distinct'}) {
        my $nolimit_select_query = "SELECT `real_name`, MAX(`mmr`) FROM `everyone` WHERE `clan_tag` LIKE \"\%" . $map->{'name'} . "\%\" " . $where . "GROUP BY `real_name` ORDER BY MAX(`mmr`) DESC";
        $amount = $conn->do_select($nolimit_select_query);
        $amount = scalar @{$amount};
    }
    else {
        $amount = $conn->do_select($count_query);
        $amount = @{@{$amount}[0]}[0];
    }

    my $data = $conn->do_select($select_query);

    my $response = "\n";

    return $response if $map->{'offset'} > $amount;

    my @distinct;

    my $count = 0;
    foreach my $user_data (@{$data}) {
        my @user_data = @{$user_data};
        my $username = $user_data[16];
        chop($username);
        chop($username);

        if (defined $map->{'distinct'}) {
            if ($username ~~ @distinct) {
                # Don't push current user
            }
            else {
                $count++;
            }
        }
        else {
            $count++;
        }

        my $pagination = $map->{'offset'};
        if ($count-1 < $pagination) {
            next;
        }
        if ($count > ($pagination+15)) {
            next;
        }

        my $clan_tag = '';
        if ($user_data[24] ne '') {
            $clan_tag = '[' . $user_data[24] . ']';
        }


        if (defined $map->{'distinct'}) {
            if ($username ~~ @distinct) {

            }
            else {
                $response .= sprintf("%s%s%s %s %s (mmr: %s)\n", 
                    get_race_emoji($user_data[18]),
                    get_league_emoji($user_data[21]),
                    $clan_tag,
                    $username,
                    get_server_emoji($user_data[28]),
                    $user_data[0]
                );
                push @distinct, $username;
            }
        }
        else {
            $response .= sprintf("%s%s%s %s %s (mmr: %s)\n", 
                get_race_emoji($user_data[18]),
                get_league_emoji($user_data[21]),
                $clan_tag,
                $username,
                get_server_emoji($user_data[28]),
                $user_data[0]
            );
        }
    }
    $amount = $count;
    if ($amount > 15) {
        my $max_pages = $amount / 15;
        if (int $max_pages ne $max_pages) {
            $max_pages = int $max_pages + 1;
        }
        my $current_page = 1;
        if ($map->{'offset'} > 0) {
            $current_page = ($map->{'offset'} / 15) + 1;
        } 
        my $hidden = $amount - 15;
        $response .= 
            "Page: [$current_page/$max_pages]\n" .
            "**$amount** total results, **$hidden** hidden (**15** shown).\n" .
            "To display a different page of results, use an numeric parameter.\n" .
            "i.e: ~clan ENCE 3\n" .
            "The above command will go to the third page of results.\n"
        ;
    }

    return $response;
}

sub map_parameters
{
    my ($parameters) = @_;
    my @parameters = @{$parameters};

    if (scalar @parameters eq 0) {
        return sprintf("Missing clan tag, please try\n `~clan <clan tag>`\ni.e: `~clan ENCE`");
    }
    # elsif (scalar @parameters > 5) {
    #     return sprintf("Too many options, found %d. At most 5", scalar @parameters);
    # }

    my @leagues = (
        'grandmaster',
        'master',
        'diamond',
        'platinum',
        'gold',
        'silver',
        'bronze',
    );

    my @races = (
        'terran',
        'zerg',
        'protoss',
        'random',
    );

    my @servers = (
        'us',
        'kr',
        'eu',
    );

    my %mapped = (
        'name' => undef,
        'count_type' => undef,
        'race' => undef,
        'league' => undef,
        'server' => undef,
        'offset' => undef,
        'export' => undef,
        'distinct' => undef,
    );

    $mapped{'name'} = shift @parameters;

    if (@parameters) {
        # TODO: this forces the user to use `count` right after clan tag. Are you sure?
        my $is_count = shift @parameters;
        if ($is_count eq "count") {
            $mapped{'count_type'} = shift @parameters;
        }
        else {
            unshift @parameters, $is_count;
        }

        foreach my $parameter (@parameters) {
            if ($parameter ~~ @leagues) {
                $mapped{'league'} = $parameter;
            }
            elsif ($parameter ~~ @races) {
                $mapped{'race'} = $parameter;
            }
            elsif ($parameter ~~ @servers) {
                $mapped{'server'} = $parameter;
            }
            elsif ($parameter eq 'distinct') {
                $mapped{'distinct'} = '=D';
            }
            elsif ($parameter =~ m/^\d+$/ && $parameter > 0) {
                $mapped{'offset'} = ($parameter - 1) * 15;
            }
            else {
                return sprintf("Unknown option '%s'", $parameter);
            }
        }
    }

    return \%mapped;
}

sub create_connection 
{
    my $self = shift;    

    my $dbi = Component::Db->new(
        %{$self->{'discord'}->{'gw'}->{'bot'}->{'users_database'}}
    );

    return $dbi->connect();
}

sub get_server_emoji {
    my $server = shift;
    my %map = (
        'us' => '<:NA:297912161064452096>',
        'kr' => '<:KR:297912218438074368>',
        'eu' => '<:EU:297912193729560577>',
    );
    return $map{lc($server)};
}

sub get_race_emoji {
    my $race = shift;
    my %map = (
        'terran'    => '<:TERRAN:278762425552207883>',
        'zerg'      => '<:ZERG:278762452265467907>',
        'protoss'   => '<:PROTOSS:278762398347689984>',
        'random'    => '<:RANDOM:278762354001444864>',
    );
    return $map{lc($race)};
}

sub get_league_emoji {
    my $league = shift;
    my %map = (
        'bronze'        => '<:BRONZE3:278725418641522688>',
        'silver'        => '<:SILVER2:278725418813751297>',
        'gold'          => '<:GOLD1:278725419073536012>',
        'platinum'      => '<:PLATINUM1:278725419056758784>',
        'diamond'       => '<:DIAMOND1:278725418960551937>',
        'master'        => '<:MASTER1:278725418679271425>',
        'grandmaster'   => '<:GRANDMASTER:278725419186782208>',
    );
    return $map{lc($league)};
}

1;