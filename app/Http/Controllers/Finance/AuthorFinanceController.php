<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinancialTransaction;
use App\Models\Payout;
use App\Services\PayoutService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthorFinanceController extends Controller
{
    private function author(Request $request): void { abort_unless($request->user()?->role === 'author', 403, 'Author access required.'); }

    public function show(Request $request)
    {
        $this->author($request); $user = $request->user();
        $eligible = FinancialTransaction::query()->where('seller_id', $user->id)->where('type', 'SELLER_EARNING')->where('status', 'completed')->whereDoesntHave('payoutItem');
        return response()->json(['profile' => $this->profile($user), 'payout_summary' => ['available' => (float) (clone $eligible)->sum('net_amount'), 'currency' => (clone $eligible)->value('currency') ?? 'USD', 'minimum' => 50, 'can_request' => $user->tax_form_status === 'verified' && filled($user->payout_method) && filled($user->payout_destination)]]);
    }

    public function update(Request $request)
    {
        $this->author($request);
        $data = $request->validate(['payout_method' => ['required', Rule::in(['bank_transfer', 'paypal'])], 'payout_destination' => ['required', 'string', 'min:4', 'max:255'], 'tax_residency_country' => ['required', 'string', 'size:2'], 'tax_form_type' => ['required', Rule::in(['W-8BEN', 'W-9', 'local'])], 'tax_attested' => ['required', 'accepted']]);
        $user = $request->user(); $destination = trim($data['payout_destination']);
        $user->update(['payout_method' => $data['payout_method'], 'payout_destination' => $destination, 'payout_destination_last4' => substr(preg_replace('/\s+/', '', $destination), -4), 'tax_residency_country' => strtoupper($data['tax_residency_country']), 'tax_form_type' => $data['tax_form_type'], 'tax_form_status' => $user->tax_form_status === 'verified' ? 'verified' : 'pending_review', 'tax_form_completed_at' => now()]);
        return response()->json(['message' => 'Payout and tax details saved for review.', 'profile' => $this->profile($user->fresh())]);
    }

    public function requestPayout(Request $request, PayoutService $payouts)
    {
        $this->author($request); $user = $request->user();
        if ($user->tax_form_status !== 'verified') throw ValidationException::withMessages(['tax_form' => ['Your tax form must be verified before a payout can be requested.']]);
        if (!filled($user->payout_method) || !filled($user->payout_destination)) throw ValidationException::withMessages(['payout' => ['Set up a payout destination before requesting a payout.']]);
        $currency = strtoupper($request->validate(['currency' => ['nullable', 'string', 'size:3']])['currency'] ?? 'USD');
        $available = FinancialTransaction::query()->where('seller_id', $user->id)->where('currency', $currency)->where('type', 'SELLER_EARNING')->where('status', 'completed')->whereDoesntHave('payoutItem')->sum('net_amount');
        if ((float) $available < 50) throw ValidationException::withMessages(['payout' => ['At least USD 50.00 in available earnings is required for payout.']]);
        return response()->json(['payout' => $payouts->request($user->id, $currency, $user->payout_method)], 201);
    }

    public function payouts(Request $request) { $this->author($request); return Payout::query()->where('seller_id', $request->user()->id)->latest()->paginate(20); }

    private function profile($user): array { return ['payout_method' => $user->payout_method, 'payout_destination_last4' => $user->payout_destination_last4, 'tax_residency_country' => $user->tax_residency_country, 'tax_form_type' => $user->tax_form_type, 'tax_form_status' => $user->tax_form_status ?? 'unverified', 'tax_form_completed_at' => $user->tax_form_completed_at]; }
}