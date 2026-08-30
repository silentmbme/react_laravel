<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\AuthorEarning;
use App\Models\Order;
use App\Services\MarketplaceSettings;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    public function customerStatement(Request $request, MarketplaceSettings $settings)
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'paid')
            ->with('items')
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('paid_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('paid_at', '<=', $to))
            ->latest('paid_at')
            ->get();

        $documents = $orders->map(fn (Order $order) => $this->order($order, $settings));

        return response()->json([
            'summary' => [
                'orders' => $orders->count(),
                'total' => (float) $orders->sum('total'),
                'currency' => $orders->first()?->currency ?? 'USD',
            ],
            // Kept for existing clients; each entry is a financial document in the customer ledger.
            'orders' => $documents,
            'entries' => $documents->map(fn (array $order) => [
                'id' => $order['id'],
                'date' => $order['date'],
                'type' => 'purchase',
                'document_number' => $order['invoice_number'],
                'description' => collect($order['items'])->pluck('name')->join(', '),
                'provider' => $order['provider'],
                'amount' => $order['total'],
                'currency' => $order['currency'],
            ]),
        ]);
    }

    public function invoice(Request $request, Order $order, MarketplaceSettings $settings)
    {
        abort_unless($order->user_id === $request->user()->id && $order->status === 'paid', 404);
        $order->loadMissing('items', 'user');

        return response()->json(['invoice' => [
            'number' => $this->invoiceNumber($order, $settings),
            'issued_at' => $order->paid_at,
            'seller' => $settings->group('billing'),
            'buyer' => ['name' => $order->user->name, 'email' => $order->user->email],
            'order' => $this->order($order, $settings),
        ]]);
    }

    public function authorStatement(Request $request)
    {
        $rows = AuthorEarning::where('author_id', $request->user()->id)->with('item')->latest('available_at')->get();
        return response()->json(['summary' => ['gross' => (float) $rows->sum('gross_amount'), 'fees' => (float) $rows->sum('platform_fee_amount'), 'net' => (float) $rows->sum('net_amount'), 'currency' => $rows->first()?->currency ?? 'USD'], 'entries' => $rows->map(fn ($row) => ['id' => $row->id, 'date' => $row->available_at, 'product' => $row->item?->product_name, 'license' => $row->item?->license_name, 'gross' => (float) $row->gross_amount, 'fee' => (float) $row->platform_fee_amount, 'net' => (float) $row->net_amount, 'tier' => $row->commission_tier_name, 'status' => $row->status])]);
    }

    public function adminOverview(Request $request)
    {
        abort_unless($request->user()?->role === 'superadmin', 403, 'Superadmin access required.');
        $orders = Order::where('status', 'paid');
        $earnings = AuthorEarning::query();
        return response()->json(['summary' => ['paid_orders' => (clone $orders)->count(), 'gross_sales' => (float) (clone $orders)->sum('total'), 'platform_revenue' => (float) (clone $earnings)->sum('platform_fee_amount'), 'author_payable' => (float) (clone $earnings)->sum('net_amount'), 'currency' => 'USD'], 'recent_orders' => (clone $orders)->with('user', 'items')->latest('paid_at')->take(20)->get()->map(fn ($order) => $this->order($order, app(MarketplaceSettings::class)))]);
    }

    private function invoiceNumber(Order $order, MarketplaceSettings $settings): string
    {
        $prefix = $settings->group('billing')['invoice_prefix'] ?? 'MP';
        return strtoupper($prefix) . '-' . str_pad((string) $order->id, 8, '0', STR_PAD_LEFT);
    }

    private function order(Order $order, MarketplaceSettings $settings): array
    {
        return [
            'id' => $order->public_id,
            'invoice_number' => $this->invoiceNumber($order, $settings),
            'date' => $order->paid_at,
            'provider' => $order->provider,
            'currency' => $order->currency,
            'subtotal' => (float) $order->subtotal,
            'buyer_fee_total' => (float) $order->buyer_fee_total,
            'handling_fee' => (float) $order->handling_fee,
            'tax' => (float) $order->tax,
            'total' => (float) $order->total,
            'items' => $order->items->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->product_name,
                'license' => $item->license_name,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'unit_buyer_fee' => (float) $item->unit_buyer_fee,
                'buyer_fee_total' => (float) $item->buyer_fee_total,
                'total' => (float) $item->total,
            ]),
        ];
    }
}