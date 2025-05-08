<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Junges\Kafka\Facades\Kafka;

class CreateKafkaTopic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kafka:create-topic {topic}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a Kafka topic';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $topic = $this->argument('topic');

        try {
            // Assuming Kafka::producer() creates the topic if it doesn't exist
            Kafka::publish()
                ->onTopic($topic)
                ->send('Kafka topic created successfully.');

            $this->info("Topic '{$topic}' has been created successfully.");
        } catch (\Exception $e) {
            $this->error("Error creating topic: " . $e->getMessage());
        }
    }
}
