<?php
require_once __DIR__ . '/../config/constants.php';

function formatCurrency(float $amount): string {
    return AppConfig::CURRENCY_SYMBOL . ' ' . number_format($amount, 2);
}

// Backwards-compatible wrapper for older calls
function formatKwacha(float $amount): string {
    return formatCurrency($amount);
}
