package Component::LeagueEmoji;

use v5.10;
use strict;
use warnings;
use Exporter;
no warnings 'experimental';

our @ISA = qw(Exporter);
our @EXPORT_OK = qw(leagueNameToEmoji leagueNumToEmoji);
our @EXPORT = qw(leagueNameToEmoji leagueNumToEmoji);

sub leagueNameToEmoji {
    my ($name) = @_;
    
    given (lc($name)) {
        when ($_ eq 'bronze') {
            return leagueNumToEmoji(0);
        }
        when ($_ eq 'silver') {
            return leagueNumToEmoji(1);
        }
        when ($_ eq 'gold') {
            return leagueNumToEmoji(2);
        }
        when ($_ eq 'platinum') {
            return leagueNumToEmoji(3);
        }
        when ($_ eq 'diamond') {
            return leagueNumToEmoji(4);
        }
        when ($_ eq 'master') {
            return leagueNumToEmoji(5);
        }
        when ($_ eq 'masters') {
            return leagueNumToEmoji(5);
        }
        when ($_ eq 'grandmaster') {
            return leagueNumToEmoji(6);
        }
    }
}

sub leagueNumToEmoji {
    my ($num) = @_;
    
    given ($num) {
        when ($_ eq 0) {
            return "<:BRONZE3:278725418641522688>";
        }
        when ($_ eq 1) {
            return "<:SILVER2:278725418813751297>";
        }
        when ($_ eq 2) {
            return "<:GOLD1:278725419073536012>";
        }
        when ($_ eq 3) {
            return "<:PLATINUM1:278725419056758784>";
        }
        when ($_ eq 4) {
            return "<:DIAMOND1:278725418960551937>";
        }
        when ($_ eq 5) {
            return "<:MASTER1:278725418679271425>";
        }
        when ($_ eq 6) {
            return "<:GRANDMASTER:278725419186782208>";
        }
    }
}

1;