package Component::Db;

use v5.10;
use strict;
use warnings;

use Mojo::Discord;
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

sub connect 
{
    my $self = shift;
    my $dbh = DBI->connect(
        sprintf("DBI:mysql:database=%s;host=%s", 
            $self->{'database'}, 
            $self->{'host'}
        ),
        $self->{'username'},
        $self->{'password'},
        {'RaiseError' => 1}
    );
    $dbh->do('SET NAMES utf8mb4') or die $dbh->errstr;
    $dbh->{'mysql_enable_utf8mb4'} = 1;
    bless $self;
    $self->{'dbh'} = $dbh;
    return $self;
}

sub do_select 
{
    my $self = shift;
    my $query = shift;
    my $unique = shift;
    my $dbh = $self->{'dbh'};
    my $sth = $dbh->prepare("$query");
    say Dumper $query;
    $sth->execute();
    my $data = $sth->fetchall_arrayref;
    return $data;
}

# sub newConnection {
#     $dbh = DBI->connect(
#         sprintf("DBI:mysql:database=%s;host=%s", 
#             $self->{'database'}->{'database'}, 
#             $config->{'database'}->{'host'}
#         ),
#         $config->{'database'}->{'username'},
#         $config->{'database'}->{'password'},
#         {'RaiseError' => 1}
#     );

#     $dbh->do('SET NAMES utf8mb4') or die $dbh->errstr;
    
#     $dbh->{'mysql_enable_utf8mb4'} = 1;

#     return $dbh;
# }

sub execSelectQuery {
    my ($queryString) = @_;
    
}

1;