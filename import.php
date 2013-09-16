<?php

if(PHP_SAPI != 'cli') die('ERROR: You must run this script under shell.');

include_once __DIR__.'/bootstrap.php';
use \Symfony\Component\Yaml\Yaml;
use \Guzzle\Http\Client;
use \dflydev\markdown\MarkdownParser;
use \dflydev\markdown\MarkdownExtraParser;

$argTitle = '';
$argFile = '';
$argScriptogram = false;
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
	elseif($arg == '-s'){
		$argScriptogram = true;
	}
}

if(!$argTitle || !$argFile){
	print "Usage: ".$argv[0]." -t TITLE -f FILE [-s]\n";
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


$dtzone = new DateTimeZone('Europe/Vienna');
$date = new DateTime('now', $dtzone);
$markdownParser = new MarkdownExtraParser();
$client = new Client();

$categories = array('News', 'Article', 'Post');
$tags = '';
$link = '';
$error = 0;


$content = '';
if($fh = fopen($argFile, 'r')){
	$isHeader = true;
	$n = 0;
	while(!feof($fh)){
		$row = fgets($fh, 4096);
		$row = str_replace("\n", '', $row);
		#print "row: '$row'\n";
		if($isHeader){
			if($row == ''){
				$isHeader = false;
			}
			elseif(strtolower(substr($row, 0, 6)) == 'date: '){
				$date = new DateTime(substr($row, 6), $dtzone);
			}
			elseif(strtolower(substr($row, 0, 6)) == 'tags: '){
				$tags = substr($row, 6);
			}
			elseif(strtolower(substr($row, 0, 6)) == 'link: '){
				$link = substr($row, 6);
			}
		}
		else{
			if($n <= 5){
				if($row != ''){
					$content .= $row."\n";
				}
			}
			else{
				$content .= $row."\n";
			}
		}
		$n++;
	}
	fclose($fh);
}

$contentHtml = $markdownParser->transformMarkdown($content);


$request = $client->post($paramters['wordpress']['api_url'].'/sites/'.$paramters['wordpress']['site'].'/posts/new');
$request->addHeader('Authorization', 'Bearer '.$paramters['wordpress']['token']);

if($link){
	$request->setPostField('format', 'link');
	
	$categories[] = 'Link';
	$categories[] = 'URL';
	
	$contentHtml = '<p><a href="'.$link.'">'.$link.'</a></p>'.$contentHtml;
}
else{
	$request->setPostField('format', 'standard');
}

$request->setPostField('date', $date->format(DateTime::ISO8601));
$request->setPostField('title', $argTitle);
$request->setPostField('content', $contentHtml);
$request->setPostField('status', 'publish');
$request->setPostField('categories', $categories);
$request->setPostField('tags', $tags);

try{
	print "post file '".$argFile."'  ".$date->format('Y-m-d H:i:s')." ".($link ? 'link' : '')." ... ";
	$response = $request->send();
	$data = $response->json();
	if($data && isset($data['ID'])){
		print 'OK: '.$data['ID'];
	}
	else{
		print 'FAILED';
		$error++;
	}
	print "\n";
}
catch(Exception $e){
	print "ERROR: ".$e->getMessage()."\n";
	$error++;
}

exit((int)($error != 0));
