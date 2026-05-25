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

$donationTotalAmount = 0;
$donationTotalCache = loadCache('donation_total.cache');
if(is_array($donationTotalCache) && isset($donationTotalCache['total_amount'])) {
    $donationTotalAmount = (float)$donationTotalCache['total_amount'];
}

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
        $itemDonateGoal = isset($item['donate_goal']) ? (float)$item['donate_goal'] : 0;

        if($itemTitle === '') continue;

        if(!in_array($itemStatus, array('planned', 'in-progress', 'completed'))) {
            $itemStatus = 'planned';
        }

        if($itemDonateGoal < 0) {
            $itemDonateGoal = 0;
        }

        $progressPercent = 0;
        if($itemDonateGoal > 0) {
            $progressPercent = ($donationTotalAmount / $itemDonateGoal) * 100;
            if($progressPercent > 100) {
                $progressPercent = 100;
            }
        }

        if($itemDonateGoal > 0 && $progressPercent >= 100) {
            $itemStatus = 'completed';
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

            if($itemDonateGoal > 0) {
                echo '<div class="roadmap-progress-meta">';
                    echo '<span>Donation Goal: $'.number_format($itemDonateGoal, 2, '.', ',').'</span>';
                    echo '<span>Raised: $'.number_format($donationTotalAmount, 2, '.', ',').' ('.number_format($progressPercent, 2).'%)</span>';
                echo '</div>';
                echo '<div class="roadmap-progress">';
                    echo '<div class="roadmap-progress-bar" style="width:'.number_format($progressPercent, 2, '.', '').'%;"></div>';
                echo '</div>';
            }
        echo '</article>';
    }

    echo '</div>';
}

echo '</div>';
