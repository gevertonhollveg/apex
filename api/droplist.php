<?php
/**
 * Droplist API
 * Reads bag XML files from modules/bags and returns normalized JSON.
 */

if (!defined('access')) define('access', 'api');
if (!@include_once(rtrim(str_replace('\\', '/', dirname(__DIR__)), '/') . '/includes/webengine.php')) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('error' => 'Could not load WebEngine.'));
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$bagsDir = __PATH_MODULES__ . 'bags/';

function toIntOrNull($value)
{
    if ($value === null) {
        return null;
    }

    $value = trim((string)$value);
    if ($value === '' || strtoupper($value) === 'MAX') {
        return null;
    }

    if (!is_numeric($value)) {
        return null;
    }

    return (int)$value;
}

function loadItemNameMap($itemListPath)
{
    $map = array();

    if (!is_file($itemListPath)) {
        return $map;
    }

    $reader = new XMLReader();
    if (!$reader->open($itemListPath, null, LIBXML_NONET)) {
        return $map;
    }

    $currentCategory = null;

    while ($reader->read()) {
        if ($reader->nodeType === XMLReader::ELEMENT) {
            if ($reader->name === 'Category') {
                $catIndex = $reader->getAttribute('Index');
                $currentCategory = is_numeric($catIndex) ? (string)((int)$catIndex) : null;
            } elseif ($reader->name === 'Item' && $currentCategory !== null) {
                $itemIndex = $reader->getAttribute('Index');
                $itemName = trim((string)$reader->getAttribute('Name'));

                if (is_numeric($itemIndex) && $itemName !== '') {
                    $map[$currentCategory . ':' . (string)((int)$itemIndex)] = $itemName;
                }
            }
        } elseif ($reader->nodeType === XMLReader::END_ELEMENT && $reader->name === 'Category') {
            $currentCategory = null;
        }
    }

    $reader->close();
    return $map;
}

function describeOptionValue($optionRaw)
{
    $option = toIntOrNull($optionRaw);
    if ($option === null || $option === 0) {
        return null;
    }

    if ($option === -1) {
        return 'Opt random';
    }

    if ($option > 0 && $option <= 7) {
        return 'Opt +' . ($option * 4);
    }

    return 'Opt ' . $option;
}

function describeExcValue($excRaw)
{
    $excRaw = trim((string)$excRaw);
    if ($excRaw === '' || $excRaw === '-1') {
        return null;
    }

    if (strpos($excRaw, '-3;') === 0) {
        $parts = explode(';', $excRaw);
        $count = isset($parts[1]) && is_numeric($parts[1]) ? (int)$parts[1] : null;
        return $count !== null ? ('Exc random x' . $count) : 'Exc random';
    }

    if (strpos($excRaw, '-2') === 0) {
        return 'Exc random';
    }

    $parts = array_filter(array_map('trim', explode(';', $excRaw)), 'strlen');
    if (!count($parts)) {
        return null;
    }

    return 'Exc fixed x' . count($parts);
}

function describeSocketValue($socketRaw)
{
    $socket = toIntOrNull($socketRaw);
    if ($socket === null || $socket === 0) {
        return null;
    }

    if ($socket === -2) {
        return 'Socket random';
    }

    if ($socket > 0) {
        return 'Socket up to ' . $socket;
    }

    return null;
}

function describeElementalValue($elementRaw)
{
    $element = toIntOrNull($elementRaw);
    if ($element === null || $element === 0) {
        return null;
    }

    if ($element === -1) {
        return 'Element random';
    }

    $map = array(
        1 => 'Element Fire',
        2 => 'Element Water',
        3 => 'Element Earth',
        4 => 'Element Wind',
        5 => 'Element Darkness',
    );

    return isset($map[$element]) ? $map[$element] : ('Element ' . $element);
}

function buildItemOptionSummary($attributes)
{
    $parts = array();

    $skill = toIntOrNull($attributes['Skill'] ?? null);
    if ($skill !== null && $skill !== 0) {
        $parts[] = $skill === -1 ? 'Skill random' : 'Skill';
    }

    $luck = toIntOrNull($attributes['Luck'] ?? null);
    if ($luck !== null && $luck !== 0) {
        $parts[] = $luck === -1 ? 'Luck random' : 'Luck';
    }

    $optionDesc = describeOptionValue($attributes['Option'] ?? null);
    if ($optionDesc !== null) {
        $parts[] = $optionDesc;
    }

    $excDesc = describeExcValue($attributes['Exc'] ?? null);
    if ($excDesc !== null) {
        $parts[] = $excDesc;
    }

    $setItem = toIntOrNull($attributes['SetItem'] ?? null);
    if ($setItem !== null && $setItem > 0) {
        $parts[] = 'Ancient';
    }

    $socketDesc = describeSocketValue($attributes['SocketCount'] ?? null);
    if ($socketDesc !== null) {
        $parts[] = $socketDesc;
    }

    $elementDesc = describeElementalValue($attributes['ElementalItem'] ?? null);
    if ($elementDesc !== null) {
        $parts[] = $elementDesc;
    }

    if (!count($parts)) {
        return 'Normal';
    }

    return implode(' | ', $parts);
}

function buildBinaryOrRandomState($value)
{
    if ($value === null) {
        return 'no';
    }

    if ((int)$value === -1) {
        return 'random';
    }

    return ((int)$value > 0) ? 'yes' : 'no';
}

function buildExcState($excRaw)
{
    $excRaw = trim((string)$excRaw);
    if ($excRaw === '' || $excRaw === '-1') {
        return 'no';
    }

    if (strpos($excRaw, '-2') === 0 || strpos($excRaw, '-3;') === 0) {
        return 'random';
    }

    return 'yes';
}

function buildSocketMaxValue($socketRaw)
{
    $socket = toIntOrNull($socketRaw);
    if ($socket === null || $socket === 0) {
        return 0;
    }

    if ($socket === -2) {
        return 'random';
    }

    if ($socket < 0) {
        return 0;
    }

    if ($socket > 5) {
        return 5;
    }

    return $socket;
}

if (!isset($_GET['bag'])) {
    http_response_code(400);
    echo json_encode(array('error' => 'Missing bag parameter.'));
    exit;
}

$bagFile = basename((string)$_GET['bag']);
if (strtolower(pathinfo($bagFile, PATHINFO_EXTENSION)) !== 'xml') {
    http_response_code(400);
    echo json_encode(array('error' => 'Invalid bag file extension.'));
    exit;
}

$filePath = $bagsDir . $bagFile;
if (!is_file($filePath)) {
    http_response_code(404);
    echo json_encode(array('error' => 'Bag file not found.'));
    exit;
}

libxml_use_internal_errors(true);
$xml = simplexml_load_file($filePath, 'SimpleXMLElement', LIBXML_NONET);
if (!$xml) {
    http_response_code(422);
    echo json_encode(array('error' => 'Invalid XML file.'));
    exit;
}

$itemNameMap = loadItemNameMap($bagsDir . 'ItemList.xml');

$results = array();

$dropNodes = $xml->xpath('//Drop');
if ($dropNodes === false) {
    $dropNodes = array();
}

$dropStats = array();
$totalDropRate = 0;

foreach ($dropNodes as $dropIndex => $dropNode) {
    $dropAttr = $dropNode->attributes();
    $dropItems = $dropNode->xpath('./Item');
    if ($dropItems === false) {
        $dropItems = array();
    }

    $itemCount = count($dropItems);
    $rate = toIntOrNull($dropAttr['Rate'] ?? null);
    $type = toIntOrNull($dropAttr['Type'] ?? null);
    $count = toIntOrNull($dropAttr['Count'] ?? null);

    if ($count === null || $count < 1) {
        $count = 1;
    }

    if ($rate !== null && $rate > 0 && $itemCount > 0) {
        $totalDropRate += $rate;
    }

    $dropStats[$dropIndex] = array(
        'rate' => $rate,
        'type' => $type,
        'count' => $count,
        'item_count' => $itemCount,
    );
}

foreach ($dropNodes as $dropIndex => $dropNode) {
    $dropItems = $dropNode->xpath('./Item');
    if ($dropItems === false) {
        $dropItems = array();
    }

    $stats = isset($dropStats[$dropIndex]) ? $dropStats[$dropIndex] : array();
    $dropRate = $stats['rate'] ?? null;
    $dropType = $stats['type'] ?? 0;
    $dropCount = $stats['count'] ?? 1;
    $dropItemCount = $stats['item_count'] ?? count($dropItems);

    $dropPercent = null;
    if ($dropRate !== null && $dropRate > 0 && $totalDropRate > 0) {
        $dropPercent = ((float)$dropRate / (float)$totalDropRate) * 100.0;
    }

    foreach ($dropItems as $node) {
        $attr = $node->attributes();

        $cat = is_numeric((string)($attr['Cat'] ?? '')) ? (string)((int)$attr['Cat']) : '';
        $index = is_numeric((string)($attr['Index'] ?? '')) ? (string)((int)$attr['Index']) : '';
        $lookupKey = $cat !== '' && $index !== '' ? ($cat . ':' . $index) : '';
        $mappedName = ($lookupKey !== '' && isset($itemNameMap[$lookupKey])) ? $itemNameMap[$lookupKey] : '';
        $xmlName = trim((string)($attr['Name'] ?? ''));
        $displayName = $mappedName !== '' ? $mappedName : ($xmlName !== '' ? $xmlName : ('Item ' . $lookupKey));

        $perItemPercent = null;
        if ($dropPercent !== null) {
            if ($dropType === 1) {
                $perItemPercent = $dropPercent;
            } else {
                $denominator = max(1, (int)$dropItemCount);
                $perItemPercent = $dropPercent * ((float)$dropCount / (float)$denominator);
                if ($perItemPercent > 100) {
                    $perItemPercent = 100;
                }
            }
        }

        $skill = toIntOrNull($attr['Skill'] ?? null);
        $luck = toIntOrNull($attr['Luck'] ?? null);
        $set = toIntOrNull($attr['SetItem'] ?? null);
        $excRaw = trim((string)($attr['Exc'] ?? ''));
        $skillState = buildBinaryOrRandomState($skill);
        $luckState = buildBinaryOrRandomState($luck);
        $excState = buildExcState($excRaw);
        $ancientState = ($set !== null && $set !== 0) ? 'yes' : 'no';
        $socketMax = buildSocketMaxValue($attr['SocketCount'] ?? null);

        $results[] = array(
            'type' => 'item',
            'name' => $displayName,
            'cat' => $cat,
            'index' => $index,
            'cat_index' => $lookupKey,
            'drop_rate' => $dropRate,
            'drop_type' => $dropType,
            'drop_count' => $dropCount,
            'drop_percent' => $perItemPercent !== null ? round($perItemPercent, 4) : null,
            'min_level' => toIntOrNull($attr['ItemMinLevel'] ?? null),
            'max_level' => toIntOrNull($attr['ItemMaxLevel'] ?? null),
            'socket_max' => $socketMax,
            'skill_state' => $skillState,
            'luck_state' => $luckState,
            'exc_state' => $excState,
            'anc_state' => $ancientState,
            'excellent' => ($excState !== 'no'),
            'ancient' => ($ancientState === 'yes'),
            'luck' => ($luckState !== 'no'),
            'skill' => ($skillState !== 'no'),
            'option_summary' => buildItemOptionSummary($attr),
            'raw' => trim((string)$node[0])
        );
    }
}

$ruudNodes = $xml->xpath('//*[local-name()="Ruud"]');
if ($ruudNodes === false) $ruudNodes = array();

foreach ($ruudNodes as $node) {
    $attr = $node->attributes();
    $min = (string)($attr['MinValue'] ?? $attr['minvalue'] ?? '');
    $max = (string)($attr['MaxValue'] ?? $attr['maxvalue'] ?? '');

    $results[] = array(
        'type' => 'ruud',
        'name' => 'Ruud',
        'drop_rate' => isset($attr['GainRate']) ? (int)$attr['GainRate'] : null,
        'drop_percent' => isset($attr['GainRate']) ? round(((int)$attr['GainRate']) / 100, 4) : null,
        'min' => ($min === '' || strtoupper($min) === 'MAX') ? null : (int)$min,
        'max' => ($max === '' || strtoupper($max) === 'MAX') ? null : (int)$max,
        'max_level' => null,
        'socket_max' => null,
        'skill_state' => null,
        'luck_state' => null,
        'exc_state' => null,
        'anc_state' => null,
        'option_summary' => 'Ruud reward',
        'excellent' => false,
        'ancient' => false,
        'luck' => false,
        'skill' => false,
        'raw' => trim((string)$node[0])
    );
}

$coinNodes = $xml->xpath('//*[local-name()="AddCoin"]');
if ($coinNodes === false) $coinNodes = array();

foreach ($coinNodes as $node) {
    $attr = $node->attributes();
    $value = (string)($attr['CoinValue'] ?? '');

    if ($value !== '' && (int)$value > 0) {
        $results[] = array(
            'type' => 'coin',
            'name' => 'Coin',
            'drop_rate' => null,
            'drop_percent' => null,
            'min' => (int)$value,
            'max' => (int)$value,
            'max_level' => null,
            'socket_max' => null,
            'skill_state' => null,
            'luck_state' => null,
            'exc_state' => null,
            'anc_state' => null,
            'option_summary' => 'Coin reward',
            'excellent' => false,
            'ancient' => false,
            'luck' => false,
            'skill' => false,
            'raw' => trim((string)$node[0])
        );
    }
}

$bagConfigNodes = $xml->xpath('//*[local-name()="BagConfig"]');
if (is_array($bagConfigNodes) && isset($bagConfigNodes[0])) {
    $bagAttr = $bagConfigNodes[0]->attributes();
    if (isset($bagAttr['MoneyDrop']) && (int)$bagAttr['MoneyDrop'] > 0) {
        $money = (int)$bagAttr['MoneyDrop'];
        $results[] = array(
            'type' => 'zen',
            'name' => 'Zen',
            'drop_rate' => null,
            'drop_percent' => null,
            'min' => $money,
            'max' => $money,
            'max_level' => null,
            'socket_max' => null,
            'skill_state' => null,
            'luck_state' => null,
            'exc_state' => null,
            'anc_state' => null,
            'option_summary' => 'Zen reward',
            'excellent' => false,
            'ancient' => false,
            'luck' => false,
            'skill' => false,
            'raw' => ''
        );
    }
}

echo json_encode(array(
    'bag' => $bagFile,
    'total' => count($results),
    'items' => $results
));
exit;
