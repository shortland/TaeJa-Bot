package Command::Info;

use v5.10;
use strict;
use warnings;

use Exporter qw(import);
our @EXPORT_OK = qw(cmd_info);

use Mojo::Discord;
use Bot::TaeJa;
use Data::Dumper;

###########################################################################################
# Command Info
my $command = "Info";
my $access = 0; # Public
my $description = "Display information about the bot, including framework, creator, and source code";
my $pattern = '^info ?.*$';
my $function = \&cmd_info;
my $usage = <<EOF;
Usage: ```~info```
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

sub cmd_info
{
    my ($self, $channel, $author) = @_;

    my $discord = $self->{'discord'};
    my $bot = $self->{'bot'};

    my $info;
    
    # We can use some special formatting with the webhook.
    if ( my $hook = $bot->has_webhook($channel) )
    {
        $info = "**Info**\n" .
                "I am a TaeJa Bot by <\@131231443694125056>\n" .
                "I can show Starcraft II ranking details on users and clans.\n\n" .
                "**Source Code**\n" .
                "I am open source! I am written in Perl, and PHP, built on the [Mojo::Discord](<https://github.com/vsTerminus/Net-Discord>) library.\n" .
                "My source code is available [on GitHub](<https://github.com/shortland/TaeJa-Bot>).\n\n" .
                "**Add Me**\n" .
                "You can choose to run my source code yourself, or simply message <\@131231443694125056> to get me on your server.\n\n";

        $discord->send_webhook($channel, $hook, $info);
                
    }
    else
    {
        $info = "**Info**\n" .
                'I am a TaeJa Bot by <@131231443694125056>' . "\n" .
                "I can show Starcraft II ranking details on users and clans.\n\n".
                "**Source Code**\n" .
                "I am open source! I am written in Perl, and PHP, built on the [Mojo::Discord](<https://github.com/vsTerminus/Net-Discord>) library.\n" .
                "My source code is available [on GitHub](<https://github.com/shortland/TaeJa-Bot>).\n\n" .
                "**Add Me**\n" .
                "You can choose to run my source code yourself, or simply message <\@131231443694125056> to get me on your server.\n\n";


        $discord->send_message($channel, $info);
    }
}

1;
