<?php
/**
 * WebEngine CMS
 * https://webenginecms.org/
 * 
 * @version 1.2.1
 * @author Lautaro Angelico <http://lautaroangelico.com/>
 * @copyright (c) 2013-2020 Lautaro Angelico, All Rights Reserved
 * 
 * Licensed under the MIT license
 * http://opensource.org/licenses/MIT
 */

// Module Title
echo '<div class="page-title"><span>'.lang('module_titles_txt_17').'</span></div>';

?>

<div class="module-info">
	<div class="module-info-grid">
		<div class="module-info-card">
			<div class="module-info-label"><i class="fa-solid fa-server"></i> Server Version</div>
			<div class="module-info-value"><?php echo config('server_info_season'); ?></div>
		</div>
		<div class="module-info-card">
			<div class="module-info-label"><i class="fa-solid fa-bolt"></i> Experience</div>
			<div class="module-info-value"><?php echo config('server_info_exp'); ?></div>
		</div>
		<div class="module-info-card">
			<div class="module-info-label"><i class="fa-solid fa-star"></i> Master Experience</div>
			<div class="module-info-value"><?php echo config('server_info_masterexp'); ?></div>
		</div>
		<div class="module-info-card">
			<div class="module-info-label"><i class="fa-solid fa-gem"></i> Drop</div>
			<div class="module-info-value"><?php echo config('server_info_drop'); ?></div>
		</div>
	</div>

	<div class="module-section-title"><i class="fa-solid fa-flask-vial"></i> Chaos Machine</div>
	<table class="table module-table module-table-info">
		<thead>
			<tr>
				<th rowspan="2">Combination</th>
				<th colspan="2" class="text-center">Maximum Success Rate</th>
			</tr>
			<tr>
				<th class="text-center">Normal</th>
				<th class="text-center">Gold</th>
			</tr>
		</thead>
		<tbody>
			<tr><td>Item Luck</td><td class="text-center">x%</td><td class="text-center">x%</td></tr>
			<tr><td>Items +10, +11, +12</td><td class="text-center">x% + Luck</td><td class="text-center">x% + Luck</td></tr>
			<tr><td>Items +13, +14, +15</td><td class="text-center">x% + Luck</td><td class="text-center">x% + Luck</td></tr>
			<tr><td>Wings Level 1</td><td class="text-center">x% + Luck</td><td class="text-center">x% + Luck</td></tr>
			<tr><td>Wings Level 2</td><td class="text-center">x%</td><td class="text-center">x%</td></tr>
			<tr><td>Wings Level 3</td><td class="text-center">x%</td><td class="text-center">x%</td></tr>
			<tr><td>Wings Level 4</td><td class="text-center">x%</td><td class="text-center">x%</td></tr>
			<tr><td>Cape of Lord Mix</td><td class="text-center">x%</td><td class="text-center">x%</td></tr>
			<tr><td>Socket Weapon Mix</td><td class="text-center">x%</td><td class="text-center">x%</td></tr>
			<tr><td>Fragment of Horn Mix</td><td class="text-center">x%</td><td class="text-center">x%</td></tr>
			<tr><td>Broken Horn Mix</td><td class="text-center">x%</td><td class="text-center">x%</td></tr>
			<tr><td>Horn of Fenrir Mix</td><td class="text-center">x%</td><td class="text-center">x%</td></tr>
			<tr><td>Feather of Condor</td><td class="text-center">x%</td><td class="text-center">x%</td></tr>
			<tr><td>Ancient Hero's Soul</td><td class="text-center">x%</td><td class="text-center">x%</td></tr>
		</tbody>
	</table>

	<div class="module-section-title"><i class="fa-solid fa-people-group"></i> Party Bonus Experience</div>
	<table class="table module-table module-table-info">
		<thead>
			<tr>
				<th rowspan="2">Members</th>
				<th colspan="2" class="text-center">Experience Rate</th>
			</tr>
			<tr>
				<th class="text-center">Same Character Classes</th>
				<th class="text-center">Different Classes</th>
			</tr>
		</thead>
		<tbody>
			<tr><td>2 Players</td><td>EXP% + x%</td><td>EXP% + x%</td></tr>
			<tr><td>3 Players</td><td>EXP% + x%</td><td>EXP% + x%</td></tr>
			<tr><td>4 Players</td><td>EXP% + x%</td><td>EXP% + x%</td></tr>
			<tr><td>5 Players</td><td>EXP% + x%</td><td>EXP% + x%</td></tr>
		</tbody>
	</table>

	<div class="module-section-title"><i class="fa-solid fa-terminal"></i> Commands</div>
	<table class="table module-table module-table-info module-table-commands">
		<thead>
			<tr>
				<th style="width:35%">Command</th>
				<th>Description</th>
			</tr>
		</thead>
		<tbody>
			<tr><td><code>/reset</code></td><td>Reset your character.</td></tr>
			<tr><td><code>/whisper [on/off]</code></td><td>Enable / disable whisper.</td></tr>
			<tr><td><code>/clearpk</code></td><td>Clear killer status.</td></tr>
			<tr><td><code>/post [message]</code></td><td>Sends a message to the whole server.</td></tr>
			<tr><td><code>/str [points]</code></td><td>Adds points to Strength.</td></tr>
			<tr><td><code>/addagi [points]</code></td><td>Adds points to Agility.</td></tr>
			<tr><td><code>/addvit [points]</code></td><td>Adds points to Life.</td></tr>
			<tr><td><code>/addene [points]</code></td><td>Adds points to Energy.</td></tr>
			<tr><td><code>/addcmd [points]</code></td><td>Adds points to Command.</td></tr>
			<tr><td><code>/requests [on/off]</code></td><td>Enable / disable requests in-game.</td></tr>
		</tbody>
	</table>
</div>