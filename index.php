<?php
require 'Slim/Slim.php';
\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

$app->post('/orders', 'postOrder');
$app->put('/orders/:id', 'updateOrder');
$app->put('/orders/:id/cancel', 'cancelOrder');
$app->get('/orders/search', 'searchOrder');
$app->get('/orders/today', 'getOrdersToday');
$app->get('/orders/:id', 'getOrders');

$app->run();	

function getOrdersToday() {
	$request = \Slim\Slim::getInstance()->request();
    $body = $request->getBody();

    $sql = "SELECT orders.id, orders.email_id, orders.status, order_items.name, order_items.price, order_items.quantity FROM orders, order_items WHERE orders.id=order_items.order_id and DATE(orders.created_at) = CURDATE()";
    try {
        $db = getConnection();
        $stmt = $db->query($sql);
        $orders = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;

        echo json_encode(array(
            "status" => true,
            "message" => "order fetched successfully",
            "order" => $orders
        ));
    } catch(PDOException $e) {
        echo json_encode(array(
            "status" => false,
            "message" => $e->getMessage()
        ));
    }
}

function searchOrder(){
	$request = \Slim\Slim::getInstance()->request();
    
    $user_id = $request->params('user_id');
    
    $sql = "SELECT orders.id, orders.email_id, orders.status, order_items.name, order_items.price, order_items.quantity FROM orders, order_items WHERE orders.id=order_items.order_id and orders.email_id='$user_id' ";
    try {
        $db = getConnection();
        $stmt = $db->query($sql);
        $orders = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
         echo json_encode(array(
            "status" => true,
            "message" => "order searched successfully",
            "order" => $orders
        ));
    } catch(PDOException $e) {
        echo json_encode(array(
            "status" => false,
            "message" => $e->getMessage()
        ));
    }
}

function getOrders($id) {
	$request = \Slim\Slim::getInstance()->request();
    $body = $request->getBody();

    $sql = "SELECT orders.id, orders.email_id, orders.status, order_items.name, order_items.price, order_items.quantity FROM orders INNER JOIN order_items ON orders.id=order_items.order_id WHERE orders.id=$id";
    try {
        $db = getConnection();
        $stmt = $db->query($sql);
        $orders = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
    
        echo json_encode(array(
            "status" => true,
            "message" => "order fetched successfully",
            "orders" => $orders
        ));
    } catch(PDOException $e) {
        echo json_encode(array(
            "status" => false,
            "message" => $e->getMessage()
        ));
    }
}

function cancelOrder($id) {
	$request = \Slim\Slim::getInstance()->request();
    $body = $request->getBody();

	try{
    	$db = getConnection();
        $timestamp = date('Y-m-d G:i:s');
		$ordersSql = "UPDATE orders SET status='cancelled', updated_at='$timestamp' WHERE id='$id'";

        $ordersStmt = $db->prepare($ordersSql);
        $ordersStmt->execute();
        echo json_encode(array(
            "status" => true,
            "message" => "order cencelled successfully"
        ));
    } catch(PDOException $e) {
        echo json_encode(array(
            "status" => false,
            "message" => $e->getMessage()
        ));
    }
}

function updateOrder($id) {
    $request = \Slim\Slim::getInstance()->request();
    $body = $request->getBody();
    $order = json_decode($body);

    $valid = json_decode(validateOrder($order));
    if (!$valid->status) {
        echo json_encode($valid);
        return;
    }

    try {
    	$db = getConnection();
        $timestamp = date('Y-m-d G:i:s');
        
    	$ordersVar    	= array("email_id", "status");
	    $orderItemsVar  = array("name", "price", "quantity");

		$ordersSetVar = '';
		foreach($ordersVar as $var){
			try{
				if ($order->$var) {
					$ordersSetVar = $ordersSetVar. ''. $var.'= "'.$order->$var.'",';
				}
			}catch(Exception $e) {}
		}
		
		$orderItemsSetVar = '';
		foreach($orderItemsVar as $var){
			try{
				if ($order->$var) {
					$orderItemsSetVar = $orderItemsSetVar. ''. $var.'="'.$order->$var.'",';
				}
			}catch(Exception $e) {}
		}

		$ordersSql = "UPDATE orders SET $ordersSetVar updated_at='$timestamp' WHERE id=:id";
		$orderItemsSql = "UPDATE order_items SET $orderItemsSetVar updated_at='$timestamp' WHERE order_id=:id";

        $ordersStmt = $db->prepare($ordersSql);
        $orderItemsStmt = $db->prepare($orderItemsSql);
        
        $ordersStmt->bindParam("id", $id);
        $orderItemsStmt->bindParam("id", $id);
      
        $db->beginTransaction();
        
        $ordersStmt->execute();
    	$orderItemsStmt->execute();
        $db->commit();
        echo json_encode(array(
            "status" => true,
            "message" => "order updated successfully",
            "order" => $order
        ));
    } catch(PDOException $e) {
        echo json_encode(array(
            "status" => false,
            "message" => $e->getMessage()
        ));
    }
}

function postOrder() {
    $request = \Slim\Slim::getInstance()->request();
    $order = json_decode($request->getBody());
    $ordersSql = "INSERT INTO orders (email_id, status, created_at, updated_at) VALUES (:email_id, :status, :created_at, :updated_at)";
    $orderItemsSql = "INSERT INTO order_items (order_id, name, price, quantity, created_at, updated_at) VALUES (:order_id, :name, :price, :quantity, :created_at, :updated_at)";
    
    $valid = json_decode(validateOrder($order));
  	if (!$valid->status) {
  	  	echo json_encode($valid);
  	  	return;
  	}

    $db = getConnection();
    try {
		$timestamp = date('Y-m-d G:i:s');
        $ordersStmt = $db->prepare($ordersSql);
        $ordersStmt->bindParam("email_id", $order->email_id);
        $ordersStmt->bindParam("status", $order->status);
        $ordersStmt->bindParam("created_at", $timestamp);
        $ordersStmt->bindParam("updated_at", $timestamp);
        
        $orderItemsStmt = $db->prepare($orderItemsSql);
        $orderItemsStmt->bindParam("name", $order->name);
        $orderItemsStmt->bindParam("price", $order->price);
        $orderItemsStmt->bindParam("quantity", $order->quantity);
        $orderItemsStmt->bindParam("created_at", $timestamp);
        $orderItemsStmt->bindParam("updated_at", $timestamp);
    
        $db->beginTransaction();
        
        $ordersStmt->execute();
    	
    	$order->id = $db->lastInsertId();
        $orderItemsStmt->bindParam("order_id", $order->id);
        $orderItemsStmt->execute();
    	
    	$db->commit();

        $db = null;
        echo json_encode(array(
            "status" => true,
            "message" => "order posted successfully",
            "order" => $order
        ));
    } catch(PDOException $e) {
        $db->rollBack();
        echo json_encode(array(
            "status" => false,
            "message" => $e->getMessage()
        ));
    }
}	

function validateOrder($order){
	$errorInOrder 	= false;
	
	$ordersVar    	= array("email_id", "status", "name", "price", "quantity");
    $errors 	  	= array( );
	foreach($ordersVar as $var){
		try{
            $message = checkForVariableDataInOrders($order, $var);
            if($message != ''){
                $errors[] = $message;
                $errorInOrder =true;
            }
		}catch(Exception $e) {}
	}
	
	$string = '';
	foreach($errors as $key=>$error){
		if($string != '')
			$string = $string. ', ';
		$string = $string . "Invalid ".$error;
	}

	if ($errorInOrder) {
	    return json_encode(array(
            "status" => false,
            "message" => $string
        ));
	}
	return json_encode(array(
            "status" => true,
            "message" => "Valid orders."
        ));
}

function checkForVariableDataInOrders($order ,$var){

    $message = '';
    switch ($var) {
        case "email_id":
            if (!filter_var($order->$var, FILTER_VALIDATE_EMAIL)) {
                $message  = 'email_id';
            }
            break;
        case "status":
            $statusArr = array('created', 'processed', 'delivered', 'cancelled');
            $validStatus = false;
            foreach($statusArr as $status){
                if ($status == $order->$var) {
                    $validStatus = true;
                }
            }
            if (!$validStatus) {
                $message = 'status';
            }
            break;
        case "name":
            if ($order->$var == '') {
                $message = 'name';
            }
            break;
        case "price":
            $options['options']['min_range'] = 1; 
            $options['options']['max_range'] = 10000; 
            
            if (!filter_var($order->$var, FILTER_VALIDATE_INT, $options)) {
                //if (!filter_var($order->$var, FILTER_VALIDATE_FLOAT)) {
                //Todo : find exact diff between FILTER_VALIDATE_FLOAT & FILTER_VALIDATE_INT
                $message = 'price';
            }
            break;
        case "quantity":
            $options['options']['min_range'] = 1; 
            $options['options']['max_range'] = 100; 
            
            if (!filter_var($order->$var, FILTER_VALIDATE_INT, $options)) {
                $message = 'quantity';
            }
            break;
    }
    return  $message;
}

function getConnection() {
    $dbhost="127.0.0.1";
    $dbuser="root";
    $dbpass="";
    $dbname="phpappwithslim";
    $dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $dbh;
}

?>