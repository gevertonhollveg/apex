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

if(!isLoggedIn()) redirect(1,'login');

echo '<div class="page-title"><span>'.lang('usercp_menu_title').'</span></div>';

$cfg = loadConfig('usercp');
if(!is_array($cfg)) throw new Exception('Could not load usercp, please contact support.');

echo '<div class="usercp-grid">';
	foreach($cfg as $element) {
		if(!is_array($element)) continue;
		if(!$element['active']) continue;
		$link = $element['type'] == 'internal' ? __BASE_URL__ . $element['link'] : $element['link'];
		$title = check_value(lang($element['phrase'], true)) ? lang($element['phrase']) : 'ERROR';
		$icon = check_value($element['icon']) ? __PATH_TEMPLATE_IMG__ . 'icons/' . $element['icon'] : __PATH_TEMPLATE_IMG__ . 'icons/usercp_default.png';

		echo $element['newtab'] ? '<a href="'.$link.'" target="_blank" class="usercp-card">' : '<a href="'.$link.'" class="usercp-card">';
			echo '<div class="usercp-card-icon"><img src="'.$icon.'" alt="'.$title.'" loading="lazy" /></div>';
			echo '<div class="usercp-card-title">'.$title.'</div>';
			echo '<div class="usercp-card-action">Open <i class="fa-solid fa-arrow-right"></i></div>';
		echo '</a>';
	}
echo '</div>';

