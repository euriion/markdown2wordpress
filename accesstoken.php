<?php

if(PHP_SAPI != 'cli') die('ERROR: You must run this script under shell.');

include_once __DIR__.'/bootstrap.php';

use \Symfony\Component\Yaml\Yaml;
use \Guzzle\Http\Client;

$argCode = '';
$argc = count($argv);
for($argn = 1; $argn < $argc; $argn++){
	$arg = $argv[$argn];
	if($arg == '-c'){
		$argn++;
		$argCode = $argv[$argn];
	}
}

$paramtersFilePath = __DIR__.'/parameters.yml';
if(!file_exists($paramtersFilePath)){
	die('ERROR: File "'.$paramtersFilePath.'" not found.'."\n");
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
){
	print "ERROR: parameters invalid.\n";
	var_export($paramters); print "\n";
	exit(1);
}

$url = 'https://public-api.wordpress.com/oauth2/authorize?client_id='.$paramters['wordpress']['consumer_key'].'&redirect_uri='.urlencode($paramters['wordpress']['redirect_uri']).'&response_type=code';

if(!$argCode){
	print "url: $url\n";
	
	#system('/usr/bin/open -a "/Applications/Google Chrome.app" --args "'.$url.'"');
	system('/usr/bin/open -a "/Applications/Firefox.app" --args "'.$url.'"');
	
	print "\nnow execute:\nphp accesstoken.php -c CODE\n";
	
}
else{
	
	print "code: '".$argCode."'\n";
	
	$client = new Client(null, array('redirect.disable' => true));
	
	$request = $client->post('https://public-api.wordpress.com/oauth2/token');
	$request->setPostField('code', $argCode);
	$request->setPostField('client_id', $paramters['wordpress']['consumer_key']);
	$request->setPostField('client_secret', $paramters['wordpress']['consumer_secret']);
	$request->setPostField('redirect_uri', $paramters['wordpress']['redirect_uri']);
	$request->setPostField('grant_type', 'authorization_code');
	
	try{
		$response = $request->send();
		$data = $response->json();
		if($data && isset($data['access_token'])){
			print "token: '".$data['access_token']."'\n";
			
			$paramters = Yaml::parse($paramtersFilePath);
			$paramters['wordpress']['token'] = $data['access_token'];
			file_put_contents($paramtersFilePath, Yaml::dump($paramters));
		}
		else{
			print "ERROR: could not get token\n";
		}
	}
	catch(Exception $e){
		print "ERROR: ".$e->getMessage()."\n";
	}
	
}
