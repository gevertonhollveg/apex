<?php
/**
 * Character Item Inventory Manager
 * AdminCP Module
 *
 * Manages character items from the character_item_inventory table.
 *
 * DB field format (semicolon-separated inside curly braces):
 * [0]  invType      – 0=Equipment, 1=Inventory, 2=PersonalStore, 3=Warehouse, 8=Muun
 * [1]  slot         – position in inventory grid
 * [2]  itemCode     – (512 * categoryId) + itemIndex  →  ItemList.xml
 * [3]  unknown
 * [4]  serial       – unique item serial number
 * [5]  unknown
 * [6]  level        – 0-15
 * [7]  durability
 * [8]  unknown
 * [9]  skill        – 0 or 1
 * [10] luck         – 0 or 1
 * [11] option       – add-option value (0,4,8,12,16)
 * [12] exc          – excellent options bitmask (6 bits)
 * [13] ancient      – ancient option bitmask
 * [14] unknown
 * [15] unknown
 * [16-20] sockets   – 5 socket slots (65535 = none)
 * [21] socketBonus  – socket set bonus (255 = none)
 * [22-41] extended  – remaining fields
 */

// ─── Item name loader ───
function loadItemNameMapAdmin($itemListPath) {
	$map = array();
	if (!is_file($itemListPath)) return $map;
	$reader = new XMLReader();
	if (!$reader->open($itemListPath, null, LIBXML_NONET)) return $map;
	$currentCategory = null;
	while ($reader->read()) {
		if ($reader->nodeType === XMLReader::ELEMENT) {
			if ($reader->name === 'Category') {
				$catIndex = $reader->getAttribute('Index');
				$catName = trim((string)$reader->getAttribute('Name'));
				$currentCategory = is_numeric($catIndex) ? array('index' => (int)$catIndex, 'name' => $catName) : null;
			} elseif ($reader->name === 'Item' && $currentCategory !== null) {
				$itemIndex = $reader->getAttribute('Index');
				$itemName = trim((string)$reader->getAttribute('Name'));
				if (is_numeric($itemIndex) && $itemName !== '') {
					$code = (512 * $currentCategory['index']) + (int)$itemIndex;
					$map[$code] = array(
						'name' => $itemName,
						'category' => $currentCategory['name'],
						'catIndex' => $currentCategory['index'],
						'itemIndex' => (int)$itemIndex,
					);
				}
			}
		} elseif ($reader->nodeType === XMLReader::END_ELEMENT && $reader->name === 'Category') {
			$currentCategory = null;
		}
	}
	$reader->close();
	return $map;
}

function getInventoryTypeName($type) {
	$types = array(
		0 => 'Equipment',
		1 => 'Inventory',
		2 => 'Personal Store',
		3 => 'Warehouse',
		8 => 'Muun Inventory',
	);
	return isset($types[$type]) ? $types[$type] : 'Type ' . $type;
}

// ─── Load item map ───
$itemListPath = __ROOT_DIR__ . 'modules/bags/ItemList.xml';
$itemMap = loadItemNameMapAdmin($itemListPath);

echo '<h1 class="page-header">Character Item Inventory</h1>';

// ═══════════ AJAX API ═══════════
if (isset($_GET['ajax'])) {
	header('Content-Type: application/json; charset=utf-8');
	while (ob_get_level()) ob_end_clean();

	$response = array('success' => false, 'message' => 'Invalid request.');

	try {
		switch ($_GET['ajax']) {

			case 'characters':
				if (!isset($_GET['account']) || !check_value($_GET['account'])) throw new Exception('Account name required.');
				$accName = $_GET['account'];
				$accRow = $dB->query_fetch_single("SELECT " . _CLMN_MEMBID_ . ", " . _CLMN_USERNM_ . " FROM " . _TBL_MI_ . " WHERE " . _CLMN_USERNM_ . " = ?", array($accName));
				if (!$accRow) throw new Exception('Account not found.');
				$accId = $accRow[_CLMN_MEMBID_];
				$chars = $dB->query_fetch("SELECT " . _CLMN_CHR_NAME_ . ", " . _CLMN_CHR_GUID_ . ", " . _CLMN_CHR_CLASS_ . ", " . _CLMN_CHR_LVL_ . " FROM " . _TBL_CHR_ . " WHERE " . _CLMN_CHR_ACCID_ . " = ?", array($accId));
				$charList = array();
				if (is_array($chars)) {
					foreach ($chars as $c) {
						$charList[] = array(
							'name'  => $c[_CLMN_CHR_NAME_],
							'guid'  => $c[_CLMN_CHR_GUID_],
							'class' => $c[_CLMN_CHR_CLASS_],
							'level' => $c[_CLMN_CHR_LVL_],
						);
					}
				}
				$response = array('success' => true, 'account' => $accName, 'accountId' => $accId, 'characters' => $charList);
				break;

			case 'items':
				if (!isset($_GET['guid']) || !is_numeric($_GET['guid'])) throw new Exception('Character GUID required.');
				$guid = (int)$_GET['guid'];
				$row = $dB->query_fetch_single("SELECT InventoryData FROM character_item_inventory WHERE GUID = ?", array($guid));
				if (!$row || !isset($row['InventoryData']) || $row['InventoryData'] === null) {
					$response = array('success' => true, 'guid' => $guid, 'items' => array());
					break;
				}
				$rawData = $row['InventoryData'];
				preg_match_all('/\{([^}]+)\}/', $rawData, $matches);
				$items = array();
				if (!empty($matches[1])) {
					foreach ($matches[1] as $itemStr) {
						$f = explode(';', $itemStr);
						if (count($f) < 16) continue;
						$itemCode = (int)$f[2];
						$itemName = 'Unknown Item';
						$catName = ''; $catIdx = -1; $itmIdx = -1;
						if (isset($itemMap[$itemCode])) {
							$itemName = $itemMap[$itemCode]['name'];
							$catName  = $itemMap[$itemCode]['category'];
							$catIdx   = $itemMap[$itemCode]['catIndex'];
							$itmIdx   = $itemMap[$itemCode]['itemIndex'];
						}
						$sockets = array();
						for ($s = 16; $s <= 20; $s++) {
							$sockets[] = isset($f[$s]) ? (int)$f[$s] : 65535;
						}
						$items[] = array(
							'invType'     => (int)$f[0],
							'invTypeName' => getInventoryTypeName((int)$f[0]),
							'slot'        => (int)$f[1],
							'itemCode'    => $itemCode,
							'itemName'    => $itemName,
							'category'    => $catName,
							'catIndex'    => $catIdx,
							'itemIndex'   => $itmIdx,
							'serial'      => $f[4],
							'level'       => (int)$f[6],
							'durability'  => (int)$f[7],
							'skill'       => (int)$f[9],
							'luck'        => (int)$f[10],
							'option'      => (int)$f[11],
							'exc'         => (int)$f[12],
							'ancient'     => (int)$f[13],
							'sockets'     => $sockets,
							'socketBonus' => isset($f[21]) ? (int)$f[21] : 255,
						);
					}
				}
				$response = array('success' => true, 'guid' => $guid, 'items' => $items);
				break;

			case 'itemlist':
				$list = array();
				foreach ($itemMap as $code => $info) {
					$list[] = array('code' => $code, 'name' => $info['name'], 'category' => $info['category'], 'catIndex' => $info['catIndex'], 'itemIndex' => $info['itemIndex']);
				}
				$response = array('success' => true, 'items' => $list);
				break;
		}
	} catch (Exception $ex) {
		$response = array('success' => false, 'message' => $ex->getMessage());
	}
	echo json_encode($response);
	die();
}

// ═══════════ POST ACTIONS ═══════════
if (isset($_POST['item_action'])) {
	try {
		$action = $_POST['item_action'];
		if (!isset($_POST['char_guid']) || !is_numeric($_POST['char_guid'])) throw new Exception('Invalid character GUID.');
		$guid = (int)$_POST['char_guid'];

		if (isset($_POST['account_name']) && check_value($_POST['account_name'])) {
			if ($common->accountOnline($_POST['account_name'])) {
				throw new Exception('The account is currently online. Please wait until the player disconnects.');
			}
		}

		$row = $dB->query_fetch_single("SELECT InventoryData FROM character_item_inventory WHERE GUID = ?", array($guid));
		$rawData = ($row && isset($row['InventoryData'])) ? $row['InventoryData'] : '';
		preg_match_all('/\{([^}]+)\}/', $rawData, $matches);
		$allItems = !empty($matches[1]) ? $matches[1] : array();

		switch ($action) {
			case 'edit':
				if (!isset($_POST['item_index']) || !is_numeric($_POST['item_index'])) throw new Exception('Invalid item index.');
				$idx = (int)$_POST['item_index'];
				if (!isset($allItems[$idx])) throw new Exception('Item not found at position ' . $idx . '.');
				$fields = explode(';', $allItems[$idx]);
				if (count($fields) < 16) throw new Exception('Invalid item data.');

				if (isset($_POST['new_item_code']) && is_numeric($_POST['new_item_code'])) $fields[2] = (int)$_POST['new_item_code'];
				if (isset($_POST['new_level']) && is_numeric($_POST['new_level'])) {
					$v = (int)$_POST['new_level'];
					if ($v < 0 || $v > 15) throw new Exception('Level must be between 0 and 15.');
					$fields[6] = $v;
				}
				if (isset($_POST['new_durability']) && is_numeric($_POST['new_durability'])) {
					$v = (int)$_POST['new_durability'];
					if ($v < 0 || $v > 255) throw new Exception('Durability must be 0-255.');
					$fields[7] = $v;
				}
				if (isset($_POST['new_skill']))   $fields[9]  = (int)$_POST['new_skill'] ? 1 : 0;
				if (isset($_POST['new_luck']))    $fields[10] = (int)$_POST['new_luck'] ? 1 : 0;
				if (isset($_POST['new_option']) && is_numeric($_POST['new_option'])) {
					$v = (int)$_POST['new_option'];
					if (!in_array($v, array(0, 4, 8, 12, 16))) throw new Exception('Option must be 0, 4, 8, 12 or 16.');
					$fields[11] = $v;
				}
				if (isset($_POST['new_exc']) && is_numeric($_POST['new_exc'])) {
					$v = (int)$_POST['new_exc'];
					if ($v < 0 || $v > 63) throw new Exception('Excellent must be 0-63.');
					$fields[12] = $v;
				}
				if (isset($_POST['new_ancient']) && is_numeric($_POST['new_ancient'])) $fields[13] = (int)$_POST['new_ancient'];

				$allItems[$idx] = implode(';', $fields);
				message('success', 'Item updated successfully.');
				break;

			case 'delete':
				if (!isset($_POST['item_index']) || !is_numeric($_POST['item_index'])) throw new Exception('Invalid item index.');
				$idx = (int)$_POST['item_index'];
				if (!isset($allItems[$idx])) throw new Exception('Item not found at position ' . $idx . '.');
				array_splice($allItems, $idx, 1);
				message('success', 'Item removed successfully.');
				break;

			case 'add':
				if (!isset($_POST['add_inv_type']) || !is_numeric($_POST['add_inv_type'])) throw new Exception('Invalid inventory type.');
				if (!isset($_POST['add_slot']) || !is_numeric($_POST['add_slot'])) throw new Exception('Invalid slot.');
				if (!isset($_POST['add_item_code']) || !is_numeric($_POST['add_item_code'])) throw new Exception('Invalid item code.');
				if (!isset($_POST['add_level']) || !is_numeric($_POST['add_level'])) throw new Exception('Invalid level.');

				$addLevel = (int)$_POST['add_level'];
				if ($addLevel < 0 || $addLevel > 15) throw new Exception('Level must be between 0 and 15.');
				$addDur    = isset($_POST['add_durability']) && is_numeric($_POST['add_durability']) ? (int)$_POST['add_durability'] : 255;
				$addSkill  = isset($_POST['add_skill']) ? ((int)$_POST['add_skill'] ? 1 : 0) : 0;
				$addLuck   = isset($_POST['add_luck']) ? ((int)$_POST['add_luck'] ? 1 : 0) : 0;
				$addOpt    = isset($_POST['add_option']) && is_numeric($_POST['add_option']) ? (int)$_POST['add_option'] : 0;
				$addExc    = isset($_POST['add_exc']) && is_numeric($_POST['add_exc']) ? (int)$_POST['add_exc'] : 0;
				$addAnc    = isset($_POST['add_ancient']) && is_numeric($_POST['add_ancient']) ? (int)$_POST['add_ancient'] : 0;

				if (!in_array($addOpt, array(0, 4, 8, 12, 16))) throw new Exception('Option must be 0, 4, 8, 12 or 16.');
				if ($addExc < 0 || $addExc > 63) throw new Exception('Excellent must be 0-63.');

				$addSerial = mt_rand(10000, 99999);

				$newFields = array(
					(int)$_POST['add_inv_type'], (int)$_POST['add_slot'], (int)$_POST['add_item_code'], 0, $addSerial, 0,
					$addLevel, $addDur, 0,
					$addSkill, $addLuck, $addOpt, $addExc, $addAnc, 0, 0,
					65535, 65535, 65535, 65535, 65535, 255,
					0, 0, 0, 0, 0, 0, 0, 0, 0,
					255, 255, 255, 255, 255, 255,
					0, 0, 254, 254, 254, 254
				);
				$allItems[] = implode(';', $newFields);
				message('success', 'Item added successfully.');
				break;

			default:
				throw new Exception('Invalid action.');
		}

		$newData = '';
		foreach ($allItems as $i => $item) {
			if ($i > 0) $newData .= ",\n";
			$newData .= '{' . $item . '}';
		}

		if ($row) {
			$dB->query("UPDATE character_item_inventory SET InventoryData = ? WHERE GUID = ?", array($newData, $guid));
		} else {
			$dB->query("INSERT INTO character_item_inventory (GUID, InventoryData) VALUES (?, ?)", array($guid, $newData));
		}
	} catch (Exception $ex) {
		message('error', $ex->getMessage());
	}
}
?>

<!-- ═══════════ SEARCH FORM ═══════════ -->
<div class="row">
	<div class="col-md-12">
		<div class="panel panel-default">
			<div class="panel-heading"><i class="fa fa-search"></i> Search Account</div>
			<div class="panel-body">
				<form class="form-inline" id="searchAccountForm">
					<div class="form-group">
						<input type="text" class="form-control" id="accountInput" placeholder="Account name" style="width:250px;">
					</div>
					<button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> Search</button>
				</form>
			</div>
		</div>
	</div>
</div>

<!-- Characters -->
<div class="row" id="charactersSection" style="display:none;">
	<div class="col-md-12">
		<div class="panel panel-info">
			<div class="panel-heading"><i class="fa fa-user"></i> Characters for: <strong id="accountLabel"></strong></div>
			<div class="panel-body" id="charactersList"></div>
		</div>
	</div>
</div>

<style>
.ci-badge-normal { background:#999; color:#fff; }
.ci-badge-exc { background:#5cb85c; color:#fff; }
.ci-badge-ancient { background:#5bc0de; color:#1a5632; font-weight:bold; }
.ci-badge-socket { background:#9b59b6; color:#fff; }
.ci-item-row td { vertical-align:middle!important; }
.ci-item-name { cursor:default; }
.ci-type-badge { cursor:default; font-size:11px; padding:3px 8px; display:inline-block; border-radius:3px; }
.ci-popover-content { font-size:12px; line-height:1.6; }
.ci-popover-content .ci-pop-label { color:#888; font-size:11px; }
.ci-popover-content .ci-pop-val { font-weight:bold; }
.ci-opts-line { margin:2px 0; }
</style>
<!-- Items Table -->
<div class="row" id="itemsSection" style="display:none;">
	<div class="col-md-12">
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-cubes"></i> Items for: <strong id="characterLabel"></strong>
				<button class="btn btn-success btn-xs pull-right" onclick="showAddItemModal()"><i class="fa fa-plus"></i> Add Item</button>
			</div>
			<div class="panel-body">
				<div class="table-responsive">
					<table class="table table-striped table-condensed table-hover" id="itemsTable">
						<thead>
							<tr>
								<th style="width:30px;">#</th>
								<th>Item</th>
								<th style="width:80px;">Level</th>
								<th style="width:100px;">Type</th>
								<th style="width:60px;">Skill</th>
								<th style="width:60px;">Luck</th>
								<th style="width:60px;">Opt</th>
								<th style="width:90px;">Actions</th>
							</tr>
						</thead>
						<tbody id="itemsTableBody"></tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- ═══════════ EDIT MODAL ═══════════ -->
<div class="modal fade" id="editItemModal" tabindex="-1">
	<div class="modal-dialog">
		<div class="modal-content">
			<form method="post" id="editItemForm">
				<input type="hidden" name="item_action" value="edit">
				<input type="hidden" name="char_guid" id="editCharGuid">
				<input type="hidden" name="account_name" id="editAccountName">
				<input type="hidden" name="item_index" id="editItemIndex">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">&times;</button>
					<h4 class="modal-title"><i class="fa fa-pencil"></i> Edit Item</h4>
				</div>
				<div class="modal-body">
					<div class="form-group">
						<label>Current Item</label>
						<p id="editCurrentItemName" style="font-weight:bold;color:#337ab7;"></p>
					</div>
					<div class="form-group">
						<label>Item</label>
						<select class="form-control" name="new_item_code" id="editNewItemCode"></select>
					</div>
					<div class="row">
						<div class="col-sm-4"><div class="form-group"><label>Level (0-15)</label><input type="number" class="form-control" name="new_level" id="editNewLevel" min="0" max="15"></div></div>
						<div class="col-sm-4"><div class="form-group"><label>Durability</label><input type="number" class="form-control" name="new_durability" id="editNewDurability" min="0" max="255"></div></div>
						<div class="col-sm-4"><div class="form-group"><label>Option</label>
							<select class="form-control" name="new_option" id="editNewOption">
								<option value="0">None</option><option value="4">+4%</option><option value="8">+8%</option><option value="12">+12%</option><option value="16">+16%</option>
							</select>
						</div></div>
					</div>
					<div class="row">
						<div class="col-sm-4"><div class="form-group"><label>Skill</label>
							<select class="form-control" name="new_skill" id="editNewSkill"><option value="0">No</option><option value="1">Yes</option></select>
						</div></div>
						<div class="col-sm-4"><div class="form-group"><label>Luck</label>
							<select class="form-control" name="new_luck" id="editNewLuck"><option value="0">No</option><option value="1">Yes</option></select>
						</div></div>
						<div class="col-sm-4"><div class="form-group"><label>Ancient</label>
							<select class="form-control" name="new_ancient" id="editNewAncient">
								<option value="0">None</option>
								<option value="5">Type 1 Stat+5</option><option value="9">Type 1 Stat+10</option><option value="13">Type 1 Stat+15</option>
								<option value="6">Type 2 Stat+5</option><option value="10">Type 2 Stat+10</option><option value="14">Type 2 Stat+15</option>
								<option value="20">Type 3 Stat+5</option><option value="24">Type 3 Stat+10</option><option value="28">Type 3 Stat+15</option>
							</select>
						</div></div>
					</div>
					<div class="form-group">
						<label>Excellent Options</label>
						<div class="well well-sm" style="margin-bottom:0;">
							<label class="checkbox-inline"><input type="checkbox" class="exc-bit" data-bit="1"> Bit1 (0x01)</label>
							<label class="checkbox-inline"><input type="checkbox" class="exc-bit" data-bit="2"> Bit2 (0x02)</label>
							<label class="checkbox-inline"><input type="checkbox" class="exc-bit" data-bit="4"> Bit3 (0x04)</label>
							<label class="checkbox-inline"><input type="checkbox" class="exc-bit" data-bit="8"> Bit4 (0x08)</label>
							<label class="checkbox-inline"><input type="checkbox" class="exc-bit" data-bit="16"> Bit5 (0x10)</label>
							<label class="checkbox-inline"><input type="checkbox" class="exc-bit" data-bit="32"> Bit6 (0x20)</label>
						</div>
						<input type="hidden" name="new_exc" id="editNewExc" value="0">
						<p class="help-block" style="margin-top:4px;font-size:11px;">
							<b>Attack:</b> 0x01=ManaRecov, 0x02=HPRecov, 0x04=Speed+7, 0x08=Dmg+2%, 0x10=Dmg+Lv/20, 0x20=ExcDmg+10%<br>
							<b>Defense:</b> 0x01=Zen+30%, 0x02=DefRate+10%, 0x04=Reflect5%, 0x08=Dmg-4%, 0x10=MaxMana+4%, 0x20=MaxHP+4%
						</p>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
					<button type="submit" class="btn btn-primary">Save Changes</button>
				</div>
			</form>
		</div>
	</div>
</div>

<!-- ═══════════ ADD MODAL ═══════════ -->
<div class="modal fade" id="addItemModal" tabindex="-1">
	<div class="modal-dialog">
		<div class="modal-content">
			<form method="post" id="addItemForm">
				<input type="hidden" name="item_action" value="add">
				<input type="hidden" name="char_guid" id="addCharGuid">
				<input type="hidden" name="account_name" id="addAccountName">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">&times;</button>
					<h4 class="modal-title"><i class="fa fa-plus"></i> Add Item</h4>
				</div>
				<div class="modal-body">
					<div class="row">
						<div class="col-sm-6"><div class="form-group"><label>Inventory Type</label>
							<select class="form-control" name="add_inv_type" id="addInvType">
								<option value="1">Inventory</option><option value="0">Equipment</option><option value="2">Personal Store</option><option value="3">Warehouse</option><option value="8">Muun Inventory</option>
							</select>
						</div></div>
						<div class="col-sm-6"><div class="form-group"><label>Slot Position</label><input type="number" class="form-control" name="add_slot" id="addSlot" min="0" max="255" value="0"></div></div>
					</div>
					<div class="form-group">
						<label>Item</label>
						<select class="form-control" name="add_item_code" id="addItemCode"></select>
					</div>
					<div class="row">
						<div class="col-sm-4"><div class="form-group"><label>Level (0-15)</label><input type="number" class="form-control" name="add_level" id="addLevel" min="0" max="15" value="0"></div></div>
						<div class="col-sm-4"><div class="form-group"><label>Durability</label><input type="number" class="form-control" name="add_durability" id="addDurability" min="0" max="255" value="255"></div></div>
						<div class="col-sm-4"><div class="form-group"><label>Option</label>
							<select class="form-control" name="add_option" id="addOptionVal">
								<option value="0">None</option><option value="4">+4%</option><option value="8">+8%</option><option value="12">+12%</option><option value="16">+16%</option>
							</select>
						</div></div>
					</div>
					<div class="row">
						<div class="col-sm-4"><div class="form-group"><label>Skill</label>
							<select class="form-control" name="add_skill" id="addSkill"><option value="0">No</option><option value="1">Yes</option></select>
						</div></div>
						<div class="col-sm-4"><div class="form-group"><label>Luck</label>
							<select class="form-control" name="add_luck" id="addLuck"><option value="0">No</option><option value="1">Yes</option></select>
						</div></div>
						<div class="col-sm-4"><div class="form-group"><label>Ancient</label>
							<select class="form-control" name="add_ancient" id="addAncient">
								<option value="0">None</option>
								<option value="5">Type 1 Stat+5</option><option value="9">Type 1 Stat+10</option><option value="13">Type 1 Stat+15</option>
								<option value="6">Type 2 Stat+5</option><option value="10">Type 2 Stat+10</option><option value="14">Type 2 Stat+15</option>
								<option value="20">Type 3 Stat+5</option><option value="24">Type 3 Stat+10</option><option value="28">Type 3 Stat+15</option>
							</select>
						</div></div>
					</div>
					<div class="form-group">
						<label>Excellent Options</label>
						<div class="well well-sm" style="margin-bottom:0;">
							<label class="checkbox-inline"><input type="checkbox" class="add-exc-bit" data-bit="1"> Bit1</label>
							<label class="checkbox-inline"><input type="checkbox" class="add-exc-bit" data-bit="2"> Bit2</label>
							<label class="checkbox-inline"><input type="checkbox" class="add-exc-bit" data-bit="4"> Bit3</label>
							<label class="checkbox-inline"><input type="checkbox" class="add-exc-bit" data-bit="8"> Bit4</label>
							<label class="checkbox-inline"><input type="checkbox" class="add-exc-bit" data-bit="16"> Bit5</label>
							<label class="checkbox-inline"><input type="checkbox" class="add-exc-bit" data-bit="32"> Bit6</label>
						</div>
						<input type="hidden" name="add_exc" id="addExc" value="0">
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
					<button type="submit" class="btn btn-success">Add Item</button>
				</div>
			</form>
		</div>
	</div>
</div>

<!-- ═══════════ DELETE MODAL ═══════════ -->
<div class="modal fade" id="deleteItemModal" tabindex="-1">
	<div class="modal-dialog modal-sm">
		<div class="modal-content">
			<form method="post">
				<input type="hidden" name="item_action" value="delete">
				<input type="hidden" name="char_guid" id="deleteCharGuid">
				<input type="hidden" name="account_name" id="deleteAccountName">
				<input type="hidden" name="item_index" id="deleteItemIndex">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">&times;</button>
					<h4 class="modal-title" style="color:#c9302c;"><i class="fa fa-trash"></i> Remove Item</h4>
				</div>
				<div class="modal-body">
					<p>Are you sure you want to remove:</p>
					<p id="deleteItemName" style="font-weight:bold;"></p>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
					<button type="submit" class="btn btn-danger">Remove</button>
				</div>
			</form>
		</div>
	</div>
</div>

<script>
var baseAdminUrl = '<?php echo admincp_base("characteritems"); ?>';
var currentGuid = null;
var currentAccountName = '';
var itemSelectLoaded = false;

var EXC_ATK = ['Mana Recovery','HP Recovery','Speed +7','Dmg +2%','Dmg +Lv/20','ExcDmg +10%'];
var EXC_DEF = ['Zen +30%','DefRate +10%','Reflect 5%','Dmg -4%','MaxMana +4%','MaxHP +4%'];

function describeExcFull(val) {
	if (!val) return '';
	var atk=[],def=[];
	for (var i=0;i<6;i++) { if (val&(1<<i)) { atk.push(EXC_ATK[i]); def.push(EXC_DEF[i]); } }
	var out = '';
	if (atk.length) out += '<b>Atk:</b> '+atk.join(', ');
	if (def.length) out += (out?'<br>':'')+'<b>Def:</b> '+def.join(', ');
	return out;
}
function describeExcShort(val) {
	if (!val) return '';
	var n=0; for(var i=0;i<6;i++) if(val&(1<<i)) n++;
	return n+'x Opt';
}
function describeAncient(val) {
	if (!val) return '';
	var t=''; if(val&16) t='Type 3'; else if(val&2) t='Type 2'; else if(val&1) t='Type 1';
	var sv=val&12; var s=sv===4?'Stat+5':(sv===8?'Stat+10':(sv===12?'Stat+15':''));
	return t+(s?' '+s:'');
}
function describeOption(val) { return val ? '+'+val+'%' : '-'; }
function describeSockets(arr) {
	var a=[];
	for(var i=0;i<arr.length;i++) { if(arr[i]!==65535) a.push('S'+(i+1)+':'+arr[i]); }
	return a.length ? a.join(', ') : '';
}
function countSockets(arr) {
	var n=0; for(var i=0;i<arr.length;i++) if(arr[i]!==65535) n++; return n;
}
function yesNo(v) { return v ? '<span class="label label-success">Y</span>' : '<span class="label label-default">N</span>'; }
function escHtml(s) { return s ? String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;') : ''; }
function syncExcBits(sel, hid) { var v=0; $(sel).each(function(){if(this.checked) v|=parseInt($(this).data('bit'));}); $('#'+hid).val(v); }
function setExcBits(sel, val) { $(sel).each(function(){this.checked=!!(val&parseInt($(this).data('bit')));}); }

function getItemType(it) {
	var hasSockets = countSockets(it.sockets) > 0;
	if (it.ancient) return 'ancient';
	if (hasSockets) return 'socket';
	if (it.exc) return 'exc';
	return 'normal';
}
function typeBadgeClass(type) {
	switch(type) {
		case 'exc': return 'ci-badge-exc';
		case 'ancient': return 'ci-badge-ancient';
		case 'socket': return 'ci-badge-socket';
		default: return 'ci-badge-normal';
	}
}
function typeBadgeLabel(type) {
	switch(type) {
		case 'exc': return 'Excellent';
		case 'ancient': return 'Ancient';
		case 'socket': return 'Socket';
		default: return 'Normal';
	}
}
function buildTypeTooltip(it) {
	var type = getItemType(it);
	var lines = [];
	if (it.exc) lines.push(describeExcFull(it.exc));
	if (it.ancient) lines.push('<b>Ancient:</b> '+describeAncient(it.ancient));
	var sk = describeSockets(it.sockets);
	if (sk) lines.push('<b>Sockets:</b> '+sk);
	if (it.socketBonus && it.socketBonus !== 255) lines.push('<b>Socket Bonus:</b> '+it.socketBonus);
	return lines.length ? lines.join('<br>') : typeBadgeLabel(type);
}
function buildItemTooltip(it) {
	var lines = [];
	lines.push('<span class="ci-pop-label">Category:</span> <span class="ci-pop-val">'+escHtml(it.category)+'</span> ('+it.catIndex+':'+it.itemIndex+')');
	lines.push('<span class="ci-pop-label">Inventory:</span> <span class="ci-pop-val">'+escHtml(it.invTypeName)+'</span>');
	lines.push('<span class="ci-pop-label">Slot:</span> <span class="ci-pop-val">'+it.slot+'</span>');
	lines.push('<span class="ci-pop-label">Durability:</span> <span class="ci-pop-val">'+it.durability+'</span>');
	lines.push('<span class="ci-pop-label">Serial:</span> <span class="ci-pop-val">'+it.serial+'</span>');
	return '<div class="ci-popover-content">'+lines.join('<br>')+'</div>';
}

function _ciInit() {
	$('#searchAccountForm').on('submit', function(e) {
		e.preventDefault();
		var acc = $.trim($('#accountInput').val());
		if (!acc) return;
		$('#charactersSection, #itemsSection').hide();
		$.getJSON(baseAdminUrl + '&ajax=characters&account=' + encodeURIComponent(acc), function(r) {
			if (!r.success) { alert(r.message); return; }
			currentAccountName = r.account;
			$('#accountLabel').text(r.account + ' (ID: ' + r.accountId + ')');
			var h = '';
			if (!r.characters.length) { h = '<p class="text-muted">No characters found.</p>'; }
			else {
				h = '<table class="table table-striped table-condensed"><thead><tr><th>Name</th><th>GUID</th><th>Class</th><th>Level</th><th></th></tr></thead><tbody>';
				for (var i=0; i<r.characters.length; i++) {
					var c = r.characters[i];
					h += '<tr><td>'+escHtml(c.name)+'</td><td>'+c.guid+'</td><td>'+c['class']+'</td><td>'+c.level+'</td>';
					h += '<td><button class="btn btn-xs btn-warning" onclick="loadItems('+c.guid+',\''+escHtml(c.name)+'\')"><i class="fa fa-cubes"></i> Items</button></td></tr>';
				}
				h += '</tbody></table>';
			}
			$('#charactersList').html(h);
			$('#charactersSection').show();
		}).fail(function(){ alert('Request failed.'); });
	});
	$(document).on('change', '.exc-bit', function(){ syncExcBits('.exc-bit','editNewExc'); });
	$(document).on('change', '.add-exc-bit', function(){ syncExcBits('.add-exc-bit','addExc'); });
}

function loadItems(guid, name) {
	currentGuid = guid;
	$('#itemsSection').hide();
	$.getJSON(baseAdminUrl + '&ajax=items&guid=' + guid, function(r) {
		if (!r.success) { alert(r.message); return; }
		$('#characterLabel').text(name + ' (GUID: ' + guid + ')');
		var h = '';
		if (!r.items.length) { h = '<tr><td colspan="8" class="text-center text-muted">No items found.</td></tr>'; }
		else {
			for (var i=0; i<r.items.length; i++) {
				var it = r.items[i];
				var itype = getItemType(it);
				h += '<tr class="ci-item-row">';
				h += '<td>'+i+'</td>';
				h += '<td class="ci-item-name" data-toggle="popover" data-trigger="hover" data-placement="right" data-html="true" data-content="'+escHtml(buildItemTooltip(it))+'">';
				h += '<strong>'+escHtml(it.itemName)+'</strong></td>';
				h += '<td><span class="label label-info">+'+it.level+'</span></td>';
				h += '<td><span class="ci-type-badge '+typeBadgeClass(itype)+'" data-toggle="popover" data-trigger="hover" data-placement="top" data-html="true" data-content="'+escHtml(buildTypeTooltip(it))+'">'+typeBadgeLabel(itype)+'</span></td>';
				h += '<td>'+yesNo(it.skill)+'</td>';
				h += '<td>'+yesNo(it.luck)+'</td>';
				h += '<td>'+describeOption(it.option)+'</td>';
				h += '<td>';
				h += '<button class="btn btn-xs btn-primary" onclick=\'showEditModal('+JSON.stringify({i:i,c:it.itemCode,l:it.level,d:it.durability,s:it.skill,lk:it.luck,o:it.option,e:it.exc,a:it.ancient,n:it.itemName})+')\' title="Edit"><i class="fa fa-pencil"></i></button> ';
				h += '<button class="btn btn-xs btn-danger" onclick="showDeleteModal('+i+',\''+escHtml(it.itemName)+' +'+it.level+'\')" title="Remove"><i class="fa fa-trash"></i></button>';
				h += '</td></tr>';
			}
		}
		$('#itemsTableBody').html(h);
		$('#itemsSection').show();
		$('#itemsTable [data-toggle="popover"]').popover({container:'body'});
	}).fail(function(){ alert('Request failed.'); });
}

function loadItemSelect(cb) {
	if (itemSelectLoaded) { if(cb) cb(); return; }
	$.getJSON(baseAdminUrl + '&ajax=itemlist', function(r) {
		if (!r.success) return;
		itemSelectLoaded = true;
		var g = {};
		for (var i=0; i<r.items.length; i++) {
			var it = r.items[i];
			if (!g[it.category]) g[it.category] = [];
			g[it.category].push(it);
		}
		var h = '';
		var cats = Object.keys(g).sort();
		for (var c=0; c<cats.length; c++) {
			h += '<optgroup label="'+escHtml(cats[c])+'">';
			for (var j=0; j<g[cats[c]].length; j++) {
				var x = g[cats[c]][j];
				h += '<option value="'+x.code+'">'+escHtml(x.name)+' ('+x.catIndex+':'+x.itemIndex+')</option>';
			}
			h += '</optgroup>';
		}
		$('#editNewItemCode, #addItemCode').html(h);
		if(cb) cb();
	});
}

function showEditModal(d) {
	loadItemSelect(function() {
		$('#editCharGuid').val(currentGuid);
		$('#editAccountName').val(currentAccountName);
		$('#editItemIndex').val(d.i);
		$('#editCurrentItemName').text(d.n + ' +' + d.l);
		$('#editNewItemCode').val(d.c);
		$('#editNewLevel').val(d.l);
		$('#editNewDurability').val(d.d);
		$('#editNewSkill').val(d.s);
		$('#editNewLuck').val(d.lk);
		$('#editNewOption').val(d.o);
		$('#editNewAncient').val(d.a);
		setExcBits('.exc-bit', d.e);
		$('#editNewExc').val(d.e);
		$('#editItemModal').modal('show');
	});
}

function showDeleteModal(idx, name) {
	$('#deleteCharGuid').val(currentGuid);
	$('#deleteAccountName').val(currentAccountName);
	$('#deleteItemIndex').val(idx);
	$('#deleteItemName').text(name);
	$('#deleteItemModal').modal('show');
}

function showAddItemModal() {
	loadItemSelect(function() {
		$('#addItemForm')[0].reset();
		$('#addCharGuid').val(currentGuid);
		$('#addAccountName').val(currentAccountName);
		setExcBits('.add-exc-bit', 0);
		$('#addExc').val(0);
		$('#addItemModal').modal('show');
	});
}

(function() {
	if (typeof $ !== 'undefined') { $(function(){ _ciInit(); }); }
	else { document.addEventListener('DOMContentLoaded', function(){ if(typeof $!=='undefined') _ciInit(); }); }
})();
</script>
