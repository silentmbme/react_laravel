<?php
namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\License;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductLicense;
use App\Models\PurchaseVerification;
use App\Models\User;
use App\Services\OrderFulfillmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderFulfillmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_order_is_fulfilled_once_and_creates_download_access(): void
    {
        $buyer = User::factory()->create();
        $author = User::factory()->create();
        $category = Category::create(['name' => 'Themes', 'slug' => 'themes']);
        $license = License::create(['name' => 'Regular', 'slug' => 'regular', 'description' => 'One end product']);
        $product = Product::create([
            'author_id' => $author->id,
            'category_id' => $category->id,
            'name' => 'Demo theme',
            'slug' => 'demo-theme',
            'short_description' => 'Demo',
            'description' => 'Demo',
            'thumbnail' => 'thumbnail.jpg',
            'file' => 'user_'.$author->id.'/product.zip',
            'status' => 'published',
            'support_enabled' => true,
        ]);
        $productLicense = ProductLicense::create(['product_id' => $product->id, 'license_id' => $license->id, 'price' => 20]);
        CartItem::create(['user_id' => $buyer->id, 'product_id' => $product->id, 'product_license_id' => $productLicense->id, 'quantity' => 2]);
        $order = Order::create(['public_id' => (string) Str::uuid(), 'user_id' => $buyer->id, 'provider' => 'stripe', 'status' => 'pending', 'currency' => 'USD', 'subtotal' => 40, 'tax' => 0, 'total' => 40]);
        $item = OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'product_license_id' => $productLicense->id, 'product_name' => $product->name, 'license_name' => $license->name, 'quantity' => 2, 'unit_price' => 20, 'total' => 40]);

        app(OrderFulfillmentService::class)->markPaid($order, 'cs_test_123');
        app(OrderFulfillmentService::class)->markPaid($order, 'cs_test_123');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'paid', 'provider_reference' => 'cs_test_123']);
        $this->assertDatabaseCount('download_entitlements', 2);
        $this->assertDatabaseCount('purchase_verifications', 2);
        $this->assertDatabaseMissing('cart_items', ['user_id' => $buyer->id, 'product_id' => $product->id]);
        $this->assertSame(2, (int) $product->fresh()->sales);
        $this->assertDatabaseHas('download_entitlements', ['user_id' => $buyer->id, 'order_item_id' => $item->id, 'unit_number' => 1]);
        $this->assertDatabaseHas('download_entitlements', ['user_id' => $buyer->id, 'order_item_id' => $item->id, 'unit_number' => 2]);

        $verifications = PurchaseVerification::orderBy('unit_number')->get();
        $this->assertCount(2, $verifications);
        $this->assertNotSame($verifications[0]->purchaseCode(), $verifications[1]->purchaseCode());
        $verification = $verifications->first();
        $this->assertNotEmpty($verification->purchaseCode());
        $this->assertTrue($verification->support_until->isFuture());

        $this->postJson('/api/purchase-verifications', ['purchase_code' => $verification->purchaseCode()])
            ->assertOk()
            ->assertJsonPath('valid', true)
            ->assertJsonPath('purchase.product_id', $product->id)
            ->assertJsonPath('purchase.support_included', true)
            ->assertJsonPath('purchase.supported', true);
    }
}
