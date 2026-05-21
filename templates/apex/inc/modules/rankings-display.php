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

echo '<style>
	.rankings-podium {
		display: flex;
		justify-content: center;
		align-items: flex-end;
		gap: 20px;
		flex-basis: 100%;
		justify-self: center;
		margin-left: auto;
		margin-right: auto;
	}
	.rankings-podium .podium-first {
		position: relative;
		order: 3;
	}
	.rankings-podium .podium-first .podium-character img {
		width: 120px !important;
		height: 120px !important;
		box-shadow: 0 0 20px rgba(255,215,0,0.7), 0 0 40px rgba(255,215,0,0.4) !important;
		border: 3px solid #fff !important;
	}
	.rankings-podium .podium-first .player-name {
		text-shadow: 2px 2px 4px rgba(0,0,0,0.3), 0 0 10px rgba(255,215,0,0.8);
		font-size: 1.15em;
		margin-top: 8px;
	}
	.rankings-podium .podium-first::before {
		content: "ðŸ‘‘";
		position: absolute;
		top: -30px;
		left: 50%;
		transform: translateX(-50%);
		font-size: 2.5em;
		animation: crown-bounce 0.6s ease-in-out infinite;
		z-index: 10;
	}
	@keyframes crown-bounce {
		0%, 100% { transform: translateX(-50%) translateY(0); }
		50% { transform: translateX(-50%) translateY(-8px); }
	}
	.rankings-podium .podium-second,
	.rankings-podium .podium-third,
	.rankings-podium .podium-fourth,
	.rankings-podium .podium-fifth {
		order: initial;
	}
	.rankings-podium .podium-second {
		order: 2;
	}
	.rankings-podium .podium-third {
		order: 4;
	}
	.rankings-podium .podium-fourth {
		order: 1;
	}
	.rankings-podium .podium-fifth {
		order: 5;
	}
</style>';

echo '<div class="rankings-podium">';

if (is_array($rankingsCache) && !empty($rankingsCache)) {
	// Skip the first line (cache timestamp) and get top 5 players
	$rankingsData = array_slice($rankingsCache, 1); // Skip first element (timestamp)
	$topPlayers = array_slice($rankingsData, 0, 5);

	// Arrange players for podium display: [4th, 2nd, 1st, 3rd, 5th]
	$podiumOrder = [];
	if (isset($topPlayers[3])) $podiumOrder[] = ['player' => $topPlayers[3], 'rank' => 4]; // 4th position
	if (isset($topPlayers[1])) $podiumOrder[] = ['player' => $topPlayers[1], 'rank' => 2]; // 2nd position  
	if (isset($topPlayers[0])) $podiumOrder[] = ['player' => $topPlayers[0], 'rank' => 1]; // 1st position (center)
	if (isset($topPlayers[2])) $podiumOrder[] = ['player' => $topPlayers[2], 'rank' => 3]; // 3rd position
	if (isset($topPlayers[4])) $podiumOrder[] = ['player' => $topPlayers[4], 'rank' => 5]; // 5th position

	foreach ($podiumOrder as $entry) {
		$player = $entry['player'];
		$rank = $entry['rank'];
		$characterClass = templateGetCharacterClass($player[1]);

		// Define podium classes based on rank
		$podiumClass = '';
		switch ($rank) {
			case 1:
				$podiumClass = 'podium-first';
				break;
			case 2:
				$podiumClass = 'podium-second';
				break;
			case 3:
				$podiumClass = 'podium-third';
				break;
			case 4:
				$podiumClass = 'podium-fourth';
				break;
			case 5:
				$podiumClass = 'podium-fifth';
				break;
		}

		echo '<div class="ranking-podium-item ' . $podiumClass . '">';
		echo '<div class="podium-rank">' . $rank . '</div>';
		echo '<div class="podium-character">';
		$avatarPath = __PATH_TEMPLATE_IMG__ . 'character-avatars/' . strtolower($characterClass) . '.jpg';
		$defaultAvatar = __PATH_TEMPLATE_IMG__ . 'character-avatars/dk.jpg';
		echo '<img src="' . $avatarPath . '" alt="' . $player[0] . '" onerror="this.onerror=null;this.src=\'' . $defaultAvatar . '\'">';
		echo '</div>';
		echo '<div class="podium-info">';
		$playerNameDisplay = ($rank == 1) ? 'ðŸ‘‘ ' . $player[0] : $player[0];
		echo '<div class="player-name">' . $playerNameDisplay . '</div>';
		echo '<div class="player-level">Level: ' . $player[2] . '</div>';
		echo '</div>';
		echo '</div>';
	}
} else {
	// Default rankings if no cache
	$defaultCharacters = [
		['DarkMaster', 400, 1250, 'dk', 16],
		['SoulReaper', 399, 1245, 'dw', 0],
		['BloodHunter', 398, 1240, 'elf', 32],
		['FireMage', 397, 1235, 'mg', 48],
		['IceQueen', 396, 1230, 'dl', 64]
	];

	// Arrange in podium order: [4th, 2nd, 1st, 3rd, 5th]
	$podiumOrder = [
		['player' => $defaultCharacters[3], 'rank' => 4],
		['player' => $defaultCharacters[1], 'rank' => 2],
		['player' => $defaultCharacters[0], 'rank' => 1],
		['player' => $defaultCharacters[2], 'rank' => 3],
		['player' => $defaultCharacters[4], 'rank' => 5]
	];

	foreach ($podiumOrder as $entry) {
		$char = $entry['player'];
		$rank = $entry['rank'];
		$characterClass = templateGetCharacterClass($char[1]);

		// Define podium classes based on rank
		$podiumClass = '';
		switch ($rank) {
			case 1:
				$podiumClass = 'podium-first';
				break;
			case 2:
				$podiumClass = 'podium-second';
				break;
			case 3:
				$podiumClass = 'podium-third';
				break;
			case 4:
				$podiumClass = 'podium-fourth';
				break;
			case 5:
				$podiumClass = 'podium-fifth';
				break;
		}

		echo '<div class="ranking-podium-item ' . $podiumClass . '">';
		echo '<div class="podium-rank">' . $rank . '</div>';
		echo '<div class="podium-character">';
		$avatarPath = __PATH_TEMPLATE_IMG__ . 'character-avatars/' . strtolower($characterClass) . '.jpg';
		$defaultAvatar = __PATH_TEMPLATE_IMG__ . 'character-avatars/dk.jpg';
		echo '<img src="' . $avatarPath . '" alt="' . $char[0] . '" onerror="this.onerror=null;this.src=\'' . $defaultAvatar . '\'">';
		echo '</div>';
		echo '<div class="podium-info">';
		$playerNameDisplay = ($rank == 1) ? 'ðŸ‘‘ ' . $char[0] : $char[0];
		echo '<div class="player-name">' . $playerNameDisplay . '</div>';
		echo '<div class="player-level">Level: ' . $char[2] . '</div>';
		echo '</div>';
		echo '</div>';
	}
}

echo '</div>';
