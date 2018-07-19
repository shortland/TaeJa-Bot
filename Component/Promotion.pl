#!/usr/bin/perl

use v5.10;
use strict;
use warnings;
use Config::Tiny;
use MIME::Base64;
use JSON;
use Encode;
use lib '../';
use GetIDKey;
use Component::Request;
use Component::MessageRequest;
use Component::LeagueEmoji;

use utf8;
use open ':std', ':encoding(UTF-8)';

binmode(STDOUT, ':utf8');

sub sayPromotions {
    my ($url, $clanTag, $channel) = @_;
    my $data = getData($url . '/Api/?endpoint=promotions&clanTag=' . $clanTag);
    
    $data = decode_json($data);

    my @webhookSearch = split(m/\|/, GetIDKeyFromC($channel));
    my $webhookId = $webhookSearch[0];
    my $webhookKey = $webhookSearch[1];

    for my $user (@{$data}) {
        say $user->{'name'};
        
        if (!defined $user->{discord_id}) {
            MakeDiscordPostJson("/webhooks/$webhookId/$webhookKey", '{"content" : "Congratulations ' . $user->{name} . ' on your promotion!"}', "1", "");
        }
        else {
            MakeDiscordPostJson("/webhooks/$webhookId/$webhookKey", '{"content" : "Congratulations <@' . $user->{discord_id} . '> on your promotion!"}', "1", "");
        }
        
        my $text = '{
            "content": "",
            "embeds": [{
                "footer": {
                    "text": "These notifications occur once an hour at the 45th minute"
                },
                "author": {
                    "icon_url": "' . $url . '/Api/Static/Images/' . uc($user->{race}) . '.png",
                    "name": "<' . $user->{clan_tag} . '> ' . $user->{name} . '",
                    "url": "http://' . $user->{server} . '.battle.net/sc2/en' . $user->{path}.'/"
                },
                "thumbnail": {
                    "url": "' . $url . '/Api/?endpoint=leagueimages&tier=1&league=' . ucfirst($user->{league}) . '",
                    "height": 60,
                    "width": 60
                },
                "type": "rich",
                "color": 4343284,
                "fields": [
                    {
                        "name": "Profile:",
                        "value": "*http://' . $user->{server} . '.battle.net/sc2/en' . $user->{path} . '/*",
                        "inline": 0
                    }, 
                    {
                        "name": "RankedFTW:",
                        "value": "*http://www.rankedftw.com/search/?name=http://' . $user->{server} . '.battle.net/sc2/en' . $user->{path} . '/*",
                        "inline": 0
                    }, 
                    {
                        "name": "League:",
                        "value": "*' . ucfirst($user->{league}) . '*",
                        "inline": 1
                    }, 
                    {
                        "name": "Tier:",
                        "value": "*' . $user->{tier} . '*",
                        "inline": 1
                    }, 
                    {
                        "name": "Promoted:",
                        "value": "*' . (scalar localtime $user->{join_time_stamp}) . '*",
                        "inline": 1
                    }, 
                    {
                        "name": "Minutes Ago:",
                        "value": "*' . $user->{promoted_min_ago} . '*",
                        "inline": 1
                    }
                ]
            }]
        }';
        $text = encode_base64(encode('utf-8', $text), "");
        MakeDiscordPostJson("/webhooks/$webhookId/$webhookKey", "$text", "1", "", "", "base64");
    }
}

BEGIN {
    my $configFile = '../config.ini';
    my $config = Config::Tiny->read($configFile, 'utf8');

    my $clanTag = $ARGV[0];
    my $channel = $config->{'servers_bot_announcements'}->{$clanTag}; 
    my $botUrl = $config->{'bot'}->{'url'};

    sayPromotions($botUrl, $clanTag, $channel);
}