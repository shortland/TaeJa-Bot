package Command::Export;

use v5.10;
use strict;
use warnings;

use Exporter qw(import);
our @EXPORT_OK = qw(cmd_export);

use Mojo::Discord;
use Bot::TaeJa;
use Component::Db;

use Encode;
use MIME::Base64;
use experimental 'smartmatch';
use JSON;
use Path::Tiny;

use utf8;
binmode(STDOUT, ':utf8');
use open ':std', ':encoding(UTF-8)';
use Data::Dumper;

###########################################################################################
# Command Info
my $command = "Export";
my $access = 0; # Public
my $description = "Export clan data to an excel file";
my $pattern = '^(export) ?(.*)$';
my $function = \&cmd_export;
my $usage = <<EOF;
```~export ENCE```
    Exports the clan data belonging to the specified clantag. User ranking data is their highest MMR race.
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

sub cmd_export
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

    # TODO: THREADS FOR THIS... Or a separate script (hard unless you parse config file again there...)
    my $response = search_map($map, $self);

    $discord->send_message($channel, $response)
}



sub search_map
{
    my ($map, $self) = @_;

    my $conn = create_connection($self);

    # TODO: Include the Discord tags too...

    my $select_query = 
        "SELECT a.`real_name`, a.`mmr`, a.`league`, a.`tier`, a.`race`, a.`game_count`, DATE_FORMAT(FROM_UNIXTIME(a.`last_played_time_stamp`), '%M %e %Y') AS `last_played_time_stamp`, a.`real_battle_tag`, a.`path`, a.`server`
        FROM `everyone` a
        INNER JOIN (
            SELECT `real_name`, MAX(`mmr`) `mmr`
            FROM `everyone`
            WHERE `clan_tag` = '" . $map->{'name'} . "'
            GROUP BY `real_name`
        ) b ON a.`real_name` = b.`real_name` AND a.`mmr` = b.`mmr`
        ORDER BY a.`server` DESC, a.`mmr` DESC";

    my $data = $conn->do_select($select_query);

    my $export_name = sprintf("Exports/%sDump.csv", $map->{'name'});
    path($export_name)->touch;
    path($export_name)->spew(" Name , MMR , League , Tier , Race , Games Played , Last Ranked 1v1 , Battle Net , Path , Server , Discord \n");

    foreach my $user_data (@{$data}) {
        my @user_data = @{$user_data};
        my $csv_line = join ',', @user_data;
        $csv_line .= "\n";
        
        path($export_name)->append({binmode => ":encoding(UTF-8)"}, $csv_line);
    }
    
    # TODO: this is hard coded...
    my $dump_link = "http://138.197.50.244/TaeJa/" . $export_name;

    return $map->{'name'} . " clan data available for download:\n" . $dump_link;
}

sub map_parameters
{
    my ($parameters) = @_;
    my @parameters = @{$parameters};

    if (scalar @parameters eq 0) {
        return sprintf("Missing clan tag, please try\n `~export <clan tag>`\ni.e: `~clan ENCE`");
    }
    elsif (scalar @parameters gt 1) {
        return sprintf("Please only provide a clantag. This feature currently doesn't support additional filtering.");
    }

    my %mapped = (
        'name' => undef,
    );

    $mapped{'name'} = shift @parameters;

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

1;