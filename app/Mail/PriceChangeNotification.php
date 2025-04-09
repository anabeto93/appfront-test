<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use App\Models\Product;

class PriceChangeNotification extends Mailable implements ShouldQueue
{
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(public Product $product, public float $oldPrice, public float $newPrice)
    {
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Product Price Change Notification')
                    ->view('emails.price-change')->with([
                        'product' => $this->product,
                        'oldPrice' => $this->oldPrice,
                        'newPrice' => $this->newPrice,
                    ]);
    }
}
