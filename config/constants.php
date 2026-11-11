<?php
// Application constants
class AppConfig {
    public const ALLOWED_INTEREST_RATES = [10, 18, 25, 28, 30, 35, 40];

    /**
     * Weekly loan rates (TOTAL interest on the principal), effective 1 November 2026.
     * Keyed by number of weeks. Fixed by loan length - staff cannot override.
     */
    public const WEEKLY_RATES = [
        1 => 15.0,
        2 => 20.0,
        3 => 25.0,
        4 => 30.0,
    ];

    public const CURRENCY_SYMBOL = 'K';
    public const CURRENCY_CODE = 'ZMW';
    public const CURRENCY_NAME = 'Zambian Kwacha';
}
