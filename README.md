# phpAppWithSlim
PHP REST APIs in Slim Framework  

Application Details:

I have used Slim micro framework for PHP [http://www.slimframework.com/] to develop this REST APIs

Took help of this [https://www.youtube.com/watch?v=26CRc89gN10] video to install slim.

Used Applications: 
- XAMPP v3.2.1
- XAMPP v3.2.1 - Apache [at My machine Apache running with URL : http://localhost:82/]
- XAMPP v3.2.1 - Mysql
- Sublime Editor
- Postman : To make REST call

Note: As you can see my APP URL does contain 'index.php' in it. However we can Rewrite URL shown here [http://docs.slimframework.com/routing/rewrite/]

=====================================================
Query To Create Tables

CREATE TABLE orders (
    id BIGINT NOT NULL AUTO_INCREMENT,
    email_id CHAR(100) NOT NULL, 
    status ENUM('created', 'processed', 'delivered', 'cancelled'),
    created_at Timestamp(6),
    updated_at Timestamp(6),
    PRIMARY KEY (id)
) ENGINE=MyISAM

CREATE TABLE order_items ( 
	id BIGINT NOT NULL AUTO_INCREMENT, 
	order_id BIGINT NOT NULL, 
	name CHAR(50) NOT NULL, 
	price DOUBLE NOT NULL, 
	quantity SMALLINT NOT NULL, 
	created_at Timestamp(6), 
	updated_at Timestamp(6), 
	PRIMARY KEY (id), 
	FOREIGN KEY (order_id) REFERENCES orders(id) 
	ON UPDATE CASCADE ON DELETE CASCADE 
) ENGINE=MyISAM

===========================================================
REST APIs are as follows:
1. POST /orders  - Create order in the system and persist the same in orders and order_items table.
	URL : http://localhost:82/phpAppWithSlim/index.php/
	data : {"email_id":"nakul.r.shinde@mail.com","status":"created", "name":"Pattis", "price": 12, "quantity": 2}
	Results: {"status":true,"message":"order posted successfully","order":{"email_id":"nakul.r.shinde@mail.com","status":"created","name":"Pattis","price":"12","quantity":"2","id":"19"}}


2. PUT /orders/{id} - Update order & order item attributes.
	URL : http://localhost:82/phpAppWithSlim/index.php/orders/15
	data: {"email_id":"nakul.r.shinde@mail.com","quantity":4}
	Result: {"status":true,"message":"order updated successfully","order":{"email_id":"nakul.r.shinde@mail.com","quantity":4}}

3. PUT /orders/{id}/cancel - Cancel the order.
	URL : http://localhost:82/phpAppWithSlim/index.php/orders/6/cancel

	Result: {"status":true,"message":"order cencelled successfully"}

4. PUT /orders/{id}/payment - Add payment to the order
	Note : Insufficient information for this API task. There are two alternatives to build this API
	a. May need extra column in Orders table as 'payment'
	b. We can hit update API call #2, with data as {'status':'processed'} [Note: I have considered payment is done and hence changing order status to 'processing']

5. GET /orders/{id} - Get order by id
	URL: http://localhost:82/phpAppWithSlim/index.php/orders/5

	Result: {"status":true,"message":"order fetched successfully","orders":[{"id":"5","email_id":"nakulshinde.it1@gmail.com","status":"cancelled","name":"Spacial Wada Pav","price":"15","quantity":"5"}]}

6. GET /orders/search?user_id=abc@bdc.com - Get orders by user
 Note: As we do not have any user_id specific information (we could do with one more user table in DB), I have used email_id to get user orders. 
	URL: http://localhost:82/phpAppWithSlim/index.php/orders/search?user_id=nakul.r.shinde@mail.com

	Result: {"status":true,"message":"order searched successfully","order":[{"id":"6","email_id":"nakul.r.shinde@mail.com","status":"processed","name":"Burger","price":"15","quantity":"55"},{"id":"15","email_id":"nakul.r.shinde@mail.com","status":"processed","name":"Pattis","price":"10","quantity":"1"},{"id":"16","email_id":"nakul.r.shinde@mail.com","status":"created","name":"Pattis","price":"12","quantity":"2"}]}


7. GET /orders/today - Get all orders which were created today.
	URL: http://localhost:82/phpAppWithSlim/index.php/orders/today

	Result: {"status":true,"message":"order fetched successfully","order":[{"id":"5","email_id":"nakulshinde.it1@gmail.com","status":"cancelled","name":"Spacial Wada Pav","price":"15","quantity":"5"},{"id":"6","email_id":"nakul.r.shinde@mail.com","status":"processed","name":"Burger","price":"15","quantity":"55"},{"id":"8","email_id":"shinde@gmail.com","status":"cancelled","name":"Butter","price":"68","quantity":"1"},{"id":"11","email_id":"shinde.nakul@gmail.com","status":"created","name":"Dry Fruites","price":"68","quantity":"1"},{"id":"18","email_id":"nikita.r.shinde@mail.com","status":"created","name":"Milk packet","price":"20","quantity":"2"},{"id":"17","email_id":"naksnaks@mail.com","status":"created","name":"Veg Pulav","price":"120","quantity":"2"},{"id":"15","email_id":"nakul.r.shinde@mail.com","status":"processed","name":"Pattis","price":"10","quantity":"1"},{"id":"16","email_id":"nakul.r.shinde@mail.com","status":"created","name":"Pattis","price":"12","quantity":"2"}]}

