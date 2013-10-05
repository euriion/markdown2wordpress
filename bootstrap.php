<?php

require __DIR__.'/vendor/autoload.php';

declare(ticks = 1);
$exit = 0;
if(function_exists('pcntl_signal')){
	function signalHandler($signo){ global $exit; $exit++; if($exit >= 2) exit(); }
	pcntl_signal(SIGTERM, 'signalHandler');
	pcntl_signal(SIGINT, 'signalHandler');
}

$paramtersFilePath = __DIR__.'/parameters.yml';
if(!file_exists($paramtersFilePath)){
	print "ERROR: please set up ".$paramtersFilePath."\n";
	
	$paramters = array(
		'wordpress' => array(
			'api_url' => 'https://public-api.wordpress.com/rest/v1',
			'site' => 'YOU_SITE.wordpress.com',
			'redirect_uri' => 'http://fox21.at/oauth.php',
			'consumer_key' => '',
			'consumer_secret' => '',
			'token' => '',
		),
	);
	
	file_put_contents($paramtersFilePath, Yaml::dump($paramters));
	exit(1);
}
