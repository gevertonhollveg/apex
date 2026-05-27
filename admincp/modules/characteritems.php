<?php
/**
 * Character Item Inventory Manager
 * AdminCP Module
 * 
 * Manages character items from the character_item_inventory table.
 * Item code formula: (512 * categoryId) + itemIndex (maps to ItemList.xml)
 */

// ─── Item name loader (reuses logic from api/droplist.php) ───
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

// Build flat item list for <select> dropdown (add item)
function buildItemSelectList($itemMap) {
	$groups = array();
	foreach ($itemMap as $code => $info) {
		$cat = $info['category'];
		if (!isset($groups[$cat])) $groups[$cat] = array();
		$groups[$cat][] = array('code' => $code, 'name' => $info['name'], 'catIndex' => $info['catIndex'], 'itemIndex' => $info['itemIndex']);
	}
	ksort($groups);
	return $groups;
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

// Parse a single item line: {field0;field1;...;fieldN}
function parseItemFields($raw) {
	$raw = trim($raw, " \t\n\r\0\x0B{},");
	if ($raw === '') return null;
	return explode(';', $raw);
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

			// ── Get characters for an account ──
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
							'name' => $c[_CLMN_CHR_NAME_],
							'guid' => $c[_CLMN_CHR_GUID_],
							'class' => $c[_CLMN_CHR_CLASS_],
							'level' => $c[_CLMN_CHR_LVL_],
						);
					}
				}
				$response = array('success' => true, 'account' => $accName, 'accountId' => $accId, 'characters' => $charList);
				break;

			// ── Get items for a character GUID ──
			case 'items':
				if (!isset($_GET['guid']) || !is_numeric($_GET['guid'])) throw new Exception('Character GUID required.');
				$guid = (int)$_GET['guid'];

				$row = $dB->query_fetch_single("SELECT InventoryData FROM character_item_inventory WHERE GUID = ?", array($guid));
				if (!$row || !isset($row['InventoryData']) || $row['InventoryData'] === null) {
					$response = array('success' => true, 'guid' => $guid, 'items' => array(), 'raw' => '');
					break;
				}
				$rawData = $row['InventoryData'];

				// Parse items
				preg_match_all('/\{([^}]+)\}/', $rawData, $matches);
				$items = array();
				if (!empty($matches[1])) {
					foreach ($matches[1] as $itemStr) {
						$fields = explode(';', $itemStr);
						if (count($fields) < 7) continue;
						$invType = (int)$fields[0];
						$slot = (int)$fields[1];
						$itemCode = (int)$fields[2];
						$serial = isset($fields[4]) ? $fields[4] : '0';
						$level = isset($fields[6]) ? (int)$fields[6] : 0;
						$durability = isset($fields[7]) ? (int)$fields[7] : 0;

						$itemName = 'Unknown Item';
						$catName = '';
						$catIndex = -1;
						$itemIndex = -1;
						if (isset($itemMap[$itemCode])) {
							$itemName = $itemMap[$itemCode]['name'];
							$catName = $itemMap[$itemCode]['category'];
							$catIndex = $itemMap[$itemCode]['catIndex'];
							$itemIndex = $itemMap[$itemCode]['itemIndex'];
						}

						$items[] = array(
							'invType' => $invType,
							'invTypeName' => getInventoryTypeName($invType),
							'slot' => $slot,
							'itemCode' => $itemCode,
							'itemName' => $itemName,
							'category' => $catName,
							'catIndex' => $catIndex,
							'itemIndex' => $itemIndex,
							'serial' => $serial,
							'level' => $level,
							'durability' => $durability,
							'raw' => $itemStr,
						);
					}
				}

				$response = array('success' => true, 'guid' => $guid, 'items' => $items);
				break;

			// ── Item list for select dropdown (JSON) ──
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

// ═══════════ POST ACTIONS (edit/delete/add) ═══════════
if (isset($_POST['item_action'])) {
	try {
		$action = $_POST['item_action'];
		if (!isset($_POST['char_guid']) || !is_numeric($_POST['char_guid'])) throw new Exception('Invalid character GUID.');
		$guid = (int)$_POST['char_guid'];

		// Check account online status
		if (isset($_POST['account_name']) && check_value($_POST['account_name'])) {
			if ($common->accountOnline($_POST['account_name'])) {
				throw new Exception('The account is currently online. Please wait until the player disconnects.');
			}
		}

		// Load current inventory
		$row = $dB->query_fetch_single("SELECT InventoryData FROM character_item_inventory WHERE GUID = ?", array($guid));
		$rawData = ($row && isset($row['InventoryData'])) ? $row['InventoryData'] : '';

		// Parse all items
		preg_match_all('/\{([^}]+)\}/', $rawData, $matches);
		$allItems = !empty($matches[1]) ? $matches[1] : array();

		switch ($action) {
			case 'edit':
				if (!isset($_POST['item_index']) || !is_numeric($_POST['item_index'])) throw new Exception('Invalid item index.');
				$idx = (int)$_POST['item_index'];
				if (!isset($allItems[$idx])) throw new Exception('Item not found at position ' . $idx . '.');

				$fields = explode(';', $allItems[$idx]);
				if (count($fields) < 7) throw new Exception('Invalid item data.');

				// Update item code
				if (isset($_POST['new_item_code']) && is_numeric($_POST['new_item_code'])) {
					$fields[2] = (int)$_POST['new_item_code'];
				}
				// Update level
				if (isset($_POST['new_level']) && is_numeric($_POST['new_level'])) {
					$newLevel = (int)$_POST['new_level'];
					if ($newLevel < 0 || $newLevel > 15) throw new Exception('Level must be between 0 and 15.');
					$fields[6] = $newLevel;
				}

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

				$addInvType = (int)$_POST['add_inv_type'];
				$addSlot = (int)$_POST['add_slot'];
				$addItemCode = (int)$_POST['add_item_code'];
				$addLevel = (int)$_POST['add_level'];
				$addDurability = isset($_POST['add_durability']) && is_numeric($_POST['add_durability']) ? (int)$_POST['add_durability'] : 255;

				if ($addLevel < 0 || $addLevel > 15) throw new Exception('Level must be between 0 and 15.');

				// Generate a basic serial (timestamp-based)
				$addSerial = mt_rand(10000, 99999);

				// Build new item with default fields
				// Fields: invType;slot;itemCode;0;serial;0;level;durability;0;0;0;0;0;0;0;0;65535;65535;65535;65535;65535;255;0;0;0;0;0;0;0;0;0;255;255;255;255;255;255;0;0;254;254;254;254
				$newItemFields = array(
					$addInvType, $addSlot, $addItemCode, 0, $addSerial, 0,
					$addLevel, $addDurability, 0, 0, 0, 0, 0, 0, 0, 0,
					65535, 65535, 65535, 65535, 65535, 255,
					0, 0, 0, 0, 0, 0, 0, 0, 0,
					255, 255, 255, 255, 255, 255,
					0, 0, 254, 254, 254, 254
				);
				$allItems[] = implode(';', $newItemFields);
				message('success', 'Item added successfully.');
				break;

			default:
				throw new Exception('Invalid action.');
		}

		// Rebuild data string
		$newData = '';
		foreach ($allItems as $i => $item) {
			if ($i > 0) $newData .= ",\n";
			$newData .= '{' . $item . '}';
		}

		// Check if row exists
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
						<label for="accountInput" class="sr-only">Account Name</label>
						<input type="text" class="form-control" id="accountInput" placeholder="Account name" style="width:250px;">
					</div>
					<button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> Search</button>
				</form>
			</div>
		</div>
	</div>
</div>

<!-- ═══════════ CHARACTERS LIST ═══════════ -->
<div class="row" id="charactersSection" style="display:none;">
	<div class="col-md-12">
		<div class="panel panel-info">
			<div class="panel-heading"><i class="fa fa-user"></i> Characters for: <strong id="accountLabel"></strong></div>
			<div class="panel-body" id="charactersList">
			</div>
		</div>
	</div>
</div>

<!-- ═══════════ ITEMS TABLE ═══════════ -->
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
								<th>#</th>
								<th>Inventory</th>
								<th>Slot</th>
								<th>Item</th>
								<th>Category</th>
								<th>Level</th>
								<th>Durability</th>
								<th>Serial</th>
								<th style="width:160px;">Actions</th>
							</tr>
						</thead>
						<tbody id="itemsTableBody">
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- ═══════════ EDIT MODAL ═══════════ -->
<div class="modal fade" id="editItemModal" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<form method="post" id="editItemForm">
				<input type="hidden" name="item_action" value="edit">
				<input type="hidden" name="char_guid" id="editCharGuid">
				<input type="hidden" name="account_name" id="editAccountName">
				<input type="hidden" name="item_index" id="editItemIndex">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
					<h4 class="modal-title">Edit Item</h4>
				</div>
				<div class="modal-body">
					<div class="form-group">
						<label>Current Item</label>
						<p id="editCurrentItemName" class="form-control-static" style="font-weight:bold;"></p>
					</div>
					<div class="form-group">
						<label for="editNewItemCode">Item</label>
						<select class="form-control" name="new_item_code" id="editNewItemCode"></select>
					</div>
					<div class="form-group">
						<label for="editNewLevel">Level (0-15)</label>
						<input type="number" class="form-control" name="new_level" id="editNewLevel" min="0" max="15" value="0">
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

<!-- ═══════════ ADD ITEM MODAL ═══════════ -->
<div class="modal fade" id="addItemModal" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<form method="post" id="addItemForm">
				<input type="hidden" name="item_action" value="add">
				<input type="hidden" name="char_guid" id="addCharGuid">
				<input type="hidden" name="account_name" id="addAccountName">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
					<h4 class="modal-title">Add Item</h4>
				</div>
				<div class="modal-body">
					<div class="form-group">
						<label for="addInvType">Inventory Type</label>
						<select class="form-control" name="add_inv_type" id="addInvType">
							<option value="1">Inventory</option>
							<option value="0">Equipment</option>
							<option value="2">Personal Store</option>
							<option value="3">Warehouse</option>
							<option value="8">Muun Inventory</option>
						</select>
					</div>
					<div class="form-group">
						<label for="addSlot">Slot Position</label>
						<input type="number" class="form-control" name="add_slot" id="addSlot" min="0" max="255" value="0">
					</div>
					<div class="form-group">
						<label for="addItemCode">Item</label>
						<select class="form-control" name="add_item_code" id="addItemCode"></select>
					</div>
					<div class="form-group">
						<label for="addLevel">Level (0-15)</label>
						<input type="number" class="form-control" name="add_level" id="addLevel" min="0" max="15" value="0">
					</div>
					<div class="form-group">
						<label for="addDurability">Durability</label>
						<input type="number" class="form-control" name="add_durability" id="addDurability" min="0" max="255" value="255">
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

<!-- ═══════════ DELETE CONFIRM MODAL ═══════════ -->
<div class="modal fade" id="deleteItemModal" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-sm" role="document">
		<div class="modal-content">
			<form method="post" id="deleteItemForm">
				<input type="hidden" name="item_action" value="delete">
				<input type="hidden" name="char_guid" id="deleteCharGuid">
				<input type="hidden" name="account_name" id="deleteAccountName">
				<input type="hidden" name="item_index" id="deleteItemIndex">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
					<h4 class="modal-title" style="color:#c9302c;">Remove Item</h4>
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
var itemSelectData = [];

function _ciInit() {
// ── Search account ──
$('#searchAccountForm').on('submit', function(e) {
	e.preventDefault();
	var account = $.trim($('#accountInput').val());
	if (!account) return;

	$('#charactersSection').hide();
	$('#itemsSection').hide();

	$.getJSON(baseAdminUrl + '&ajax=characters&account=' + encodeURIComponent(account), function(res) {
		if (!res.success) {
			alert(res.message || 'Error');
			return;
		}
		currentAccountName = res.account;
		$('#accountLabel').text(res.account + ' (ID: ' + res.accountId + ')');

		var html = '';
		if (res.characters.length === 0) {
			html = '<p class="text-muted">No characters found for this account.</p>';
		} else {
			html = '<table class="table table-striped table-condensed"><thead><tr><th>Name</th><th>GUID</th><th>Class</th><th>Level</th><th></th></tr></thead><tbody>';
			for (var i = 0; i < res.characters.length; i++) {
				var c = res.characters[i];
				html += '<tr>';
				html += '<td>' + escapeHtml(c.name) + '</td>';
				html += '<td>' + c.guid + '</td>';
				html += '<td>' + c['class'] + '</td>';
				html += '<td>' + c.level + '</td>';
				html += '<td><button class="btn btn-xs btn-warning" onclick="loadItems(' + c.guid + ', \'' + escapeHtml(c.name) + '\')"><i class="fa fa-cubes"></i> View Items</button></td>';
				html += '</tr>';
			}
			html += '</tbody></table>';
		}
		$('#charactersList').html(html);
		$('#charactersSection').show();
	}).fail(function() {
		alert('Request failed. Check connection.');
	});
});
} // end _ciInit

// ── Load items for character ──
function loadItems(guid, charName) {
	currentGuid = guid;
	$('#itemsSection').hide();

	$.getJSON(baseAdminUrl + '&ajax=items&guid=' + guid, function(res) {
		if (!res.success) {
			alert(res.message || 'Error');
			return;
		}

		$('#characterLabel').text(charName + ' (GUID: ' + guid + ')');

		var html = '';
		if (res.items.length === 0) {
			html = '<tr><td colspan="9" class="text-center text-muted">No items found.</td></tr>';
		} else {
			for (var i = 0; i < res.items.length; i++) {
				var it = res.items[i];
				html += '<tr>';
				html += '<td>' + i + '</td>';
				html += '<td><span class="label label-' + invTypeLabel(it.invType) + '">' + escapeHtml(it.invTypeName) + '</span></td>';
				html += '<td>' + it.slot + '</td>';
				html += '<td><strong>' + escapeHtml(it.itemName) + '</strong><br><small class="text-muted">Code: ' + it.itemCode + ' (Cat ' + it.catIndex + ', Idx ' + it.itemIndex + ')</small></td>';
				html += '<td>' + escapeHtml(it.category) + '</td>';
				html += '<td><span class="label label-info">+' + it.level + '</span></td>';
				html += '<td>' + it.durability + '</td>';
				html += '<td><small>' + it.serial + '</small></td>';
				html += '<td>';
				html += '<button class="btn btn-xs btn-primary" onclick="showEditModal(' + i + ', ' + it.itemCode + ', ' + it.level + ', \'' + escapeHtml(it.itemName) + '\')"><i class="fa fa-pencil"></i> Edit</button> ';
				html += '<button class="btn btn-xs btn-danger" onclick="showDeleteModal(' + i + ', \'' + escapeHtml(it.itemName) + ' +' + it.level + '\')"><i class="fa fa-trash"></i></button>';
				html += '</td>';
				html += '</tr>';
			}
		}
		$('#itemsTableBody').html(html);
		$('#itemsSection').show();
	}).fail(function() {
		alert('Request failed.');
	});
}

// ── Load item list for select dropdowns ──
function loadItemSelect(callback) {
	if (itemSelectLoaded) {
		if (callback) callback();
		return;
	}
	$.getJSON(baseAdminUrl + '&ajax=itemlist', function(res) {
		if (!res.success) return;
		itemSelectData = res.items;
		itemSelectLoaded = true;

		// Group by category
		var groups = {};
		for (var i = 0; i < res.items.length; i++) {
			var it = res.items[i];
			if (!groups[it.category]) groups[it.category] = [];
			groups[it.category].push(it);
		}

		var html = '';
		var cats = Object.keys(groups).sort();
		for (var c = 0; c < cats.length; c++) {
			html += '<optgroup label="' + escapeHtml(cats[c]) + '">';
			var items = groups[cats[c]];
			for (var j = 0; j < items.length; j++) {
				html += '<option value="' + items[j].code + '">' + escapeHtml(items[j].name) + ' (' + items[j].catIndex + ':' + items[j].itemIndex + ')</option>';
			}
			html += '</optgroup>';
		}
		$('#editNewItemCode').html(html);
		$('#addItemCode').html(html);
		if (callback) callback();
	});
}

// ── Edit modal ──
function showEditModal(index, itemCode, level, itemName) {
	loadItemSelect(function() {
		$('#editCharGuid').val(currentGuid);
		$('#editAccountName').val(currentAccountName);
		$('#editItemIndex').val(index);
		$('#editCurrentItemName').text(itemName + ' +' + level);
		$('#editNewItemCode').val(itemCode);
		$('#editNewLevel').val(level);
		$('#editItemModal').modal('show');
	});
}

// ── Delete modal ──
function showDeleteModal(index, itemName) {
	$('#deleteCharGuid').val(currentGuid);
	$('#deleteAccountName').val(currentAccountName);
	$('#deleteItemIndex').val(index);
	$('#deleteItemName').text(itemName);
	$('#deleteItemModal').modal('show');
}

// ── Add item modal ──
function showAddItemModal() {
	loadItemSelect(function() {
		$('#addCharGuid').val(currentGuid);
		$('#addAccountName').val(currentAccountName);
		$('#addItemModal').modal('show');
	});
}

// ── Helpers ──
function invTypeLabel(type) {
	switch(type) {
		case 0: return 'primary';
		case 1: return 'default';
		case 8: return 'warning';
		default: return 'info';
	}
}

function escapeHtml(str) {
	if (!str) return '';
	return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Wait for jQuery to be available then initialize
(function waitForJQuery() {
	if (typeof $ !== 'undefined') {
		$(document).ready(function() { _ciInit(); });
	} else {
		document.addEventListener('DOMContentLoaded', function() {
			if (typeof $ !== 'undefined') { _ciInit(); }
		});
	}
})();
</script>
