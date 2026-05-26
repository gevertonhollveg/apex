<?php
/**
 * Donation total cache updater
 */

// File Name
$file_name = basename(__FILE__);

$database = (config('SQL_USE_2_DB', true) ? Connection::Database('Me_MuOnline') : Connection::Database('MuOnline'));

$totalAmount = 0;
$totalTransactions = 0;

$summary = $database->query_fetch_single("SELECT COALESCE(SUM(payment_amount), 0) AS total_amount, COUNT(*) AS total_transactions FROM ".WEBENGINE_PAYPAL_TRANSACTIONS." WHERE transaction_status = 1");
if(is_array($summary)) {
    $totalAmount = isset($summary['total_amount']) ? (float)$summary['total_amount'] : 0;
    $totalTransactions = isset($summary['total_transactions']) ? (int)$summary['total_transactions'] : 0;
}

$cacheData = array(
    'total_amount' => round($totalAmount, 2),
    'total_transactions' => $totalTransactions,
    'updated_at' => time(),
);

updateCacheFile('donation_total.cache', encodeCache($cacheData));

// UPDATE CRON
updateCronLastRun($file_name);
