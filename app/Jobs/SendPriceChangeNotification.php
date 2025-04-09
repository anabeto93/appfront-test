<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use App\Mail\PriceChangeNotification;
use App\Models\Product;

class SendPriceChangeNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        public Product $product,
        public float $oldPrice,
        public float $newPrice,
        public string $email
    ){
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
            Mail::to($this->email)
                ->send(new PriceChangeNotification(
                    $this->product,
                    $this->oldPrice,
                    $this->newPrice
                ));

    }
}
