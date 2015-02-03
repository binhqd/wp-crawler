<?php

require_once(dirname(__FILE__).'/libs/vendor/autoload.php');
require_once(dirname(__FILE__).'/libs/Inflector.php');
require_once(dirname(__FILE__).'/libs/simpleHtmlDom.php');
require_once(dirname(__FILE__).'/functions.php');

if(!empty($_POST['listPhotos'])){
	if(!empty($_POST['listPhotos'])){
		$photos = explode(',',$_POST['listPhotos']);
		if (!empty($photos)) {
			$dir = createImageDir(md5(uniqid(32)), 'download');
			downloadMultipleFiles($photos, $dir);

			exit('Done');
		}else{
			throw new Execption("Invalid request!!");
		}
	}else{
		throw new Execption("Photo invalid");
	}
}else{
	throw new Exception('Photo empty!!!');
}