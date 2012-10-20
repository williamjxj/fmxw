#!/usr/bin/perl

use strict;
use warnings;
use Data::Dumper;

use MongoDB;
use MongoDB::OID;

my $conn = MongoDB::Connection->new;

print Dumper($conn);

my $host = $ARGV[0] || 'localhost';

my $conn = MongoDB::Connection->new(host => $host);
my $config = $conn->config;
my $chunks = $config->chunks;

my $temp3 = $chunks->find;
print "before loop\n";
while (my $temp4 = $temp3->next) {
	print "in loop\n";
} 
