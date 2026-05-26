<?php
/**
 * WebEngine CMS
 * https://webenginecms.org/
 * 
 * @version 1.2.6
 * @author Lautaro Angelico <http://lautaroangelico.com/>
 * @copyright (c) 2013-2025 Lautaro Angelico, All Rights Reserved
 * 
 * Licensed under the MIT license
 * http://opensource.org/licenses/MIT
 */

echo '<div class="page-title"><span>'.lang('module_titles_txt_8',true).'</span></div>';

try {
	
	if(!mconfig('active')) throw new Exception(lang('error_47',true));

	$downloadCLIENTS = array();
	$downloadPATCHES = array();
	$downloadTOOLS = array();
	$allDownloads = array();

	$downloadsCACHE = loadCache('downloads.cache');
	if(is_array($downloadsCACHE) && count($downloadsCACHE)) {
		$allDownloads = $downloadsCACHE;
	} else {
		// Fallback to DB when cache is empty or stale.
		$db = config('SQL_USE_2_DB',true) ? Connection::Database('Me_MuOnline') : Connection::Database('MuOnline');
		$downloadsDB = $db->query_fetch("SELECT * FROM ".WEBENGINE_DOWNLOADS." ORDER BY download_type ASC, download_id ASC");
		if(is_array($downloadsDB) && count($downloadsDB)) {
			$allDownloads = $downloadsDB;
			@updateCacheFile('downloads.cache', encodeCache($downloadsDB));
		}
	}

	if(is_array($allDownloads)) {
		foreach($allDownloads as $tempDownloadsData) {
			if(!is_array($tempDownloadsData)) continue;
			switch((int)$tempDownloadsData['download_type']) {
				case 1:
					$downloadCLIENTS[] = $tempDownloadsData;
				break;
				case 2:
					$downloadPATCHES[] = $tempDownloadsData;
				break;
				case 3:
					$downloadTOOLS[] = $tempDownloadsData;
				break;
			}
		}
	}

	$renderedCount = 0;

	$renderDownloadSection = function($title, $items, $typeClass, $iconClass) use (&$renderedCount) {
		if(!is_array($items) || !count($items)) return;
		$renderedCount += count($items);

		echo '<section class="downloads-section downloads-section-'.$typeClass.'">';
			echo '<header class="downloads-section-header">';
				echo '<h3 class="downloads-section-title"><i class="fa-solid '.$iconClass.'"></i> '.$title.'</h3>';
				echo '<span class="downloads-section-count">'.count($items).' item'.(count($items) > 1 ? 's' : '').'</span>';
			echo '</header>';

			echo '<div class="downloads-grid">';
				foreach($items as $download) {
					$downloadTitle = isset($download['download_title']) ? $download['download_title'] : '-';
					$downloadDescription = isset($download['download_description']) ? $download['download_description'] : '';
					$downloadSize = isset($download['download_size']) ? round((float)$download['download_size'], 2) : 0;
					$downloadLink = isset($download['download_link']) ? $download['download_link'] : '#';

					echo '<article class="download-card download-card-'.$typeClass.'">';
						echo '<div class="download-card-head">';
							echo '<div class="download-card-icon"><i class="fa-solid '.$iconClass.'"></i></div>';
							echo '<div class="download-card-titlewrap">';
								echo '<h4 class="download-card-title">'.htmlspecialchars($downloadTitle).'</h4>';
								echo '<div class="download-card-size">'.$downloadSize.' '.lang('downloads_txt_4',true).'</div>';
							echo '</div>';
						echo '</div>';

						if(check_value($downloadDescription)) {
							echo '<p class="download-card-description">'.htmlspecialchars($downloadDescription).'</p>';
						}

						echo '<a href="'.htmlspecialchars($downloadLink).'" class="btn btn-primary download-card-action" target="_blank" rel="noopener noreferrer">';
							echo '<i class="fa-solid fa-download"></i> '.lang('downloads_txt_5',true);
						echo '</a>';
					echo '</article>';
				}
			echo '</div>';
		echo '</section>';
	};

	echo '<div class="downloads-module">';
		if(mconfig('show_client_downloads')) {
			$renderDownloadSection(lang('downloads_txt_6',true), $downloadCLIENTS, 'client', 'fa-hard-drive');
		}
		if(mconfig('show_patch_downloads')) {
			$renderDownloadSection(lang('downloads_txt_7',true), $downloadPATCHES, 'patch', 'fa-screwdriver-wrench');
		}
		if(mconfig('show_tool_downloads')) {
			$renderDownloadSection(lang('downloads_txt_8',true), $downloadTOOLS, 'tool', 'fa-toolbox');
		}
		if($renderedCount === 0) {
			message('warning', 'No download links are available right now.');
		}
	echo '</div>';
	
} catch(Exception $ex) {
	message('error', $ex->getMessage());
}