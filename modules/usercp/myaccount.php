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

if(!isLoggedIn()) redirect(1,'login');

echo '<div class="page-title"><span>'.lang('module_titles_txt_4').'</span></div>';

// module status
if(!mconfig('active')) throw new Exception(lang('error_47'));
	
// common class
$common = new common();

// Retrieve Account Information
$accountInfo = $common->accountInformation($_SESSION['userid']);
if(!is_array($accountInfo)) throw new Exception(lang('error_12'));

# account online status
$onlineStatus = (DVT::getOnlineStatusFromAccountId($_SESSION['userid']) ? '<span class="label label-success">'.lang('myaccount_txt_9').'</span>' : '<span class="label label-danger">'.lang('myaccount_txt_10').'</span>');

# account status
$accountStatus = ($accountInfo[_CLMN_BLOCCODE_] == 1 ? '<span class="label label-danger">'.lang('myaccount_txt_8').'</span>' : '<span class="label label-default">'.lang('myaccount_txt_7').'</span>');

# characters info
$Character = new Character();
$AccountCharacters = $Character->AccountCharacter($_SESSION['username']);

$creditRows = array();
try {
	$creditSystem = new CreditSystem();
	$creditCofigList = $creditSystem->showConfigs();
	if(is_array($creditCofigList)) {
		foreach($creditCofigList as $myCredits) {
			if(!$myCredits['config_display']) continue;

			$creditSystem->setConfigId($myCredits['config_id']);
			switch($myCredits['config_user_col_id']) {
				case 'userid':
					$creditSystem->setIdentifier($accountInfo[_CLMN_MEMBID_]);
					break;
				case 'username':
					$creditSystem->setIdentifier($accountInfo[_CLMN_USERNM_]);
					break;
				case 'email':
					$creditSystem->setIdentifier($accountInfo[_CLMN_EMAIL_]);
					break;
				default:
					continue 2;
			}

			$configCredits = $creditSystem->getCredits();
			$creditRows[] = array(
				'title' => $myCredits['config_title'],
				'value' => number_format($configCredits)
			);
		}
	}
} catch(Exception $ex) {}

$charactersCount = (is_array($AccountCharacters) ? count($AccountCharacters) : 0);
$safeUsername = htmlspecialchars($accountInfo[_CLMN_USERNM_]);
$safeEmail = htmlspecialchars($accountInfo[_CLMN_EMAIL_]);

echo '<div class="myaccount-modern">';
	echo '<div class="myaccount-modern-summary">';
		echo '<div class="myaccount-modern-stat">';
			echo '<span class="myaccount-modern-stat-label">'.lang('myaccount_txt_1').'</span>';
			echo '<span class="myaccount-modern-stat-value">'.$accountStatus.'</span>';
		echo '</div>';
		echo '<div class="myaccount-modern-stat">';
			echo '<span class="myaccount-modern-stat-label">'.lang('myaccount_txt_5').'</span>';
			echo '<span class="myaccount-modern-stat-value">'.$onlineStatus.'</span>';
		echo '</div>';
		echo '<div class="myaccount-modern-stat">';
			echo '<span class="myaccount-modern-stat-label">Characters</span>';
			echo '<span class="myaccount-modern-stat-value">'.number_format($charactersCount).'</span>';
		echo '</div>';
		echo '<div class="myaccount-modern-stat">';
			echo '<span class="myaccount-modern-stat-label">Account</span>';
			echo '<span class="myaccount-modern-stat-value">'.$safeUsername.'</span>';
		echo '</div>';
	echo '</div>';

	echo '<div class="myaccount-modern-panel">';
		echo '<div class="myaccount-modern-row">';
			echo '<div class="myaccount-modern-key">'.lang('myaccount_txt_2').'</div>';
			echo '<div class="myaccount-modern-val">'.$safeUsername.'</div>';
		echo '</div>';
		echo '<div class="myaccount-modern-row">';
			echo '<div class="myaccount-modern-key">'.lang('myaccount_txt_3').'</div>';
			echo '<div class="myaccount-modern-val">'.$safeEmail.' <a href="'.__BASE_URL__.'usercp/myemail/" class="myaccount-modern-action">'.lang('myaccount_txt_6').'</a></div>';
		echo '</div>';
		echo '<div class="myaccount-modern-row">';
			echo '<div class="myaccount-modern-key">'.lang('myaccount_txt_4').'</div>';
			echo '<div class="myaccount-modern-val">&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226; <a href="'.__BASE_URL__.'usercp/mypassword/" class="myaccount-modern-action">'.lang('myaccount_txt_6').'</a></div>';
		echo '</div>';

		if(is_array($creditRows) && count($creditRows) > 0) {
			foreach($creditRows as $creditRow) {
				echo '<div class="myaccount-modern-row">';
					echo '<div class="myaccount-modern-key">'.htmlspecialchars($creditRow['title']).'</div>';
					echo '<div class="myaccount-modern-val">'.$creditRow['value'].'</div>';
				echo '</div>';
			}
		}
	echo '</div>';
echo '</div>';

// Account Characters
echo '<div class="page-title"><span>'.lang('myaccount_txt_15').'</span></div>';
if(is_array($AccountCharacters)) {
	$onlineCharacters = loadCache('online_characters.cache') ? loadCache('online_characters.cache') : array();
	echo '<div class="myaccount-character-grid">';
		foreach($AccountCharacters as $characterName) {
			$characterData = $Character->CharacterData($characterName);
			if(!is_array($characterData)) continue;
			
			if(defined('_TBL_MASTERLVL_')) {
				if(_TBL_MASTERLVL_ != _TBL_CHR_) {
					$characterMLData = $Character->getMasterLevelInfo($characterName);
					if(is_array($characterMLData)) {
						$characterData[_CLMN_CHR_LVL_] += $characterMLData[_CLMN_ML_LVL_];
					}
				} else {
					$characterData[_CLMN_CHR_LVL_] += $characterData[_CLMN_ML_LVL_];
				}
			}
			
			$characterClassAvatar = getPlayerClassAvatar($characterData[_CLMN_CHR_CLASS_], false);
			$characterOnlineStatus = in_array($characterName, $onlineCharacters) ? '<img src="'.__PATH_ONLINE_STATUS__.'" class="online-status-indicator"/>' : '<img src="'.__PATH_OFFLINE_STATUS__.'" class="online-status-indicator"/>';
			echo '<div class="myaccount-character-card">';
				echo '<div class="myaccount-character-name">'.playerProfile($characterName).$characterOnlineStatus.'</div>';
				echo '<div class="myaccount-character-block">';
					echo '<a href="'.playerProfile($characterName, true).'" target="_blank">';
						echo '<img src="'.$characterClassAvatar.'" />';
					echo '</a>';
				echo '</div>';
				echo '<div class="myaccount-character-block-location">'.returnMapName($characterData[_CLMN_CHR_MAP_]).'<br />'.$characterData[_CLMN_CHR_MAP_X_].', '.$characterData[_CLMN_CHR_MAP_Y_].'</div>';
				echo '<span class="myaccount-character-block-level">'.$characterData[_CLMN_CHR_LVL_].'</span>';
			echo '</div>';
		}
	echo '</div>';
} else {
	message('warning', lang('error_46'));
}