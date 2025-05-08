<?php

namespace App\Console\Commands;

use App\Http\Controllers\OrderController;
use App\Models\Order;
use App\Models\User;
use App\Notifications\UserRegisteredNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Junges\Kafka\Facades\Kafka;

class KafkaConsume extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kafka:consume';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consumes Kafka messages and handles notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Listening for Kafka messages...');

        Kafka::consumer()
            ->subscribe(['inventory.reserved', 'inventory.failed', 'payment.success', 'payment.fail'])
            ->withHandler(function(\Junges\Kafka\Contracts\ConsumerMessage $message, \Junges\Kafka\Contracts\MessageConsumer $consumer) {
                $data = $message->getBody();
                $order = Order::find($data['order_id']);

                if (!$order) return;
                $this->info('Topic name: ' . $message->getTopicName());
                match ($message->getTopicName()) {
                    'inventory.reserved' => Kafka::publish()->onTopic('payment.success')->withBody([
                        'order_id' => $order->id,
                        'amount' => 100.0 // Example static price
                    ])->send(),

                    'inventory.failed' => $order->update(['status' => 'cancelled']),

                    'payment.success' => Kafka::publish()
                        ->onTopic('order.placed')
                        ->withBody([
                            'order_id' => $order->id,
                            'user_id' => $order->user_id
                        ])
                        ->send(),
                    
                    'payment.failed' => $order->update(['status' => 'cancelled']),
                };
            })
            ->build()
            ->consume();
    }
}
