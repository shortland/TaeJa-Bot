package Command::Bounds;

use v5.10;
use strict;
use warnings;

use Exporter qw(import);
our @EXPORT_OK = qw(cmd_bounds);

use Mojo::Discord;
use Bot::TaeJa;
use Component::RequestAPI;

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
my $command = "Bounds";
my $access = 0; # Public
my $description = "Shows the MMR boundaries for the specified region.";
my $pattern = '^(bounds) ?(.*)$';
my $function = \&cmd_bounds;
my $usage = <<EOF;
```~bounds [na|kr|eu]```
    Shows the MMR boundaries for the specified region.
EOF
###########################################################################################

sub new
{
    my ($class, %params) = @_;
    my $self = {};
    bless $self, $class;
    $self->{'bot'} = $params{'bot'};
    $self->{'discord'} = $self->{'bot'}->discord;
    $self->{'pattern'} = $pattern;
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

sub cmd_bounds
{
    my ($self, $channel, $author, $msg) = @_;

    my $args = $msg;
    my $pattern = $self->{'pattern'};
    $args =~ s/$pattern/$2/i;

    my $discord = $self->{'discord'};
    my $replyto = '<@' . $author->{'id'} . '>';

    my @parameters = ($args =~ m/([\S]+)/g);
    
    my $map = map_parameters(\@parameters);

    if (ref($map) ne 'HASH') {
        $discord->send_message($channel, $map);
        return;
    }

    my $url_string = url_parameterized($map);

    my $result = search_map($url_string, $self);

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
        $discord->send_message($channel, $result);
    }
}

sub search_map 
{
    my ($parameters, $self) = @_;

    my $api = create_api($self);

    my $data = $api->get_api("bounds", $parameters);

    $data = replace_league_emoji($data);

    return $data;
}

sub map_parameters 
{
    my ($parameters) = @_;
    my @parameters = @{$parameters};

    if (scalar @parameters eq 0) {
        return sprintf("Missing server, please try\n `~bounds <us|kr|eu>`\ni.e: `~bounds us`");
    }

    my %mapped = (
        'server' => undef,
    );

    $mapped{'server'} = shift @parameters;

    return \%mapped;
}

sub url_parameterized
{
    my ($map) = @_;

    my $where = "";
    foreach my $param (keys %{$map}) {
        if (defined ($map->{$param})) {
            if ($param ne 'name' && $param ne 'offset') {
                $where .= "&" . $param . "=" . $map->{$param};
            }
        }
    }

    return $where;
}

sub create_api
{
    my $self = shift;    

    my $api = Component::RequestAPI->new(
        %{$self->{'discord'}->{'gw'}->{'bot'}->{'bot_path'}}
    );

    return $api->setup();
}

sub replace_league_emoji {
    my $league = shift;

    $league =~ s/bronze/<:BRONZE3:278725418641522688>/g;
    $league =~ s/silver/<:SILVER2:278725418813751297>/g;
    $league =~ s/gold/<:GOLD1:278725419073536012>/g;
    $league =~ s/platinum/<:PLATINUM1:278725419056758784>/g;
    $league =~ s/diamond/<:DIAMOND1:278725418960551937>/g;
    $league =~ s/master/<:MASTER1:278725418679271425>/g;
    $league =~ s/grandmaster/<:GRANDMASTER:278725419186782208>/g;

    return $league;
}

1;
