🛒 E-Commerce Microservices with Laravel & Kafka
This project demonstrates a microservices-based architecture for an e-commerce system using Laravel and Apache Kafka for asynchronous, event-driven communication between services.

📦 Microservices Involved
Order Service (Producer & Consumer)

Inventory Service (Consumer & Producer)

Payment Service (Consumer & Producer)

Shipping Service (Consumer & Producer)

Notification Service (Global Consumer)

🧩 Workflow Overview
1. User Places Order
css
Copy
Edit
User → Order Service: placeOrder(product_id, quantity, user_id)
Order is created in DB with status = pending.

Order Service publishes event:

json
Copy
Edit
topic: order.placed
payload: { "order_id", "product_id", "quantity", "user_id" }
2. Inventory Service Handles Stock Reservation
Listens to: order.placed

Locks product row using SELECT ... FOR UPDATE.

Checks if stock >= quantity.

✅ If Stock Available:

Publishes inventory.reserved event:

json
Copy
Edit
topic: inventory.reserved
payload: { "order_id", "product_id", "quantity" }
❌ If Out of Stock:

Publishes inventory.failed event:

json
Copy
Edit
topic: inventory.failed
payload: { "order_id", "product_id", "quantity" }
3. Order Service Handles Inventory Response
Listens to: inventory.reserved / inventory.failed

On inventory.reserved:

Updates order status to inventory_reserved.

Publishes:

json
Copy
Edit
topic: payment.initiated
payload: { "order_id", "amount", "user_id" }
On inventory.failed:

Updates order status to cancelled.

Publishes:

json
Copy
Edit
topic: notification.send
payload: { "order_id", "message": "Order cancelled due to insufficient stock." }
4. Payment Service Initiates Charge
Listens to: payment.initiated

Attempts charge via payment gateway.

✅ If Successful:

json
Copy
Edit
topic: payment.success
payload: { "order_id", "txn_id", "amount" }
❌ If Failed:

json
Copy
Edit
topic: payment.failed
payload: { "order_id" }
5. Order Service Handles Payment Response
Listens to: payment.success / payment.failed

On payment.success:

Updates order status to paid.

Publishes:

json
Copy
Edit
topic: order.shipped
payload: { "order_id", "user_id" }
On payment.failed:

Updates order status to payment_failed.

Publishes:

json
Copy
Edit
topic: notification.send
payload: { "order_id", "message": "Payment failed." }
6. Shipping Service Ships the Order
Listens to: order.shipped

Assigns tracking number.

Publishes:

json
Copy
Edit
topic: order.tracking.updated
payload: { "order_id", "tracking_id" }
7. Notification Service Sends Updates
Listens to: All relevant topics

Sends appropriate emails/SMS/push notifications for:

Order placed

Inventory reserved or failed

Payment success or failure

Order shipped

Tracking updates

🧰 Technologies Used
Laravel (Microservice implementation)

Apache Kafka (Event streaming and communication)

MySQL/PostgreSQL (Service-specific databases)

Docker (Optional) for local development and Kafka setup

🚀 Setup & Run
Clone the repositories of individual services.

Ensure Kafka is running locally or remotely.

Set Kafka configurations (KAFKA_BROKER, topics) in each service .env.

Run Laravel queues and Kafka consumers for each service:

bash
Copy
Edit
php artisan queue:work
php artisan kafka:consume
📬 Kafka Topics Summary
Topic	Producer	Consumer(s)
order.placed	Order Service	Inventory Service
inventory.reserved	Inventory Service	Order Service
inventory.failed	Inventory Service	Order Service
payment.initiated	Order Service	Payment Service
payment.success	Payment Service	Order Service
payment.failed	Payment Service	Order Service
order.shipped	Order Service	Shipping Service
order.tracking.updated	Shipping Service	Notification Service
notification.send	Order/Payment	Notification Service

🧪 Testing
Simulate user actions using Postman or frontend UI.

Monitor logs for Kafka message flow.

Test resilience by bringing down a service and checking reprocessing behavior.