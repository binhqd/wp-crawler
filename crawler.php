<?php

require_once(dirname(__FILE__).'/libs/vendor/autoload.php');
require_once(dirname(__FILE__).'/libs/Inflector.php');
require_once(dirname(__FILE__).'/libs/simpleHtmlDom.php');
require_once(dirname(__FILE__).'functions.php';

// You should request every page for once crawler
// It maybe take about 5 minutes to 10 minutes 

$url = 'http://www.gotceleb.com';
$pagination = $url . '/page/%s';
$listAttr = '.post-list';
$itemAttr = '#(<div class="post-thumbnail"[^>]*?>)(.*?)(</div>)#ism';
$total = 4261;

if (!empty($_REQUEST['page'])) {
	$pageNumber = (int)$_REQUEST['page'];
	if ($pageNumber < $total) {
		$page = $pageNumber == 1 ? $url : sprintf($pagination, $pageNumber);
		$html = crawlerImages($page,$listAttr);
		$items = extractLinks($html, $itemAttr);
		foreach ($items as $item) {
			$dir = createImageDir($item['dirname'], $pageNumber);
			downloadMultipleFiles($item['items'], $dir);
		}
		$content = 'Crawler page success: '.$pageNumber.' - Crawler page pending: '.($total - $pageNumber);
		writeLog($content);
		echo json_encode(array('page' => $pageNumber, 'success' => false));
	} else {
		echo json_encode(array('page' => $pageNumber, 'success' => true));
	}
}