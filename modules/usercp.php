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

if(!function_exists('usercpCardSubtitle')) {
	function usercpCardSubtitle($type, $link) {
		if($type !== 'internal') {
			return 'External access module';
		}

		$normalizedLink = strtolower(trim((string)$link));
		$normalizedLink = trim($normalizedLink, '/');

		switch($normalizedLink) {
			case 'usercp/myaccount':
				return 'Account overview and details';
			case 'usercp/reset':
				return 'Character level reset module';
			case 'usercp/unstick':
				return 'Move a stuck character safely';
			case 'usercp/clearpk':
				return 'Clear PK status penalties';
			case 'usercp/resetstats':
				return 'Rebuild your character stats';
			case 'usercp/addstats':
				return 'Distribute level-up points';
			case 'usercp/vote':
				return 'Claim rewards by voting';
			case 'donation':
				return 'Support the server with donations';
			case 'usercp/buyzen':
				return 'Exchange credits for Zen';
			default:
				return 'Quick access module';
		}
	}
}

usort($cfg, function($a, $b) {
	$ao = isset($a['order']) ? (int)$a['order'] : 0;
	$bo = isset($b['order']) ? (int)$b['order'] : 0;
	return $ao - $bo;
});

echo '<div class="usercp-grid">';
	$cardIndex = 0;
	foreach($cfg as $element) {
		if(!is_array($element)) continue;
		if(!$element['active']) continue;
		$cardIndex++;

		$link = $element['type'] == 'internal' ? __BASE_URL__ . $element['link'] : $element['link'];
		$title = check_value(lang($element['phrase'], true)) ? lang($element['phrase']) : 'ERROR';
		$subtitle = usercpCardSubtitle($element['type'], $element['link']);
		$icon = check_value($element['icon']) ? __PATH_TEMPLATE_IMG__ . 'icons/' . $element['icon'] : __PATH_TEMPLATE_IMG__ . 'icons/usercp_default.png';
		$iconFallback = __PATH_TEMPLATE_IMG__ . 'icons/usercp_default.png';
		$themeClass = 'usercp-card-theme-' . (($cardIndex % 5) + 1);

		$target = $element['newtab'] ? ' target="_blank"' : '';
		echo '<a href="'.$link.'"'.$target.' class="usercp-card '.$themeClass.'">';
			echo '<div class="usercp-card-cover" style="background-image:url('.__PATH_TEMPLATE_IMG__.'usercp_bg.jpg);"></div>';
			echo '<div class="usercp-card-noise"></div>';
			echo '<div class="usercp-card-figure">';
				echo '<div class="usercp-card-icon"><img src="'.$icon.'" alt="'.$title.'" loading="lazy" onerror="this.onerror=null;this.src=\''.$iconFallback.'\';" /></div>';
			 echo '</div>';
			echo '<div class="usercp-card-body">';
				echo '<div class="usercp-card-title">'.$title.'</div>';
				echo '<div class="usercp-card-subtitle">'.htmlspecialchars($subtitle).'</div>';
			 echo '</div>';
		 echo '</a>';
	}
echo '</div>';

