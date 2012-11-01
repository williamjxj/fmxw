#!/usr/bin/perl

use strict;
use warnings;
use utf8;
use encoding 'utf8';
use WWW::Mechanize;
use Encode qw(decode);

use lib qw(/home/williamjxj/scraper/lib/);
use config;
use google;

use constant SURL => q{http://www.google.com};

print "Content-type: text/html; charset=utf-8\n\n";

print "Hello World from Perl!";

exit;
