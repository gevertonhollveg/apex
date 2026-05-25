<?php
/**
 * Roadmap module
 */

$title = lang('module_titles_txt_31', true);
if(!check_value($title) || $title === 'ERROR') {
    $title = 'Roadmap';
}

echo '<div class="page-title"><span>'.$title.'</span></div>';

$roadmap = loadConfig('roadmap');
$roadmapActive = true;
$roadmapItems = array();

if(is_array($roadmap)) {
    if(array_key_exists('active', $roadmap)) {
        $roadmapActive = (bool)$roadmap['active'];
    }

    if(isset($roadmap['items']) && is_array($roadmap['items'])) {
        $roadmapItems = $roadmap['items'];
    }
}

echo '<div class="roadmap-module">';

if(!$roadmapActive) {
    echo '<div class="roadmap-empty">Roadmap is temporarily unavailable.</div>';
} elseif(!count($roadmapItems)) {
    echo '<div class="roadmap-empty">No roadmap entries available yet.</div>';
} else {
    echo '<div class="roadmap-list">';

    foreach($roadmapItems as $item) {
        if(!is_array($item)) continue;

        $itemTitle = isset($item['title']) ? trim($item['title']) : '';
        $itemDescription = isset($item['description']) ? trim($item['description']) : '';
        $itemStatus = isset($item['status']) ? strtolower(trim($item['status'])) : 'planned';
        $itemEta = isset($item['eta']) ? trim($item['eta']) : '';

        if($itemTitle === '') continue;

        if(!in_array($itemStatus, array('planned', 'in-progress', 'completed'))) {
            $itemStatus = 'planned';
        }

        $statusLabel = 'Planned';
        if($itemStatus === 'in-progress') $statusLabel = 'In Progress';
        if($itemStatus === 'completed') $statusLabel = 'Completed';

        echo '<article class="roadmap-item">';
            echo '<div class="roadmap-item-header">';
                echo '<h3 class="roadmap-item-title">'.htmlspecialchars($itemTitle).'</h3>';
                echo '<span class="roadmap-status roadmap-status-'.$itemStatus.'">'.$statusLabel.'</span>';
            echo '</div>';

            if($itemDescription !== '') {
                echo '<p class="roadmap-item-description">'.nl2br(htmlspecialchars($itemDescription)).'</p>';
            }

            if($itemEta !== '') {
                echo '<div class="roadmap-item-eta">ETA: '.htmlspecialchars($itemEta).'</div>';
            }
        echo '</article>';
    }

    echo '</div>';
}

echo '</div>';
