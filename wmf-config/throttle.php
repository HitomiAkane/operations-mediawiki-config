<?php
# WARNING: This file is publicly viewable on the web. Do not put private data here.

# Initialize the array. Append to that array to add a throttle
$wmgThrottlingExceptions = [];

# $wmgThrottlingExceptions is an array of arrays of parameters:
#  'from'  => date/time to start raising account creation throttle
#  'to'    => date/time to stop
#
# Optional arguments can be added to set the value or restrict by client IP
# or project dbname. Options are:
#  'value'  => new value for $wgAccountCreationThrottle (default: 50)
#  'IP'     => client IP as given by $wgRequest->getIP() or array (default: any IP)
#  'range'  => alternatively, the client IP CIDR ranges or array (default: any range)
#  'dbname' => a $wgDBname or array of dbnames to compare to
#             (eg. enwiki, metawiki, frwikibooks, eswikiversity)
#             Note that the limit is for the total number of account
#             creations on all projects. (default: any project)
# Example:
# $wmgThrottlingExceptions[] = [
# 'from'   => '2016-01-01T00:00 +0:00',
# 'to'     => '2016-02-01T00:00 +0:00',
# 'IP'     => '123.456.78.90',
# 'dbname' => [ 'xxwiki', etc. ],
# 'value'  => xx
# ];
## Add throttling definitions below.

$wmgThrottlingExceptions[] = [ // T238986
	'from' => '2019-12-07T12:00 UTC',
	'to' => '2019-12-07T20:00 UTC',
	'IP' => '201.236.179.50',
	'dbname' => [ 'eswiki', 'enwiki', 'commonswiki', 'wikidatawiki' ],
	'value' => 50
];

$wmgThrottlingExceptions[] = [ // T239465
	'from' => '2019-12-02T08:00 GMT+1',
	'to' => '2019-12-02T11:00 GMT+1',
	'range' => [ '90.161.45.24/30', '84.88.226.0/24' ],
	'dbname' => 'cawiki',
	'value' => 50
];

$wmgThrottlingExceptions[] = [
	'from' => '2019-12-10T0:00 UTC',
	'to' => '2019-12-12T0:00 UTC',
	'IP' => '195.113.183.5',
	'dbname' => 'cswiki',
	'value' => 30
];

## Add throttling definitions above.
