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
$roadmapPercentOnly = false;

$donationTotalAmount = 0;
$donationTotalCache = loadCache('donation_total.cache');
if(is_array($donationTotalCache) && isset($donationTotalCache['total_amount'])) {
    $donationTotalAmount = (float)$donationTotalCache['total_amount'];
    $donationTotalUpdatedAt = isset($donationTotalCache['updated_at']) ? (int)$donationTotalCache['updated_at'] : 0;
} else {
    $donationTotalUpdatedAt = 0;
}

if(is_array($roadmap)) {
    if(array_key_exists('active', $roadmap)) {
        $roadmapActive = (bool)$roadmap['active'];
    }

    if(array_key_exists('progress_percent_only', $roadmap)) {
        $roadmapPercentOnly = (bool)$roadmap['progress_percent_only'];
    }

    if(isset($roadmap['items']) && is_array($roadmap['items'])) {
        $roadmapItems = $roadmap['items'];
    }
}

echo '<div class="roadmap-module">';

// Show last update time for donation progress
if(isset($donationTotalUpdatedAt) && $donationTotalUpdatedAt > 0) {
    $diff = time() - $donationTotalUpdatedAt;
    if($diff < 60) {
        $ago = $diff.'s ago';
    } elseif($diff < 3600) {
        $ago = floor($diff/60).'m ago';
    } elseif($diff < 86400) {
        $ago = floor($diff/3600).'h ago';
    } else {
        $ago = floor($diff/86400).'d ago';
    }
    echo '<div class="roadmap-cache-info" style="margin-bottom:10px;color:var(--text-muted);font-size:12px;">Donation progress last updated: <strong>'.$ago.'</strong></div>';
}

if(!$roadmapActive) {
    echo '<div class="roadmap-empty">Roadmap is temporarily unavailable.</div>';
} elseif(!count($roadmapItems)) {
    echo '<div class="roadmap-empty">No roadmap entries available yet.</div>';
} else {
    echo '<div class="roadmap-list roadmap-timeline">';

    $timelineIndex = 0;

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

        $statusIcon = 'fa-lightbulb-o';
        if($itemStatus === 'in-progress') $statusIcon = 'fa-cogs';
        if($itemStatus === 'completed') $statusIcon = 'fa-check-circle';

        $timelineIndex++;

        echo '<article class="roadmap-item roadmap-item-'.$itemStatus.'">';
            echo '<div class="roadmap-node" aria-hidden="true">';
                echo '<i class="fa '.$statusIcon.'"></i>';
            echo '</div>';
            echo '<div class="roadmap-item-header">';
                echo '<div class="roadmap-item-title-wrap">';
                    echo '<span class="roadmap-step">Step '.(int)$timelineIndex.'</span>';
                    echo '<h3 class="roadmap-item-title">'.htmlspecialchars($itemTitle).'</h3>';
                echo '</div>';
                echo '<span class="roadmap-status roadmap-status-'.$itemStatus.'"><i class="fa '.$statusIcon.'"></i> '.$statusLabel.'</span>';
            echo '</div>';

            if($itemDescription !== '') {
                echo '<p class="roadmap-item-description">'.nl2br(htmlspecialchars($itemDescription)).'</p>';
            }

            echo '<div class="roadmap-item-meta">';
                if($itemEta !== '') {
                    echo '<div class="roadmap-item-eta"><i class="fa fa-calendar"></i> ETA: '.htmlspecialchars($itemEta).'</div>';
                }
            echo '</div>';

            if($itemDonateGoal > 0) {
                echo '<div class="roadmap-progress-meta">';
                    if($roadmapPercentOnly) {
                        echo '<span><i class="fa fa-pie-chart"></i> Progress: '.number_format($progressPercent, 2).'%</span>';
                    } else {
                        echo '<span><i class="fa fa-flag-checkered"></i> Donation Goal: $'.number_format($itemDonateGoal, 2, '.', ',').'</span>';
                        echo '<span><i class="fa fa-line-chart"></i> Raised: $'.number_format($donationTotalAmount, 2, '.', ',').' ('.number_format($progressPercent, 2).'%)</span>';
                    }
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
