package Component::RequestAPI;

use v5.10;
use strict;
use warnings;

use Mojo::Discord;
use Mojo::UserAgent;
use Bot::TaeJa;
use Encode;
use MIME::Base64;
use DBI;

use utf8;
binmode(STDOUT, ':utf8');
use open ':std', ':encoding(UTF-8)';
use Data::Dumper;

sub new
{
    my $class = shift;
    my $self = {@_ };
    bless $self;
    return $self;
}

sub setup 
{
    my $self = shift;
    my $ua_requester = Mojo::UserAgent->new;
    bless $self;
    $self->{'ua_requester'} = $ua_requester;
    return $self;
}

sub get_api 
{
    my $self = shift;
    my $endpoint = shift;
    my $parameters = shift;
    my $ua_requester = $self->{'ua_requester'};
    my $url = $self->{'url'} . '/Api/?endpoint=' . $endpoint . $parameters;
    my $data = $ua_requester->get($url)->result->body;
    return $data;
}

1;