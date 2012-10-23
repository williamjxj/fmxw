#!/usr/bin/perl -w

use strict;
use warnings;
use Cache::Memcached;
use DBI;
use Data::Dumper;

# Configure the memcached server.
my $memd = new Cache::Memcached {
	'servers' => [
		'localhost:11211',
	],
};

my $keyword = $memd->get("keyword");
print Dumper($keyword);

my $include = $memd->get("include");
print Dumper($include);

my $exclude = $memd->get("exclude");
print Dumper($exclude);

