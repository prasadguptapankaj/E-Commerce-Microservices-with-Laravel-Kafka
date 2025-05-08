<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Junges\Kafka\Facades\Kafka;

class OrderController extends Controller
{
    public function create(Request $request)
    {
        // Validate incoming request data
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'items' => 'required|array',
        ]);

        // Create the order in the database
        $order = Order::create([
            'user_id' => $validated['user_id'],
            'status' => 'pending',
            'items' => $validated['items'],
        ]);

        Kafka::publish()
            ->onTopic('order.created')
            ->withBody([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'product_id' => 1,
                'quantity' => 1
            ])
            ->send();

        return response()->json([
            'message' => 'Order created successfully!',
            'order' => $order,
        ]);
    }

    public function onOrderPaymentSuccess($order){
        $order->update([
            'status' => 'confirmed'
        ]);

        Kafka::publish()
            ->onTopic('order.placed')
            ->withBody([
                'order_id' => $order->id,
                'user_id' => $order->user_id
            ])
            ->send();

        return "order exected";
    }
}
