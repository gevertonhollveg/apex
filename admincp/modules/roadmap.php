<?php
/**
 * Roadmap settings
 */

echo '<h1 class="page-header">Roadmap Settings</h1>';

$roadmapPath = __PATH_CONFIGS__ . 'roadmap.json';

if(isset($_POST['roadmap_submit'])) {
    try {
        if(!isset($_POST['roadmap_items_json'])) {
            throw new Exception('Roadmap JSON is required.');
        }

        $itemsRaw = trim((string)$_POST['roadmap_items_json']);
        $decodedItems = json_decode($itemsRaw, true);

        if($itemsRaw === '' || !is_array($decodedItems)) {
            throw new Exception('Roadmap JSON must be a valid JSON array.');
        }

        $allowedStatus = array('planned', 'in-progress', 'completed');
        $items = array();

        foreach($decodedItems as $entry) {
            if(!is_array($entry)) continue;

            $title = isset($entry['title']) ? trim((string)$entry['title']) : '';
            $description = isset($entry['description']) ? trim((string)$entry['description']) : '';
            $status = isset($entry['status']) ? strtolower(trim((string)$entry['status'])) : 'planned';
            $eta = isset($entry['eta']) ? trim((string)$entry['eta']) : '';

            if($title === '') continue;
            if(!in_array($status, $allowedStatus)) {
                $status = 'planned';
            }

            $items[] = array(
                'title' => $title,
                'description' => $description,
                'status' => $status,
                'eta' => $eta,
            );
        }

        $roadmapData = array(
            'active' => (isset($_POST['roadmap_active']) && $_POST['roadmap_active'] == '1'),
            'items' => $items,
        );

        $json = json_encode($roadmapData, JSON_PRETTY_PRINT);
        if($json === false) {
            throw new Exception('Could not encode roadmap data.');
        }

        $file = fopen($roadmapPath, 'w');
        if(!$file) {
            throw new Exception('Could not open roadmap configuration file.');
        }

        fwrite($file, $json);
        fclose($file);

        message('success', 'Roadmap successfully saved!');
    } catch(Exception $ex) {
        message('error', $ex->getMessage());
    }
}

$cfg = loadConfig('roadmap');
if(!is_array($cfg)) {
    $cfg = array(
        'active' => true,
        'items' => array(),
    );
}

if(!isset($cfg['items']) || !is_array($cfg['items'])) {
    $cfg['items'] = array();
}

echo '<form action="" method="post">';
    echo '<table class="table table-striped table-bordered table-hover">';
        echo '<tr>';
            echo '<td style="width:30%"><strong>Roadmap Status</strong><p class="setting-description">Enable or disable roadmap display on website.</p></td>';
            echo '<td>';
                echo '<label class="radio-inline"><input type="radio" name="roadmap_active" value="1" '.($cfg['active'] ? 'checked' : '').'> Enabled</label>';
                echo '<label class="radio-inline"><input type="radio" name="roadmap_active" value="0" '.(!$cfg['active'] ? 'checked' : '').'> Disabled</label>';
            echo '</td>';
        echo '</tr>';
        echo '<tr>';
            echo '<td><strong>Roadmap Items JSON</strong><p class="setting-description">Use an array of objects with fields: title, description, status (planned|in-progress|completed), eta.</p></td>';
            echo '<td>';
                echo '<textarea name="roadmap_items_json" class="form-control" rows="16" style="font-family: monospace;">'.htmlspecialchars(json_encode($cfg['items'], JSON_PRETTY_PRINT)).'</textarea>';
            echo '</td>';
        echo '</tr>';
    echo '</table>';

    echo '<button type="submit" name="roadmap_submit" value="1" class="btn btn-primary">Save Roadmap</button>';

echo '</form>';
