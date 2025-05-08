<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\UserRegisteredNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Junges\Kafka\Facades\Kafka;

class KafkaConsumer extends Command
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
        $this->info('Listening for Kafka in inventory messages...');

        Kafka::consumer()
            ->subscribe(['order.created'])
            ->withHandler(function(\Junges\Kafka\Contracts\ConsumerMessage $message, \Junges\Kafka\Contracts\MessageConsumer $consumer) {
                $data = $message->getBody();
                $this->info('Message received: ' . json_encode($data));
                $this->info('Topic name: ' . $message->getTopicName());

                switch ($message->getTopicName()) {
                    case 'order.created':
                        if(empty($data['product_id'])) break;
                        $stock = DB::table('products')->where('id', $data['product_id'])->lockForUpdate()->first();
                        
                        if ($stock && $stock->quantity >= $data['quantity']) {
                            Kafka::publish()->onTopic('inventory.reserved')->withBody([
                                'order_id' => $data['order_id'],
                                'product_id' => $data['product_id'],
                                'quantity' => $data['quantity']
                            ])->send();
                        } else {
                            Kafka::publish()->onTopic('inventory.failed')->withBody([
                                'order_id' => $data['order_id'],
                                'product_id' => $data['product_id'],
                                'quantity' => $data['quantity']
                            ])->send();
                        }
                        break;
                }
            })
            ->build()
            ->consume();
    }
}
