#!/usr/bin/env perl

use v5.10;
use strict;
use warnings;

binmode STDOUT, ":utf8";

use Config::Tiny;
use Bot::TaeJa;

use Command::Help;
use Command::Info;
use Command::Say;

use Command::Player;
use Command::Clan;
use Command::Bounds;
use Command::Export;
use Data::Dumper;

# Fallback to "config.ini" if the user does not pass in a config file.
my $config_file = $ARGV[0] // 'config.ini';
my $config = Config::Tiny->read($config_file, 'utf8');
say localtime(time) . " Loaded Config: $config_file";

my %config = %{$config};
foreach my $label (keys %config) {
	foreach my $key (keys %{$config{$label}}) {
		$config{$label}->{$key} =~ s/"//g;
	}
}

my $self = {};  # For miscellaneous information about this bot such as discord id

# Initialize the bot
my $bot = Bot::TaeJa->new(%{$config});

# Register the commands
# The new() function in each command will register with the bot.
Command::Help->new          ('bot' => $bot);
Command::Info->new          ('bot' => $bot);
Command::Say->new           ('bot' => $bot);

Command::Player->new        ('bot' => $bot);
Command::Clan->new          ('bot' => $bot);
Command::Bounds->new        ('bot' => $bot);
Command::Export->new        ('bot' => $bot);

# Start the bot
$bot->start();
