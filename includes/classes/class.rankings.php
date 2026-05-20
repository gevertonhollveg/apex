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

class Rankings {
	
	private $_results;
	private $_excludedCharacters = array('');
	private $_excludedGuilds = array('');
	private $_rankingsMenu;
	
	protected $config;
	protected $serverFiles;
	protected $mu;
	protected $me;
	
	function __construct() {
		
		// webengine configs
		$this->config = webengineConfigs();
		$this->serverFiles = strtolower($this->config['server_files']);
		
		// rankings configs
		loadModuleConfigs('rankings');
		$this->_results = (check_value(mconfig('rankings_results')) ? mconfig('rankings_results') : 25);
		
		// excluded characters
		if(check_value(mconfig('rankings_excluded_characters'))) {
			$excludedCharacters = explode(",", mconfig('rankings_excluded_characters'));
			$this->_excludedCharacters = $excludedCharacters;
		}
		
		// excluded guilds
		if(check_value(mconfig('rankings_excluded_guilds'))) {
			$excludedGuilds = explode(",", mconfig('rankings_excluded_guilds'));
			$this->_excludedGuilds = $excludedGuilds;
		}
		
		// rankings menu
		$this->_rankingsMenu = array(
			// language phrase, module, status, file-exclusive (array)
			array(lang('rankings_txt_1',true), 'level', mconfig('rankings_enable_level')),
			array(lang('rankings_txt_2',true), 'resets', mconfig('rankings_enable_resets')),
			array(lang('rankings_txt_3',true), 'killers', mconfig('rankings_enable_pk')),
			array(lang('rankings_txt_4',true), 'guilds', mconfig('rankings_enable_guilds')),
			array(lang('rankings_txt_5',true), 'grandresets', mconfig('rankings_enable_gr')),
			array(lang('rankings_txt_6',true), 'online', mconfig('rankings_enable_online'), array('xteam')),
			array(lang('rankings_txt_7',true), 'votes', mconfig('rankings_enable_votes')),
			array(lang('rankings_txt_8',true), 'gens', mconfig('rankings_enable_gens')),
			array(lang('rankings_txt_22',true), 'master', mconfig('rankings_enable_master')),
		);
		
		// extra menu links
		$extraMenuLinks = getRankingMenuLinks();
		if(is_array($extraMenuLinks)) {
			foreach($extraMenuLinks as $menuLink) {
				$this->_rankingsMenu[] = array($menuLink[0], $menuLink[1], true);
			}
		}
	}
   
	public function UpdateRankingCache($type) {
		switch($type) {
			case 'level':
				$this->_levelsRanking();
				break;
			case 'resets':
				$this->_resetsRanking();
				break;
			case 'killers':
				$this->_killersRanking();
				break;
			case 'grandresets':
				$this->_grandresetsRanking();
				break;
			case 'online':
				$this->_onlineRanking();
				break;
			case 'votes':
				$this->_votesRanking();
				break;
			case 'guilds':
				$this->_guildsRanking();
				break;
			case 'master':
				$this->_masterlevelRanking();
				break;
			case 'gens':
				$this->_gensRanking();
				break;
			default:
				return;
		}
	}
	
	private function _levelsRanking() {
		if(mconfig('combine_level_masterlevel')) {
			// level + master level combined (same tables)
			$result = $this->_getLevelRankingData(true);
		} else {
			// level only
			$result = $this->_getLevelRankingData(false);
		}
		if(!is_array($result)) return;
		
		$cache = BuildCacheData($result);
		UpdateCache('rankings_level.cache', $cache);
	}
	
	private function _resetsRanking() {
		if(mconfig('combine_level_masterlevel')) {
			// level + master level combined (same tables)
			$result = $this->_getResetRankingData(true);
		} else {
			// level only
			$result = $this->_getResetRankingData(false);
		}
		if(!is_array($result)) return;

		$cache = BuildCacheData($result);
		UpdateCache('rankings_resets.cache',$cache);
	}
	
	private function _killersRanking() {
		if(mconfig('combine_level_masterlevel')) {
			// level + master level combined (different tables)
			$result = $this->_getKillersRankingData(true);
		} else {
			// level only
			$result = $this->_getKillersRankingData(false);
		}
		if(!is_array($result)) return;

		$cache = BuildCacheData($result);
		UpdateCache('rankings_pk.cache',$cache);
	}
	
	private function _grandresetsRanking() {
		$this->mu = Connection::Database('MuOnline');
		
		$result = $this->mu->query_fetch("SELECT "._CLMN_CHR_NAME_.", "._CLMN_CHR_GRSTS_.", "._CLMN_CHR_RSTS_.", "._CLMN_CHR_CLASS_.", "._CLMN_CHR_MAP_." FROM "._TBL_CHR_." WHERE "._CLMN_CHR_GRSTS_." >= 1 AND "._CLMN_CHR_NAME_." NOT IN(".$this->_rankingsExcludeChars().") ORDER BY "._CLMN_CHR_GRSTS_." DESC, "._CLMN_CHR_RSTS_." DESC LIMIT ".$this->_results."");
		if(!is_array($result)) return;

		$cache = BuildCacheData($result);
		UpdateCache('rankings_gr.cache',$cache);
	}
	
	private function _guildsRanking() {
		$this->mu = Connection::Database('MuOnline');
		
		switch(mconfig('guild_score_formula')) {
			case 2:
				$result = $this->mu->query_fetch("SELECT FROM_BASE64(`t1`.`name`) AS `guild_name`, (SELECT `character_info`.`name` FROM `guild_members` INNER JOIN `character_info` ON `guild_members`.`char_id` = `character_info`.`guid` WHERE `guild_id` = `t1`.`guid` AND `guild_members`.`ranking` = '128') AS `master`, (SELECT SUM(`t3`.`strength`+`t3`.`agility`+`t3`.`vitality`+`t3`.`energy`+`t3`.`leadership`) FROM `guild_members` AS t2 INNER JOIN `character_info` AS t3 ON `t2`.`char_id` = `t3`.`guid` WHERE `t2`.`guild_id` = `t1`.`guid`) AS `score`,`t1`.`emblem` FROM `guild_list` AS t1 WHERE `t1`.`guid` NOT IN(".$this->_rankingsExcludeGuilds().") ORDER BY `score` DESC LIMIT ".$this->_results."");
				break;
			case 3:
				$result = $this->mu->query_fetch("SELECT FROM_BASE64(`t1`.`name`) AS `guild_name`, (SELECT `character_info`.`name` FROM `guild_members` INNER JOIN `character_info` ON `guild_members`.`char_id` = `character_info`.`guid` WHERE `guild_id` = `t1`.`guid` AND `guild_members`.`ranking` = '128') AS `master`, (SELECT SUM(`t3`.`strength`+`t3`.`agility`+`t3`.`vitality`+`t3`.`energy`) FROM `guild_members` AS t2 INNER JOIN `character_info` AS t3 ON `t2`.`char_id` = `t3`.`guid` WHERE `t2`.`guild_id` = `t1`.`guid`) AS `score`,`t1`.`emblem` FROM `guild_list` AS t1 WHERE `t1`.`guid` NOT IN(".$this->_rankingsExcludeGuilds().") ORDER BY `score` DESC LIMIT ".$this->_results."");
				break;
			default:
				$result = $this->mu->query_fetch("SELECT FROM_BASE64(`t1`.`name`) AS `guild_name`, (SELECT `character_info`.`name` FROM `guild_members` INNER JOIN `character_info` ON `guild_members`.`char_id` = `character_info`.`guid` WHERE `guild_id` = `t1`.`guid` AND `guild_members`.`ranking` = '128') AS `master`, `t1`.`score`, `t1`.`emblem` FROM `guild_list` AS t1 WHERE `t1`.`guid` NOT IN(".$this->_rankingsExcludeGuilds().") ORDER BY `t1`.`score` DESC LIMIT ".$this->_results."");
		}
		
		if(!is_array($result)) return;

		$cache = BuildCacheData($result);
		UpdateCache('rankings_guilds.cache',$cache);
	}
	
	private function _masterlevelRanking() {
		$this->mu = Connection::Database('MuOnline');
		
		
		// Master Level and Character in same table
		$result = $this->mu->query_fetch("SELECT "._CLMN_CHR_NAME_.", ("._CLMN_ML_LVL_."+"._CLMN_ML_MJLVL_.") AS "._CLMN_ML_LVL_.", "._CLMN_CHR_CLASS_.", "._CLMN_CHR_LVL_.", "._CLMN_CHR_MAP_." FROM "._TBL_CHR_." WHERE "._CLMN_CHR_NAME_." NOT IN(".$this->_rankingsExcludeChars().") AND "._CLMN_ML_LVL_." > 0 ORDER BY "._CLMN_ML_LVL_." DESC LIMIT ".$this->_results."");
		
		if(!is_array($result)) return;

		$cache = BuildCacheData($result);
		UpdateCache('rankings_master.cache',$cache);
	}
	
	private function _gensRanking() {
		$duprianData = $this->_generateGensRankingData(1);
		if(!is_array($duprianData)) $duprianData = array();
		
		$vanertData = $this->_generateGensRankingData(2);
		if(!is_array($vanertData)) $vanertData = array();
		
		$rankingData = array_merge($duprianData,$vanertData);
		usort($rankingData, function($a, $b) {
			return $b['contribution'] - $a['contribution'];
		});
		$result = array_slice($rankingData, 0, $this->_results);
		if(empty($result)) return;
		if(!is_array($result)) return;
		
		$cache = BuildCacheData($result);
		UpdateCache('rankings_gens.cache',$cache);
	}
	
	private function _votesRanking() {
		$this->me = Connection::Database('Me_MuOnline');
		
		$voteMonth = date("m/01/Y 00:00");
		$voteMonthTimestamp = strtotime($voteMonth);
		$accounts = $this->me->query_fetch("SELECT user_id,COUNT(*) as count FROM ".WEBENGINE_VOTE_LOGS." WHERE timestamp >= ? GROUP BY user_id ORDER BY count DESC LIMIT ".$this->_results."", array($voteMonthTimestamp));
		if(!is_array($accounts)) return;
		
		foreach($accounts as $data) {
			$common = new common();
			
			$accountInfo = $common->accountInformation($data['user_id']);
			if(!is_array($accountInfo)) continue;
			
			$Character = new Character();
			$characterName = $Character->AccountCharacterIDC($accountInfo[_CLMN_USERNM_]);
			if(!check_value($characterName)) continue;
			
			$characterData = $Character->CharacterData($characterName);
			if(!is_array($characterData)) continue;
			
			if(in_array($characterName, $this->_excludedCharacters)) continue;
			
			$result[] = array($characterName, $data['count'], $characterData[_CLMN_CHR_CLASS_], $characterData[_CLMN_CHR_MAP_]);
		}
		if(!is_array($result)) return;
		$cache = BuildCacheData($result);
		UpdateCache('rankings_votes.cache',$cache);
	}
	
	private function _onlineRanking() {
		// WIP
	}
	
	public function rankingsMenu() {
		echo '<div class="rankings_menu">';
		foreach($this->_rankingsMenu as $rm_item) {
			if(array_key_exists(3, $rm_item)) {
				if(is_array($rm_item[3])) {
					if(!in_array($this->serverFiles, $rm_item[3])) continue;
				}
			}
			if($rm_item[2]) {
				if($_REQUEST['subpage'] == $rm_item[1]) {
					echo '<a href="'.__PATH_MODULES_RANKINGS__.$rm_item[1].'/" class="active">'.$rm_item[0].'</a>';
				} else {
					echo '<a href="'.__PATH_MODULES_RANKINGS__.$rm_item[1].'/">'.$rm_item[0].'</a>';
				}
			}
		}
		echo '</div>';
	}
	
	private function _rankingsExcludeChars() {
		if(!is_array($this->_excludedCharacters)) return;
		$return = array();
		foreach($this->_excludedCharacters as $characterName) {
			$return[] = "'".$characterName."'";
		}
		return implode(",", $return);
	}
	
	private function _rankingsExcludeGuilds() {
		if(!is_array($this->_excludedGuilds)) return;
		$return = array();
		foreach($this->_excludedGuilds as $guildName) {
			$return[] = "'".$guildName."'";
		}
		return implode(",", $return);
	}
	
	private function _generateGensRankingData($influence=1) {
		$this->mu = Connection::Database('MuOnline');
		
		$result = $this->mu->query_fetch("SELECT `t2`.`"._CLMN_CHR_NAME_."`, `t1`.`"._CLMN_GENS_TYPE_."`, `t1`.`"._CLMN_CHR_LVL_."`, `t1`.`"._CLMN_GENS_POINT_."`, `t2`.`"._CLMN_CHR_CLASS_."`, `t2`.`"._CLMN_CHR_MAP_."` FROM `"._TBL_GENS_."` AS t1 INNER JOIN `"._TBL_CHR_."` AS t2 ON `t1`.`"._CLMN_GENS_ID_."` = `t2`.`"._CLMN_CHR_GUID_."` WHERE `t1`.`"._CLMN_GENS_TYPE_."` = ? AND `t2`.`"._CLMN_CHR_NAME_."` NOT IN(".$this->_rankingsExcludeChars().") ORDER BY `t1`.`"._CLMN_GENS_POINT_."` DESC", array($influence));
		if(!is_array($result)) return;
		
		foreach($result as $rankPos => $row) {
			$gensRank = getGensRank($row[_CLMN_GENS_POINT_]);
			if($row[_CLMN_GENS_POINT_] >= 10000) {
				$gensRank = getGensLeadershipRank($rankPos);
			}
			
			$rankingData[] = array(
				'name' => $row[_CLMN_CHR_NAME_],
				'influence' => $row[_CLMN_GENS_TYPE_],
				'contribution' => $row[_CLMN_GENS_POINT_],
				'rank' => $gensRank,
				'level' => $row[_CLMN_CHR_LVL_],
				'class' => $row[_CLMN_CHR_CLASS_],
				'map' => $row[_CLMN_CHR_MAP_]
			);
		}
		
		if(!is_array($rankingData)) return;
		return $rankingData;
	}
	
	private function _getLevelRankingData($combineMasterLevel=false) {
		$this->mu = Connection::Database('MuOnline');
		
		// level only (no master level)
		if(!$combineMasterLevel) {
			$result = $this->mu->query_fetch("SELECT "._CLMN_CHR_NAME_.","._CLMN_CHR_CLASS_.","._CLMN_CHR_LVL_.","._CLMN_CHR_MAP_." FROM "._TBL_CHR_." WHERE "._CLMN_CHR_NAME_." NOT IN(".$this->_rankingsExcludeChars().") ORDER BY "._CLMN_CHR_LVL_." DESC LIMIT ".$this->_results."");
			if(!is_array($result)) return;
			return $result;
		}
			
		// level + master level + majestic level (in same table)
		$result = $this->mu->query_fetch("SELECT "._CLMN_CHR_NAME_.","._CLMN_CHR_CLASS_.",("._CLMN_CHR_LVL_."+"._CLMN_ML_LVL_."+"._CLMN_ML_MJLVL_.") as "._CLMN_CHR_LVL_.","._CLMN_CHR_MAP_." FROM "._TBL_CHR_." WHERE "._CLMN_CHR_NAME_." NOT IN(".$this->_rankingsExcludeChars().") ORDER BY "._CLMN_CHR_LVL_." DESC LIMIT ".$this->_results."");
		if(!is_array($result)) return;
		return $result;
	}
	
	private function _getResetRankingData($combineMasterLevel=false) {
		$this->mu = Connection::Database('MuOnline');
		
		// level only (no master level)
		if(!$combineMasterLevel) {
			$result = $this->mu->query_fetch("SELECT "._CLMN_CHR_NAME_.","._CLMN_CHR_CLASS_.","._CLMN_CHR_RSTS_.","._CLMN_CHR_LVL_.","._CLMN_CHR_MAP_." FROM "._TBL_CHR_." WHERE "._CLMN_CHR_NAME_." NOT IN(".$this->_rankingsExcludeChars().") AND "._CLMN_CHR_RSTS_." > 0 ORDER BY "._CLMN_CHR_RSTS_." DESC, "._CLMN_CHR_LVL_." DESC LIMIT ".$this->_results."");
			if(!is_array($result)) return;
			return $result;
		}
		
		
		// level + master level + majectic level (in same table)
		$result = $this->mu->query_fetch("SELECT "._CLMN_CHR_NAME_.","._CLMN_CHR_CLASS_.","._CLMN_CHR_RSTS_.",("._CLMN_CHR_LVL_."+"._CLMN_ML_LVL_."+"._CLMN_ML_MJLVL_.") as "._CLMN_CHR_LVL_.","._CLMN_CHR_MAP_." FROM "._TBL_CHR_." WHERE "._CLMN_CHR_NAME_." NOT IN(".$this->_rankingsExcludeChars().") AND "._CLMN_CHR_RSTS_." > 0 ORDER BY "._CLMN_CHR_RSTS_." DESC, "._CLMN_CHR_LVL_." DESC LIMIT ".$this->_results."");
		if(!is_array($result)) return;
		return $result;
		
	}
	
	private function _getKillersRankingData($combineMasterLevel=false) {
		$this->mu = Connection::Database('MuOnline');
		
		// level only (no master level)
		if(!$combineMasterLevel) {
			$result = $this->mu->query_fetch("SELECT "._CLMN_CHR_NAME_.","._CLMN_CHR_CLASS_.","._CLMN_CHR_PK_KILLS_.","._CLMN_CHR_LVL_.","._CLMN_CHR_MAP_.","._CLMN_CHR_PK_LEVEL_." FROM "._TBL_CHR_." WHERE "._CLMN_CHR_NAME_." NOT IN(".$this->_rankingsExcludeChars().") AND "._CLMN_CHR_PK_KILLS_." > 0 ORDER BY "._CLMN_CHR_PK_KILLS_." DESC LIMIT ".$this->_results."");
			if(!is_array($result)) return;
			return $result;
		}
		
		
		// level + master level + majestic level (in same table)
		$result = $this->mu->query_fetch("SELECT "._CLMN_CHR_NAME_.","._CLMN_CHR_CLASS_.","._CLMN_CHR_PK_KILLS_.",("._CLMN_CHR_LVL_."+"._CLMN_ML_LVL_."+"._CLMN_ML_MJLVL_.") as "._CLMN_CHR_LVL_.","._CLMN_CHR_MAP_.","._CLMN_CHR_PK_LEVEL_." FROM "._TBL_CHR_." WHERE "._CLMN_CHR_NAME_." NOT IN(".$this->_rankingsExcludeChars().") AND "._CLMN_CHR_PK_KILLS_." > 0 ORDER BY "._CLMN_CHR_PK_KILLS_." DESC LIMIT ".$this->_results."");
		if(!is_array($result)) return;
		return $result;
	}
	
	private function _getOnlineRankingDataMembStatHours() {
		$this->mu = Connection::Database('MuOnline');
		
		$accounts = $this->mu->query_fetch("SELECT "._CLMN_MS_MEMBID_.", "._CLMN_MS_ONLINEHRS_." FROM "._TBL_MS_." WHERE "._CLMN_MS_ONLINEHRS_." > 0 ORDER BY "._CLMN_MS_ONLINEHRS_." DESC LIMIT ".$this->_results."");
		if(!is_array($accounts)) return;
		$Character = new Character();
		foreach($accounts as $row) {
			$playerIDC = $Character->AccountCharacterIDC($row[_CLMN_MS_MEMBID_]);
			if(!check_value($playerIDC)) continue;
			$platerData = $Character->CharacterData($playerIDC);
			if(!is_array($platerData)) continue;
			$result[] = array(
				$playerIDC,
				$row[_CLMN_MS_ONLINEHRS_]*3600,
				$platerData[_CLMN_CHR_CLASS_],
				$platerData[_CLMN_CHR_MAP_]
			);
		}
		if(!is_array($result)) return;
		return $result;
	}
	
	private function _getRankingsFilterData() {
		$classesData = custom('character_class');
		$rankingsFilter = custom('rankings_classgroup_filter');

		if(is_array($rankingsFilter)) {
			foreach($rankingsFilter as $class => $phrase) {
				if(!array_key_exists($class, $classesData)) continue;
				
				$filterName = lang($phrase) == 'ERROR' ? $phrase : lang($phrase);
				$classGroupList = array();
				foreach($classesData as $key => $row) {
					if($row['class_group'] == $class) {
						$classGroupList[] = $key;
					}
				}
				$filterList[] = array(
					$class,
					implode(',', $classGroupList),
					$filterName,
				);
			}
		}
		
		if(!is_array($filterList)) return;
		return $filterList;
	}
	
	public function rankingsFilterMenu() {
		$filterData = $this->_getRankingsFilterData();
		if(!is_array($filterData)) return;
		
		echo '<div class="text-center">';
			echo '<ul class="rankings-class-filter">';
				
				echo '<li><a onclick="rankingsFilterRemove()" class="rankings-class-filter-selection">'.getPlayerClassAvatar(-1, true, false, 'rankings-class-filter-image').'<br />'.lang('rankings_filter_1').'</a></li>';
				
				foreach($filterData as $row) {
					echo '<li><a onclick="rankingsFilterByClass('.$row[1].')" class="rankings-class-filter-selection rankings-class-filter-grayscale">'.getPlayerClassAvatar($row[0], true, false, 'rankings-class-filter-image').'<br />'.$row[2].'</a></li>';
				}
			echo '</ul>';
		echo '</div>';
	}

}