<?php

date_default_timezone_set('Europe/Vienna');

if(PHP_SAPI != 'cli') die('ERROR: You must run this script under shell.');

include_once __DIR__.'/bootstrap.php';
use \Symfony\Component\Yaml\Yaml;
use \Guzzle\Http\Client;
use \dflydev\markdown\MarkdownParser;
use \dflydev\markdown\MarkdownExtraParser;

$argTitle = '';
$argFile = '';
$argc = count($argv);
for($argn = 1; $argn < $argc; $argn++){
	$arg = $argv[$argn];
	if($arg == '-t'){
		$argn++;
		$argTitle = $argv[$argn];
	}
	elseif($arg == '-f'){
		$argn++;
		$argFile = $argv[$argn];
	}
}

if(!$argFile){
	print "Usage: ".$argv[0]." -f post.md\n";
	exit(1);
}

if(!$argFile || !file_exists($argFile)){
	print "ERROR: file not found: '".$argFile."'\n";
	exit(1);
}

$paramters = Yaml::parse($paramtersFilePath);

if(
	!isset($paramters)
	|| !isset($paramters['wordpress'])
	|| !isset($paramters['wordpress']['api_url'])
	|| !isset($paramters['wordpress']['site'])
	|| !isset($paramters['wordpress']['redirect_uri'])
	|| !isset($paramters['wordpress']['consumer_key'])
	|| !isset($paramters['wordpress']['consumer_secret'])
	|| !isset($paramters['wordpress']['token'])
){
	print "ERROR: parameters invalid.\n";
	var_export($paramters); print "\n";
	exit(1);
}


#$date = new DateTime('now');
$date = new DateTime('now', new DateTimeZone('Europe/Vienna'));
$markdownParser = new MarkdownExtraParser();
$client = new Client();

$request = $client->post($paramters['wordpress']['api_url'].'/sites/'.$paramters['wordpress']['site'].'/posts/new');
$request->addHeader('Authorization', 'Bearer '.$paramters['wordpress']['token']);

$request->setPostField('date', $date->format(DateTime::ISO8601));
$request->setPostField('title', $argTitle);
$request->setPostField('content', $markdownParser->transformMarkdown(file_get_contents($argFile)));
$request->setPostField('status', 'publish');
$request->setPostField('format', 'standard');
#$request->setPostField('format', 'link');

try{
	print "post file '".$argFile."'  ".$date->format(DateTime::ISO8601)." ... ";
	$response = $request->send();
	$data = $response->json();
	if($data && isset($data['ID'])){
		print 'OK: '.$data['ID'];
	}
	else{
		print 'FAILED';
	}
	print "\n";
}
catch(Exception $e){
	print "ERROR: ".$e->getMessage()."\n";
	exit(1);
}
