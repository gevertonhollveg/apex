<?php
/**
 * WebEngine CMS
 * https://webenginecms.org/
 * 
 * @version 1.2.6-dvteam
 * @author Lautaro Angelico <http://lautaroangelico.com/>
 * @copyright (c) 2013-2025 Lautaro Angelico, All Rights Reserved
 * 
 * Licensed under the MIT license
 * http://opensource.org/licenses/MIT
 */

// File Name
$file_name = basename(__FILE__);

// load database
$mu = Connection::Database('MuOnline');

$result = array();

$query = "SELECT `"._CLMN_CHR_NAME_."` FROM `"._TBL_CHR_."` WHERE `"._CLMN_CHR_ONLINE_."` = 1";

$onlineCharactersList = $mu->query_fetch($query);
if(is_array($onlineCharactersList)) {
	foreach($onlineCharactersList as $onlineCharacterData) {
		if(in_array($onlineCharacterData[_CLMN_CHR_NAME_], $result)) continue;
		$result[] = $onlineCharacterData[_CLMN_CHR_NAME_];
	}
}

$cacheData = encodeCache($result);
updateCacheFile('online_characters.cache', $cacheData);

// UPDATE CRON
updateCronLastRun($file_name);