<?php

/**
 * WebEngine CMS - Rankings Display Module
 * https://webenginecms.org/
 * 
 * @version 1.2.6-dvteam
 * @author Lautaro Angelico <http://lautaroangelico.com/>
 * @copyright (c) 2013-2025 Lautaro Angelico, All Rights Reserved
 * 
 * Licensed under the MIT license
 * http://opensource.org/licenses/MIT
 */

// Load rankings from cache
$rankingsCache = LoadCacheData('rankings_level.cache');

if (!is_array($rankingsCache) || empty($rankingsCache)) {
	return;
}

// Skip cache timestamp/header entry and use only top 5 players
$rankingsData = array_slice($rankingsCache, 1);
$topPlayers = array_slice($rankingsData, 0, 5);
if (!is_array($topPlayers) || empty($topPlayers)) {
	return;
}

// Arrange podium visually: 4th, 2nd, 1st, 3rd, 5th
$podiumOrder = array();
if (isset($topPlayers[3])) $podiumOrder[] = array('player' => $topPlayers[3], 'rank' => 4);
if (isset($topPlayers[1])) $podiumOrder[] = array('player' => $topPlayers[1], 'rank' => 2);
if (isset($topPlayers[0])) $podiumOrder[] = array('player' => $topPlayers[0], 'rank' => 1);
if (isset($topPlayers[2])) $podiumOrder[] = array('player' => $topPlayers[2], 'rank' => 3);
if (isset($topPlayers[4])) $podiumOrder[] = array('player' => $topPlayers[4], 'rank' => 5);

echo '<div class="rankings-podium">';
foreach ($podiumOrder as $entry) {
	$player = $entry['player'];
	$rank = (int)$entry['rank'];
	$characterClass = templateGetCharacterClass($player[1]);
	$podiumClass = 'podium-rank-'.$rank;

	$avatarPath = __PATH_TEMPLATE_IMG__ . 'character-avatars/' . strtolower($characterClass) . '.jpg';
	$defaultAvatar = __PATH_TEMPLATE_IMG__ . 'character-avatars/dk.jpg';
	$playerName = htmlspecialchars($player[0], ENT_QUOTES, 'UTF-8');
	$playerLevel = number_format((int)$player[2]);

	echo '<div class="ranking-podium-item '.$podiumClass.'">';
		echo '<div class="podium-rank">#'.$rank.'</div>';
		echo '<div class="podium-character">';
			echo '<img src="'.$avatarPath.'" alt="'.$playerName.'" onerror="this.onerror=null;this.src=\''.$defaultAvatar.'\'">';
			echo '</div>';
		echo '<div class="podium-info">';
			echo '<div class="player-name">'.($rank === 1 ? '&#x1F451; ' : '').$playerName.'</div>';
			echo '<div class="player-level">Level '.$playerLevel.'</div>';
		echo '</div>';
	echo '</div>';
}
echo '</div>';
