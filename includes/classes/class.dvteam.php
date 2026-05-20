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

class DVT {
	
	public static function getGuildNameFromCharacterId($characterId) {
		$db = Connection::Database('MuOnline');
		$result = $db->query_fetch_single("SELECT FROM_BASE64(`guild_list`.`name`) AS `guild_name` FROM `guild_members` INNER JOIN `guild_list` ON  `guild_list`.`guid` = `guild_members`.`guild_id` WHERE `char_id` = ?", array($characterId));
		if(!is_array($result)) return;
		return $result['guild_name'];
	}
	
	public static function getGuildInfoFromName($guildName) {
		$db = Connection::Database('MuOnline');
		$result = $db->query_fetch_single("SELECT *, FROM_BASE64(`name`) AS `name` FROM `guild_list` WHERE `name` = TO_BASE64(?)", array($guildName));
		if(!is_array($result)) return;
		return $result;
	}
	
	public static function getGuildMembersFromGuildId($guildId) {
		$db = Connection::Database('MuOnline');
		$result = $db->query_fetch("SELECT `character_info`.`name`, `guild_members`.`ranking` FROM `guild_members` INNER JOIN `character_info` ON `character_info`.`guid` = `guild_members`.`char_id` WHERE `guild_id` = ?", array($guildId));
		if(!is_array($result)) return;
		return $result;
	}
	
	public static function getGuildMasterFromGuildId($guildId) {
		$db = Connection::Database('MuOnline');
		$result = $db->query_fetch_single("SELECT `character_info`.`name` FROM `guild_members` INNER JOIN `character_info` ON `character_info`.`guid` = `guild_members`.`char_id` WHERE `guild_members`.`ranking` = 128 AND `guild_id` = ?", array($guildId));
		if(!is_array($result)) return;
		return $result['name'];
	}
	
	public static function getOnlineStatusFromAccountId($accountId) {
		$db = Connection::Database('Me_MuOnline');
		$result = $db->query_fetch_single("SELECT * FROM `accounts_status` WHERE `account_id` = ? AND `online` = 1", array($accountId));
		if(!is_array($result)) return;
		return true;
	}
	
	public static function getOnlineStatusFromUsername($username) {
		$db = Connection::Database('Me_MuOnline');
		$result = $db->query_fetch_single("SELECT `accounts_status`.`online` FROM `accounts` INNER JOIN `accounts_status` ON `accounts_status`.`account_id` = `accounts`.`guid` WHERE `accounts_status`.`online` = 1 AND `accounts`.`account` = ?", array($username));
		if(!is_array($result)) return;
		return true;
	}
	
}