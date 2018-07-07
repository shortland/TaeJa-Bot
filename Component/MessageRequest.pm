package Component::MessageRequest;

use v5.10;
use JSON;
use Try::Tiny;
use YAML::Tiny;
use MIME::Base64;
use Time::HiRes;

use Exporter;
our @ISA = qw(Exporter);
our @EXPORT_OK = qw(MakeDiscordGet MakeDiscordPostJson); # can be
our @EXPORT = qw(MakeDiscordGet MakeDiscordPostJson); # defaults to
my $yaml = YAML::Tiny->read('config.yml');
my $API_DISCORD = $yaml->[0]->{discord_api};

# @_[0] = endpoint /users/\@me
# @_[1] = 
# @_[2] = return decoded json, 0=no, 1=yes
# @_[3] = sleep time, if !defined then default.2
sub MakeDiscordGet {
	my ($endpoint, $etc, $return_json, $sleep_time) = @_;
    my $useragent = 'DiscordBot (http://ilankleiman.com, 4.0.0)';
    my $content_type = 'application/x-www-form-urlencoded';
	my $api_key = 'Bot ' . $API_DISCORD;
	my $base_url = 'https://discordapp.com/api';
    my $ua = Mojo::UserAgent->new;
	my %headers = (
		'Authorization'		=> $api_key,
		'Content-Type' 		=> $content_type,
		'User-Agent' 		=> $useragent
	);
	my $response = $ua->get($base_url . $endpoint => \%headers)->result->body;
	try {
		$response = decode_json($response) if ($return_json =~ /1/);
	}
	catch {
		$response = '';
	};
	if (!defined $sleep_time || $sleep_time eq '') {
		Time::HiRes::sleep(0.250);
	}
	else {
		Time::HiRes::sleep($sleep_time);
	}
	return $response;
}

# @_[0] = endpoint /users/\@me
# @_[1] = json to post
# @_[2] = return decoded json, 0=no, 1=yes
# @_[3] = post type, -X POST/PUT/DELETE/etc...
# @_[4] = sleep time, if !defined then default 1
# @_[5] = base64 or no
sub MakeDiscordPostJson {
	my @parms = @_;
	my ($endpoint, $json, $return_json, $type, $sleep_time, $base64) = @_;
	my $message;
	if ($base64 eq '' || !defined $base64) {
		$message = $json;
	}
	elsif ($base64 eq 'base64') {
		$message = decode_base64($json);
	}
	else {
		$message = $json;
	}
    my $useragent = 'DiscordBot (http://ilankleiman.com, 4.0.0)';
    my $content_type = 'application/json';
	my $api_key = 'Bot ' . $API_DISCORD;
	my $base_url = 'https://discordapp.com/api';
    my $ua = Mojo::UserAgent->new;
	my %headers = (
		'Authorization'		=> $api_key,
		'Content-Type' 		=> $content_type,
		'User-Agent' 		=> $useragent
	);
	my $response;
	if ($type eq '' || !defined $type || $type eq "POST") {
		$response = $ua->post($base_url . $endpoint => \%headers => $message)->result->body;
	}
	elsif ($type eq "PUT") {
		$response = $ua->put($base_url . $endpoint => \%headers => $message)->result->body;
	}
	elsif ($type eq "DELETE") {
		$response = $ua->delete($base_url . $endpoint => \%headers => $message)->result->body;
	}
	else {
		die "Unknown method to request data";
	}
	try {
		$response = decode_json($response) if ($return_json =~ /1/);
	}
	catch {
		# $response = $response;
	};
	if (!defined $sleep_time || $sleep_time eq '') {
		Time::HiRes::sleep(0.250);
	}
	else {
		Time::HiRes::sleep($sleep_time);
	}
	return $response;
}

1;