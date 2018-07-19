#!/usr/bin/perl

use v5.10;
use strict;
use warnings;
use Path::Tiny;

sub getHelpPage {
    my $data = path('Static/Help.txt')->slurp;
    say $data;
}

BEGIN {
    getHelpPage();
}