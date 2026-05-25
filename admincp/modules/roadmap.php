<?php
/**
 * Roadmap settings
 */

echo '<h1 class="page-header">Roadmap Settings</h1>';

$roadmapPath = __PATH_CONFIGS__ . 'roadmap.json';

if(isset($_POST['roadmap_submit'])) {
    try {
        $allowedStatus = array('planned', 'in-progress', 'completed');
        $items = array();

        if(isset($_POST['roadmap_title']) && is_array($_POST['roadmap_title'])) {
            $titles = $_POST['roadmap_title'];
            $descriptions = isset($_POST['roadmap_description']) && is_array($_POST['roadmap_description']) ? $_POST['roadmap_description'] : array();
            $statuses = isset($_POST['roadmap_status']) && is_array($_POST['roadmap_status']) ? $_POST['roadmap_status'] : array();
            $etas = isset($_POST['roadmap_eta']) && is_array($_POST['roadmap_eta']) ? $_POST['roadmap_eta'] : array();

            $total = count($titles);
            for($i = 0; $i < $total; $i++) {
                $title = trim((string)$titles[$i]);
                $description = isset($descriptions[$i]) ? trim((string)$descriptions[$i]) : '';
                $status = isset($statuses[$i]) ? strtolower(trim((string)$statuses[$i])) : 'planned';
                $eta = isset($etas[$i]) ? trim((string)$etas[$i]) : '';

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
        } elseif(isset($_POST['roadmap_items_json'])) {
            // Backward compatibility for legacy JSON field submissions.
            $itemsRaw = trim((string)$_POST['roadmap_items_json']);
            if($itemsRaw !== '') {
                $decodedItems = json_decode($itemsRaw, true);
                if(!is_array($decodedItems)) {
                    throw new Exception('Roadmap JSON must be a valid JSON array.');
                }

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
            }
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
            echo '<td><strong>Roadmap Items</strong><p class="setting-description">Manage roadmap items. Empty title rows are ignored on save.</p></td>';
            echo '<td>';
                echo '<table class="table table-bordered" style="margin-bottom:10px;">';
                    echo '<thead>';
                        echo '<tr>';
                            echo '<th style="width:24%;">Title</th>';
                            echo '<th>Description</th>';
                            echo '<th style="width:16%;">Status</th>';
                            echo '<th style="width:16%;">ETA</th>';
                            echo '<th style="width:6%;">&nbsp;</th>';
                        echo '</tr>';
                    echo '</thead>';
                    echo '<tbody id="roadmap-items-body">';
                        if(count($cfg['items']) > 0) {
                            foreach($cfg['items'] as $entry) {
                                $title = isset($entry['title']) ? (string)$entry['title'] : '';
                                $description = isset($entry['description']) ? (string)$entry['description'] : '';
                                $status = isset($entry['status']) ? strtolower((string)$entry['status']) : 'planned';
                                $eta = isset($entry['eta']) ? (string)$entry['eta'] : '';
                                if(!in_array($status, array('planned', 'in-progress', 'completed'))) {
                                    $status = 'planned';
                                }

                                echo '<tr>';
                                    echo '<td><input type="text" class="form-control" name="roadmap_title[]" value="'.htmlspecialchars($title).'" maxlength="120"></td>';
                                    echo '<td><input type="text" class="form-control" name="roadmap_description[]" value="'.htmlspecialchars($description).'" maxlength="255"></td>';
                                    echo '<td>';
                                        echo '<select class="form-control" name="roadmap_status[]">';
                                            echo '<option value="planned" '.($status === 'planned' ? 'selected' : '').'>Planned</option>';
                                            echo '<option value="in-progress" '.($status === 'in-progress' ? 'selected' : '').'>In Progress</option>';
                                            echo '<option value="completed" '.($status === 'completed' ? 'selected' : '').'>Completed</option>';
                                        echo '</select>';
                                    echo '</td>';
                                    echo '<td><input type="text" class="form-control" name="roadmap_eta[]" value="'.htmlspecialchars($eta).'" maxlength="60" placeholder="e.g. Q4 2026"></td>';
                                    echo '<td><button type="button" class="btn btn-danger btn-xs roadmap-remove-item">X</button></td>';
                                echo '</tr>';
                            }
                        }
                    echo '</tbody>';
                echo '</table>';

                echo '<button type="button" id="roadmap-add-item" class="btn btn-default btn-sm">Add Item</button>';

                echo '<script type="text/javascript">';
                    echo '(function($){';
                        echo 'function escapeHtml(value){ return String(value || "").replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/\"/g, "&quot;"); }';
                        echo 'function createRow(data){';
                            echo 'var status = data.status || "planned";';
                            echo 'var row = "";';
                            echo 'row += "<tr>";';
                            echo 'row += "<td><input type=\"text\" class=\"form-control\" name=\"roadmap_title[]\" maxlength=\"120\" value=\"" + escapeHtml(data.title) + "\"></td>";';
                            echo 'row += "<td><input type=\"text\" class=\"form-control\" name=\"roadmap_description[]\" maxlength=\"255\" value=\"" + escapeHtml(data.description) + "\"></td>";';
                            echo 'row += "<td><select class=\"form-control\" name=\"roadmap_status[]\">";';
                            echo 'row += "<option value=\"planned\"" + (status === "planned" ? " selected" : "") + ">Planned</option>";';
                            echo 'row += "<option value=\"in-progress\"" + (status === "in-progress" ? " selected" : "") + ">In Progress</option>";';
                            echo 'row += "<option value=\"completed\"" + (status === "completed" ? " selected" : "") + ">Completed</option>";';
                            echo 'row += "</select></td>";';
                            echo 'row += "<td><input type=\"text\" class=\"form-control\" name=\"roadmap_eta[]\" maxlength=\"60\" placeholder=\"e.g. Q4 2026\" value=\"" + escapeHtml(data.eta) + "\"></td>";';
                            echo 'row += "<td><button type=\"button\" class=\"btn btn-danger btn-xs roadmap-remove-item\">X</button></td>";';
                            echo 'row += "</tr>";';
                            echo 'return row;';
                        echo '}';

                        echo 'var $body = $("#roadmap-items-body");';
                        echo 'if($body.children().length === 0){ $body.append(createRow({title:"",description:"",status:"planned",eta:""})); }';
                        echo '$(document).on("click", "#roadmap-add-item", function(){ $body.append(createRow({title:"",description:"",status:"planned",eta:""})); });';
                        echo '$(document).on("click", ".roadmap-remove-item", function(){';
                            echo '$(this).closest("tr").remove();';
                            echo 'if($body.children().length === 0){ $body.append(createRow({title:"",description:"",status:"planned",eta:""})); }';
                        echo '});';
                    echo '})(jQuery);';
                echo '</script>';
            echo '</td>';
        echo '</tr>';
    echo '</table>';

    echo '<button type="submit" name="roadmap_submit" value="1" class="btn btn-primary">Save Roadmap</button>';

echo '</form>';
