#!/usr/bin/perl
use strict;
use warnings;

# perl -T taint-mode.pl 'javascript:alert(1)'
#
# https://perldoc.perl.org/perlsec#Taint-mode

sub is_tainted {
	local $@;   # Don't pollute caller's value.
	return ! eval { eval("#" . substr(join("", @_), 0, 0)); 1 };
}

my $first_arg = shift @ARGV;

print "\n";
print "Hello '$first_arg'";
print "\n\n";
print is_tainted("A") ? 'Tainted' : 'Untainted';
print "\n";
print is_tainted($first_arg) ? 'Tainted' : 'Untainted';
print "\n";

if ($first_arg =~ /(.*)/) {
	$first_arg = $1; # Now untainted
} else {
	die "Bad data in '$first_arg'";
}

print is_tainted($first_arg) ? 'Tainted' : 'Untainted';
print "\n\n";

my $html_link = "<a href='$first_arg'>User Link</a>";
print $html_link;
print "\n";
print is_tainted($html_link) ? 'Tainted' : 'Untainted';
print "\n\n";
