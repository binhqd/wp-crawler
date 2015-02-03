<?php

require_once(dirname(__FILE__).'/libs/vendor/autoload.php');
require_once(dirname(__FILE__).'/libs/Inflector.php');
require_once(dirname(__FILE__).'/libs/simpleHtmlDom.php');

// You should request every page for once crawler
// It maybe take about 5 minutes to 10 minutes 

$url = 'http://www.gotceleb.com';
$pagination = $url . '/page/%s';
$listAttr = '.post-list';
$itemAttr = '#(<div class="post-thumbnail"[^>]*?>)(.*?)(</div>)#ism';
$total = 4261;

function extractImage($url)
{
	$size = '-'.end(explode('-', $url));
	$ext = '.'.end(explode('.', $size));
	return str_replace($size, '', $url).$ext;
}

function parseItem($html, $thumbnail)
{
	$htmlDom = str_get_html($html);
	$htmlItem = $htmlDom->find('.gallery-size-thumbnail', 0)->innertext;
	preg_match_all('/<img[^>]+>/i',$htmlItem, $results);
	$items = array();
	if (!empty($results[0])) {
		foreach ($results[0] as $item) {
			preg_match('/(?<!_)src=([\'"])?(.*?)\\1/', $item, $matches);
			$link = extractImage($matches[2]);
			if ($link == $thumbnail) {
				continue;
			} else {
				$items[] = extractImage($matches[2]);
			}
		}
	}
	return $items;
}

function crawlerImages($url, $attr, $multiples = false, $start = 1, $end = 2)
{
	$crawler = new GuzzleHttp\Client();
	$strHtml = '';
	if ($multiples) {
		for ($i = $start; $i <= $end; $i++) {
			if ($i == 1) {
				$html = (string)$crawler->get($url)->getBody();
			} else {
				if (strpos('/page/', $url)!==false) {
					$html = (string)$crawler->get(sprintf($url, $i))->getBody();
				} else {
					throw new Exception('Url invalid');
				}
			}
			$htmlDom = str_get_html($html);
			$strHtml.= $htmlDom->find($attr, 0)->innertext;
		}
	} else {
		$html = (string)$crawler->get($url)->getBody();
		$htmlDom = str_get_html($html);
		$strHtml = $htmlDom->find($attr, 0)->innertext;
	}
	return $strHtml;
}

function extractLinks($html, $attr)
{
	$crawler = new GuzzleHttp\Client();
	preg_match_all($attr, $html, $listCrawler);
	$links = array();
	$items = array();
	if (!empty($listCrawler[0])) {
		foreach ($listCrawler[0] as $link) {
			$dom = str_get_html($link);
			$title = $dom->find('a', 0)->getAttribute('title');
			$link = $dom->find('a', 0)->href;
			$thumbnail = extractImage($dom->find('a > img', 0)->src);
			$links[] = array(
				'title' => $title,
				'link' => $link,
				'thumbnail' => $thumbnail
			);
		}
		foreach ($links as $link) {
			$itemDetail = parseItem((string)$crawler->get($link['link'])->getBody(), $link['link']);
			array_push($itemDetail, $link['thumbnail']);
			$items[] = array(
				'dirname' => Inflector::slug($link['title']),
				'items' => $itemDetail
			);
		}
		return $items;
	}
}

function downloadMultipleFiles($urls, $dir)
{
	set_time_limit(0);
	$multi_handle = curl_multi_init();  
	$file_pointers = array();  
	$curl_handles = array();  
	foreach ($urls as $key => $url) {  
	  	$file = $dir.'/'.basename($url);  
	  	if(!is_file($file)){  
	    	$curl_handles[$key] = curl_init($url);  
	    	$file_pointers[$key] = fopen ($file, "w");  
	    	curl_setopt($curl_handles[$key], CURLOPT_FILE, $file_pointers[$key]);  
	    	curl_setopt($curl_handles[$key], CURLOPT_HEADER , 0);  
	    	curl_setopt($curl_handles[$key], CURLOPT_CONNECTTIMEOUT, 60);  
	    	curl_multi_add_handle($multi_handle, $curl_handles[$key]);  
	  	}  
	}  
	  
	do {  
	  curl_multi_exec($multi_handle,$running);  
	}  
	while($running > 0);  
	  
	foreach ($urls as $key => $url) {
		if (isset($curl_handles[$key])) {
			curl_multi_remove_handle($multi_handle, $curl_handles[$key]);  
		  	curl_close($curl_handles[$key]);  
		  	fclose($file_pointers[$key]);
		}  
	}  
	curl_multi_close($multi_handle); 
}

function createImageDir($name, $page)
{
	$path = dirname(__FILE__).'/photos/';
	if (is_dir($path) && is_writable($path)) {
		$folder = $path.'page-'.$page.'/'.$name;
		$create = mkdir($folder, 0777, true);
		if ($create) {
			chmod($folder, 0777);
			return $folder;
		} else {
			mkdir($folder);
			chmod($folder, 0777);
			return $folder;
		}
	} else {
		throw new Exception('Path not exits');
	}
}

function writeLog($content)
{
	$root = dirname(__FILE__).'/logs/';
    if(is_dir($root)) {
        $path = $root.date('d-m-Y').".log";
        if(!file_exists($path)){
            $file = fopen($path,'a');
            fwrite($file, date('d/m/Y H:i:s')." ".$content.PHP_EOL);
        }else{
            $file = fopen($path,'a+');
            fwrite($file, date('d/m/Y H:i:s')." ".$content.PHP_EOL);
        }
    }
}