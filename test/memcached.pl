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

$memd->set("my_key", "Some value");
$memd->set("object_key", { 'complex' => [ "object", 2, 4 ]});

my $val = $memd->get("key1");
print $val . "<br>\n";
$val = $memd->get("object_key");
if ($val) { print $val->{'complex'}->[2]; }

$memd->incr("key");
$memd->decr("key");
$memd->incr("key", 2);

