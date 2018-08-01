package Command::Player;

use v5.10;
use strict;
use warnings;

use Exporter qw(import);
our @EXPORT_OK = qw(cmd_player);

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
my $command = "Player";
my $access = 0; # Public
my $description = "Search up a Starcraft player by their in-game name";
my $pattern = '^(player|bnet) ?(.*)$';
my $function = \&cmd_player;
my $usage = <<EOF;
```~player shortland```
    Shows player(s) with the specified name

```~player shortland#1803```
    Shows a specific player with the specific SC2 tag (not same as battle.net tag!)

```~player shortland terran```
    Shows player(s) with specified name and race [terran, zerg, protoss]

```~player shortland diamond```
    Shows player(s) with specified name and league [grandmaster, master, diamond...]

```~player shortland us```
    Shows player(s) with specified name in the server [us, kr, eu]

```~player shortland 2```
    Shows player(s) with specified name, if there are multiple players with this name, will jump to 2nd page of results.

```~player shortland#1803 diamond terran us```
    Shows player(s) with specified name, and other specified parameters (order doesn't matter)
EOF
# (A maximum of 15 results will be displayed at a time, specify a numeric parameter to view the next amount)
# i.e:
# ```~player shortland``` will display only 15 results, to view the next 15 use ```~player shortland 2```, then ```~player shortland 2, 3,4...``` etc. The number is the page results to view.

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

sub cmd_player
{
    my ($self, $channel, $author, $msg) = @_;

    my $args = $msg;
    my $pattern = $self->{'pattern'};
    $args =~ s/$pattern/$2/i;

    my $main_search = 'name';
    if ($1 eq 'bnet') {
        $main_search = 'battle_tag'
    }

    my $discord = $self->{'discord'};
    my $replyto = '<@' . $author->{'id'} . '>';

    my @parameters = (lc($args) =~ m/([\S]+)/g);
    
    my $map = map_parameters(\@parameters);

    if (ref($map) ne 'HASH') {
        $discord->send_message($channel, $map);
        return;
    }

    my $result = search_map($map, $main_search, $self);

    if ($result eq "\n") {
        $result = "No results";
    }

    eval 
    {
        my $json = decode_json($result);
        $discord->send_message($channel, $json);
    };
    if ($@)
    {
        #say $@;
        $discord->send_message($channel, $result);
    }
}

sub search_map 
{
    my ($map, $main_search, $self) = @_;

    my $conn = create_connection($self);

    my $select_base;
    my $count_base;
    if ($main_search eq 'battle_tag') {
        #SELECT *  FROM `everyone` WHERE `battle_tag` REGEXP '^[^\\_]*maru' ORDER BY `mmr`  DESC
        $select_base = 'SELECT * FROM `everyone` WHERE `battle_tag` REGEXP';
        $count_base = 'SELECT COUNT(*) FROM `everyone` WHERE `battle_tag` REGEXP';
    }
    else {
        $select_base = 'SELECT * FROM `everyone` WHERE `name`';
        $count_base = 'SELECT COUNT(*) FROM `everyone` WHERE `name`';
    }

    if (!defined $map->{'offset'}) {
        $map->{'offset'} = 0;
    }

    my $where = "";
    foreach my $param (keys %{$map}) {
        if (defined ($map->{$param})) {
            if ($param ne 'name' && $param ne 'offset') {
                $where .= " AND `" . $param . "` = \"" . $map->{$param} . "\" ";
            }
        }
    }

    my $select_query;
    my $count_query;
    if ($main_search eq 'battle_tag') {
        $select_query = $select_base . " '^[^\\_]*" . $map->{'name'} . "' " . $where;

        $count_query = $count_base . " '^[^\\_]*" . $map->{'name'} . "' " . $where;
    }
    else {
        $select_query = $select_base . " LIKE \"\%" . $map->{'name'} . "\%\" " . $where;

        $count_query = $count_base . " LIKE \"\%" . $map->{'name'} . "\%\" " . $where;
    }

    $select_query .= "ORDER BY `mmr` DESC LIMIT " . $map->{'offset'} . ", 15";

    my $amount = $conn->do_select($count_query, 'COUNT(*)');
    $amount = (keys %{$amount})[0];

    my $data = $conn->do_select($select_query, 'battle_tag');

    my $response = "\n";

    return $response if $map->{'offset'} > $amount;

    foreach my $battle_tag (keys %{$data}) {
        my $player = $data->{$battle_tag};
        my $clan_tag = "";
        if ($player->{'clan_tag'} ne '') {
            $clan_tag = '[' . $player->{'clan_tag'} . ']';
        }
        $response .= sprintf("%s%s%s %s %s (mmr: %s)\n", 
            get_race_emoji($player->{'race'}),
            get_league_emoji($player->{'league'}),
            $clan_tag,
            $player->{'name'},
            get_server_emoji($player->{'server'}),
            $player->{'mmr'}
        );
    }

    if ($amount eq 1) {
        foreach my $battle_tag (keys %{$data}) {
            my $player = $data->{$battle_tag};
            
            my $clan_tag = "";
            if ($player->{'clan_tag'} ne '') {
                $clan_tag = '[' . $player->{'clan_tag'} . ']';
            }

            my $battle_tag = $player->{'battle_tag'};
            ($battle_tag) = ($battle_tag =~ m/^([\w\d\W]+)\\_[\w+]/);
            ($battle_tag) = ($battle_tag =~ m/(^[\w\d\W]+#\d+)/);
            $battle_tag =~ s/'//g;
            say $battle_tag;
            
            my $base_api_url = $self->{'discord'}->{'gw'}->{'bot'}->{'bot_path'}->{'url'};
            
            $response =  '{
                "content": "", 
                "embed": {
                    "footer": {
                        "text": "User last updated: ' . (scalar localtime $player->{'last_update'}) . '"
                    }, 
                    "author": {
                        "icon_url": "' . $base_api_url . '/Api/?endpoint=raceimages&race=' . ucfirst($player->{'race'}) . '", 
                        "name": "' . $clan_tag . ' ' . $player->{'name'} . '", 
                        "url": "http://' . $player->{'server'} . '.battle.net/sc2/en' . $player->{'path'} . '/"
                    }, 
                    "thumbnail": {
                        "url": "' . $base_api_url . '/Api/?endpoint=leagueimages&tier=1&league=' . ucfirst($player->{'league'}) . '", 
                        "height": 60, 
                        "width": 60
                    }, 
                    "type": "rich", 
                    "color": 4343284, 
                    "fields": [
                        {
                            "name": "Battle Net:", 
                            "value": "*http://' . $player->{'server'} . '.battle.net/sc2/en' . $player->{'path'} . '/*", 
                            "inline": 0
                        },
                        {
                            "name": "Ranked FTW:", 
                            "value": "*http://www.rankedftw.com/search/?name=http://' . $player->{'server'} . '.battle.net/sc2/en' . $player->{'path'} . '/*", 
                            "inline": 0
                        }, 
                        {
                            "name": "Tier:", 
                            "value": "*' . $player->{'tier'} . '*", 
                            "inline": 1
                        }, 
                        {
                            "name": "MMR:", 
                            "value": "*' . $player->{'mmr'} . '*", 
                            "inline": 1
                        }, 
                        {
                            "name": "Wins:", 
                            "value": "*' . $player->{'wins'} . '*", 
                            "inline": 1
                        }, 
                        {
                            "name": "Losses:", 
                            "value": "*' . $player->{'losses'} . '*", 
                            "inline": 1
                        }, 
                        {
                            "name": "Longest Win Streak:", 
                            "value": "*' . $player->{'longest_win_streak'} . '*", 
                            "inline": 1
                        }, 
                        {
                            "name": "Current Win Streak:", 
                            "value": "*' . $player->{'current_win_streak'} . '*", 
                            "inline": 1
                        }, 
                        {
                            "name": "Last 1v1:", 
                            "value": "*' . (scalar localtime $player->{'last_played_time_stamp'}) . '*", 
                            "inline": 1
                        }, 
                        {
                            "name": "BattleNet Tag:", 
                            "value": "*' . $battle_tag . '*", 
                            "inline": 1
                        }
                    ]
                }
            }';
        }
    }

    if ($amount > 15) {
        my $max_pages = $amount / 15;
        if (int $max_pages ne $max_pages) {
            $max_pages = int $max_pages + 1;
        }
        my $current_page = 1;
        if ($map->{'offset'} > 0) {
            say $map->{'offset'};
            $current_page = ($map->{'offset'} / 15) + 1;
        } 
        my $hidden = $amount - 15;
        $response .= 
            "Page: [$current_page/$max_pages]\n" .
            "**$amount** total results, **$hidden** hidden (**15** shown).\n" .
            "To display a different page of results, use an numeric parameter.\n" .
            "i.e: ~player kira 3\n" .
            "The above command will go to the third page of results.\n"
        ;
    }
        
    return $response;
}

sub create_connection 
{
    my $self = shift;    

    my $dbi = Component::Db->new(
        %{$self->{'discord'}->{'gw'}->{'bot'}->{'users_database'}}
    );

    return $dbi->connect();
}

sub map_parameters 
{
    my ($parameters) = @_;
    my @parameters = @{$parameters};

    if (scalar @parameters eq 0) {
        return sprintf("Missing username, please try\n `~player <username>`\ni.e: `~player shortland`");
    }
    elsif (scalar @parameters > 5) {
        return sprintf("Too many options, found %d. At most 5", scalar @parameters);
    }

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
        'league' => undef,
        'race' => undef,
        'server' => undef,
        'offset' => undef,
    );

    $mapped{'name'} = shift @parameters;

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
        elsif ($parameter =~ m/^\d+$/ && $parameter > 0) {
            $mapped{'offset'} = ($parameter - 1) * 15;
        }
        else {
            return sprintf("Unknown option '%s'", $parameter);
        }
    }

    return \%mapped;
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
