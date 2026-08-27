<?php
if (!function_exists('get_currency_base_rates')) {
    function get_currency_base_rates()
    {
        $ci =& get_instance();

        // ✅ Load cache driver
        $ci->load->driver('cache', ['adapter' => 'file']);

        if (!$ci->cache->get('currency_base_rates')) {
            $rates = $ci->db->select('code, xrate')->get('tbl_currencies')->result();
            $rate_map = [];
            foreach ($rates as $xrate) {
                $rate_map[strtoupper($xrate->code)] = (float) $xrate->xrate;
            }

            $ci->cache->save('currency_base_rates', $rate_map, 3600); // Cache for 1 hour
        } else {
            $rate_map = $ci->cache->get('currency_base_rates');
        }

        return $rate_map;
    }
}

if (!function_exists('convert_with_manual_rate')) {
    /**
     * Convert amount using manual xrate entered by user,
     * auto-detect multiply/divide direction based on base rates in tbl_currencies
     *
     * @param float $amount
     * @param string $from_currency
     * @param string $to_currency
     * @param float $manual_rate
     * @return float
     * @throws Exception
     */
    function convert_with_manual_rate($amount, $from_currency, $to_currency, $manual_rate)
    {
        $from = strtoupper(trim($from_currency));
        $to = strtoupper(trim($to_currency));

        if ($from === $to) {
            return round($amount, 2);
        }

        if ($manual_rate == 0) {
            throw new Exception('Exchange xrate cannot be zero');
        }

        $rates = get_currency_base_rates();

        if (!isset($rates[$from]) || !isset($rates[$to])) {
            throw new Exception("Base xrate not found for currency: $from or $to");
        }

        $from_rate = $rates[$from];
        $to_rate = $rates[$to];

        if ($to_rate > $from_rate) {
            // multiply
            return round($amount * $manual_rate, 2);
        } else {
            // divide
            return round($amount / $manual_rate, 2);
        }
    }
}
