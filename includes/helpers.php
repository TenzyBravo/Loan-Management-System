<?php
require_once __DIR__ . '/../config/constants.php';

function formatKwacha(float $amount): string {
    return AppConfig::CURRENCY_SYMBOL . ' ' . number_format($amount, 2);
}
