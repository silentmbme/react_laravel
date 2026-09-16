<?php
namespace App\Services;
use App\Models\TaxConfiguration;

class TaxCalculationService {
    /** Returns a snapshot. No tax rule means no tax, deliberately. */
    public function calculate(string $amount, string $currency, ?string $country = null, ?string $region = null): array {
        $rule = TaxConfiguration::query()->where('status', true)->where('tax_enabled', true)
            ->where(fn($q) => $q->whereNull('effective_from')->orWhere('effective_from','<=',now()))
            ->where(fn($q) => $q->whereNull('effective_until')->orWhere('effective_until','>=',now()))
            ->where(fn($q) => $q->whereNull('country')->orWhere('country', strtoupper((string)$country)))
            ->where(fn($q) => $q->whereNull('region')->orWhere('region', $region))
            ->orderByRaw('country is null')->orderByRaw('region is null')->first();
        if (!$rule) return ['amount'=>'0.00','snapshot'=>null];
        // BC math prevents binary floating-point rounding in all authoritative calculations.
        $rate = (string)$rule->tax_rate;
        $tax = $rule->inclusive ? bcsub($amount, bcdiv($amount, bcadd('1', bcdiv($rate, '100', 8), 8), 2), 2) : bcdiv(bcmul($amount, $rate, 8), '100', 2);
        return ['amount'=>$tax, 'snapshot'=>['tax_name'=>$rule->tax_name,'tax_code'=>$rule->tax_code,'tax_rate'=>$rate,'tax_amount'=>$tax,'tax_currency'=>strtoupper($currency),'tax_country'=>$country,'tax_region'=>$region,'tax_rule_id'=>$rule->id,'inclusive'=>$rule->inclusive]];
    }
}
