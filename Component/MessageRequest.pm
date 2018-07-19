package Component::MessageRequest;

use v5.10;
use JSON;
use Try::Tiny;
use Config::Tiny;
use MIME::Base64;
use Time::HiRes;
use Data::Dumper;

use Exporter;
our @ISA = qw(Exporter);
our @EXPORT_OK = qw(MakeDiscordGet MakeDiscordPostJson); # can be
our @EXPORT = qw(MakeDiscordGet MakeDiscordPostJson); # defaults to

my $configFile = "config.ini";
my $config = Config::Tiny->read($configFile, 'utf8');
my $API_DISCORD = $config->{'discord'}->{'token'};

sub new {
    my ($class, %params) = @_;
    my $self = {};
    bless $self, $class;
    
    $self->{'bot'} = $params{'bot'};
    $self->{'discord'} = $self->{'bot'}->discord;

    return $self;
}

sub discordGet {
	my $self = shift;
	my ($endpoint, $etc, $returnJson, $sleepTime) = @_;
	
	my $data = $self->{'discord'};
	my $useragent = sprintf("DiscordBot (%s, %s)", $data->{'url'}, $data->{'version'});
	my $contentType = 'application/x-www-form-urlencoded';
	my $apiKey = sprintf("Bot %s", $data->{'token'});
	my $baseUrl = 'https://discordapp.com/api';
	my $ua = Mojo::UserAgent->new;
	
	my %headers = (
		'Authorization' => $apiKey,
		'Content-Type' => $contentType,
		'User-Agent' => $useragent,
	);

	my $response = $ua->get($baseUrl . $endpoint => \%headers)->result->body;
	
	try {
		if ($returnJson eq 1) {
			$response = decode_json($response);
		}
	};

	if (!defined $sleepTime || $sleepTime eq '') {
		Time::HiRes::sleep(0.250);
	}
	else {
		Time::HiRes::sleep($sleepTime);
	}

	if ($data->{'verbose'} eq 1) {
		say sprintf("%s Component::MessageRequest Response Data %s", localtime(time) . '', (Dumper $response));
	}
	
	return $response;
}

sub discordPost {
	my $self = shift;
	my ($endpoint, $json, $returnJson, $type, $sleepTime, $base64) = @_;

	my $message;
	
	if ($base64 eq 'base64') {
		$message = decode_base64($json);
	}
	else {
		$message = $json;
	}

	my $data = $self->{'discord'};
	my $useragent = sprintf("DiscordBot (%s, %s)", $data->{'url'}, $data->{'version'});
	my $contentType = 'application/json';
	my $apiKey = sprintf("Bot %s", $data->{'token'});
	my $baseUrl = 'https://discordapp.com/api';
	my $ua = Mojo::UserAgent->new;
	
	my %headers = (
		'Authorization' => $apiKey,
		'Content-Type' => $contentType,
		'User-Agent' => $useragent,
	);

	my $response;

	if ($type eq '' || !defined $type || $type eq 'POST') {
		$response = $ua->post($baseUrl . $endpoint => \%headers => $message)->result->body;
	}
	elsif ($type eq 'PUT') {
		$response = $ua->put($baseUrl . $endpoint => \%headers => $message)->result->body;
	}
	elsif ($type eq 'DELETE') {
		$response = $ua->delete($baseUrl . $endpoint => \%headers => $message)->result->body;
	}
	else {
		die "Unknown method to request data";
	}

	try {
		if ($returnJson eq 1) {
			$response = decode_json($response);
		}
	};

	if (!defined $sleepTime || $sleepTime eq '') {
		Time::HiRes::sleep(0.250);
	}
	else {
		Time::HiRes::sleep($sleepTime);
	}

	return $response;
}

1;