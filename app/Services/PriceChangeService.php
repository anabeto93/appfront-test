<?php

namespace App\Services;

use App\Jobs\SendPriceChangeNotification;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class PriceChangeService
{
    /**
     * Send notification about a change in price
     *
     * @param Product $product
     * @param float $oldPrice
     * @param float $newPrice
     * @return bool
     */
    public function notifyChangeInPrice(Product $product, float $oldPrice, float $newPrice): bool
    {
        if ($oldPrice == $newPrice) {
            return false;
        }

        $notificationEmail = config('price.notification_email');

        try {
            SendPriceChangeNotification::dispatch(
                $product,
                $oldPrice,
                $newPrice,
                $notificationEmail
            );

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to dispatch price change notification: ' . $e->getMessage());

            return false;
        }
    }
}
