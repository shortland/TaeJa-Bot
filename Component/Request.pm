package Component::Request;

use v5.10;
use strict;
use warnings;
use Mojo::UserAgent;

use Exporter;
our @ISA = qw(Exporter);
our @EXPORT_OK = qw(getData); # can be
our @EXPORT = qw(getData); # defaults to

sub getData {
    my ($url) = @_;

    my $ua = Mojo::UserAgent->new;

	my $response = $ua->get($url)->result->body;

	return $response;
}

1;