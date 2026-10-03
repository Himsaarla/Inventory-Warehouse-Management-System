<?php
declare(strict_types=1);
require __DIR__.'/../includes/config.php';

$action = $_GET['action'] ?? '';

try {
    if ($action === 'login') { json_response(false,null,'Use login.php for authentication.',405); }
    require_login();
    $pdo = db();

    if ($_SERVER['REQUEST_METHOD']==='GET') {
        switch ($action) {
            case 'dashboard':
                $data=[];
                $data['products']=(int)$pdo->query('SELECT COUNT(*) FROM products WHERE active=1')->fetchColumn();
                $data['suppliers']=(int)$pdo->query('SELECT COUNT(*) FROM suppliers')->fetchColumn();
                $data['warehouses']=(int)$pdo->query('SELECT COUNT(*) FROM warehouses')->fetchColumn();
                $data['customers']=(int)$pdo->query('SELECT COUNT(*) FROM customers')->fetchColumn();
                $data['units']=(int)$pdo->query('SELECT COALESCE(SUM(quantity),0) FROM stock')->fetchColumn();
                $data['low_stock']=(int)$pdo->query('SELECT COUNT(*) FROM (SELECT p.id FROM products p JOIN stock s ON s.product_id=p.id WHERE p.active=1 GROUP BY p.id,p.reorder_level HAVING SUM(s.quantity)<p.reorder_level) x')->fetchColumn();
                $data['orders_today']=(int)$pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at)=CURDATE() AND status='completed'")->fetchColumn();
                $data['sales_today']=(float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE type='sale' AND status='completed' AND DATE(created_at)=CURDATE()")->fetchColumn();
                $data['low_items']=$pdo->query("SELECT p.id,p.sku,p.name,p.reorder_level,SUM(s.quantity) quantity FROM products p JOIN stock s ON s.product_id=p.id WHERE p.active=1 GROUP BY p.id,p.sku,p.name,p.reorder_level HAVING SUM(s.quantity)<p.reorder_level ORDER BY quantity ASC LIMIT 10")->fetchAll();
                $data['recent_orders']=$pdo->query("SELECT o.id,o.order_no,o.type,o.total,o.status,o.created_at, w.name warehouse FROM orders o JOIN warehouses w ON w.id=o.warehouse_id ORDER BY o.id DESC LIMIT 8")->fetchAll();
                $data['server_time']=date('Y-m-d H:i:s');
                json_response(true,$data);
            case 'products':
                $q=trim($_GET['q']??''); $sql="SELECT p.*,c.name category,s.name supplier,COALESCE((SELECT SUM(st.quantity) FROM stock st WHERE st.product_id=p.id),0) total_stock FROM products p JOIN categories c ON c.id=p.category_id JOIN suppliers s ON s.id=p.supplier_id WHERE p.active=1"; $args=[];
                if($q!==''){ $sql.=' AND (p.name LIKE ? OR p.sku LIKE ? OR c.name LIKE ? OR s.name LIKE ?)'; $like="%$q%"; $args=[$like,$like,$like,$like]; }
                $sql.=' ORDER BY p.id DESC'; $st=$pdo->prepare($sql);$st->execute($args);json_response(true,$st->fetchAll());
            case 'categories': json_response(true,$pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll());
            case 'suppliers': json_response(true,$pdo->query('SELECT * FROM suppliers ORDER BY id DESC')->fetchAll());
            case 'customers': json_response(true,$pdo->query('SELECT * FROM customers ORDER BY id DESC')->fetchAll());
            case 'warehouses': json_response(true,$pdo->query('SELECT * FROM warehouses ORDER BY id DESC')->fetchAll());
            case 'stock':
                $rows=$pdo->query("SELECT st.id,st.product_id,st.warehouse_id,st.quantity,st.updated_at,p.sku,p.name product,w.name warehouse,p.reorder_level FROM stock st JOIN products p ON p.id=st.product_id JOIN warehouses w ON w.id=st.warehouse_id WHERE p.active=1 ORDER BY st.updated_at DESC")->fetchAll();json_response(true,$rows);
            case 'movements':
                $rows=$pdo->query("SELECT m.*,p.sku,p.name product,w.name warehouse,u.full_name user_name FROM stock_movements m JOIN products p ON p.id=m.product_id JOIN warehouses w ON w.id=m.warehouse_id JOIN users u ON u.id=m.created_by ORDER BY m.id DESC LIMIT 100")->fetchAll();json_response(true,$rows);
            case 'orders':
                $rows=$pdo->query("SELECT o.*,s.name supplier,c.name customer,w.name warehouse,u.full_name created_by_name FROM orders o JOIN warehouses w ON w.id=o.warehouse_id LEFT JOIN suppliers s ON s.id=o.supplier_id LEFT JOIN customers c ON c.id=o.customer_id JOIN users u ON u.id=o.created_by ORDER BY o.id DESC LIMIT 200")->fetchAll();json_response(true,$rows);
            case 'order_items':
                $id=(int)($_GET['id']??0);$st=$pdo->prepare("SELECT oi.*,p.sku,p.name FROM order_items oi JOIN products p ON p.id=oi.product_id WHERE oi.order_id=? ORDER BY oi.id");$st->execute([$id]);json_response(true,$st->fetchAll());
            case 'users': require_admin(); json_response(true,$pdo->query('SELECT id,full_name,username,role,active,created_at FROM users ORDER BY id DESC')->fetchAll());
            case 'reports':
                $data=[];
                $data['stock_value']=(float)$pdo->query('SELECT COALESCE(SUM(st.quantity*p.price),0) FROM stock st JOIN products p ON p.id=st.product_id')->fetchColumn();
                $data['purchase_total']=(float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE type='purchase' AND status='completed'")->fetchColumn();
                $data['sales_total']=(float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE type='sale' AND status='completed'")->fetchColumn();
                $data['stock_by_warehouse']=$pdo->query("SELECT w.name warehouse,COALESCE(SUM(st.quantity),0) units,COALESCE(SUM(st.quantity*p.price),0) value FROM warehouses w LEFT JOIN stock st ON st.warehouse_id=w.id LEFT JOIN products p ON p.id=st.product_id GROUP BY w.id,w.name ORDER BY w.name")->fetchAll();
                $data['top_products']=$pdo->query("SELECT p.name,p.sku,SUM(oi.quantity) units FROM order_items oi JOIN orders o ON o.id=oi.order_id JOIN products p ON p.id=oi.product_id WHERE o.type='sale' AND o.status='completed' GROUP BY p.id,p.name,p.sku ORDER BY units DESC LIMIT 10")->fetchAll();
                json_response(true,$data);
            default: json_response(false,null,'Unknown action.',404);
        }
    }

    $in=body();
    switch($action){
        case 'category_create':
            $st=$pdo->prepare('INSERT INTO categories(name,description) VALUES(?,?)');$st->execute([trim($in['name']),trim($in['description']??'')]);json_response(true,['id'=>$pdo->lastInsertId()],'Category created.');
        case 'category_update':
            $st=$pdo->prepare('UPDATE categories SET name=?,description=? WHERE id=?');$st->execute([trim($in['name']),trim($in['description']??''),(int)$in['id']]);json_response(true,null,'Category updated.');
        case 'category_delete': require_admin(); $st=$pdo->prepare('DELETE FROM categories WHERE id=?');$st->execute([(int)$in['id']]);json_response(true,null,'Category deleted.');
        case 'supplier_create':
            $st=$pdo->prepare('INSERT INTO suppliers(name,phone,email,address) VALUES(?,?,?,?)');$st->execute([trim($in['name']),trim($in['phone']??''),trim($in['email']??''),trim($in['address']??'')]);json_response(true,['id'=>$pdo->lastInsertId()],'Supplier created.');
        case 'supplier_update':
            $st=$pdo->prepare('UPDATE suppliers SET name=?,phone=?,email=?,address=? WHERE id=?');$st->execute([trim($in['name']),trim($in['phone']??''),trim($in['email']??''),trim($in['address']??''),(int)$in['id']]);json_response(true,null,'Supplier updated.');
        case 'supplier_delete': require_admin(); $st=$pdo->prepare('DELETE FROM suppliers WHERE id=?');$st->execute([(int)$in['id']]);json_response(true,null,'Supplier deleted.');
        case 'customer_create':
            $st=$pdo->prepare('INSERT INTO customers(name,phone,email,address) VALUES(?,?,?,?)');$st->execute([trim($in['name']),trim($in['phone']??''),trim($in['email']??''),trim($in['address']??'')]);json_response(true,['id'=>$pdo->lastInsertId()],'Customer created.');
        case 'customer_update':
            $st=$pdo->prepare('UPDATE customers SET name=?,phone=?,email=?,address=? WHERE id=?');$st->execute([trim($in['name']),trim($in['phone']??''),trim($in['email']??''),trim($in['address']??''),(int)$in['id']]);json_response(true,null,'Customer updated.');
        case 'customer_delete': require_admin(); $st=$pdo->prepare('DELETE FROM customers WHERE id=?');$st->execute([(int)$in['id']]);json_response(true,null,'Customer deleted.');
        case 'warehouse_create':
            $st=$pdo->prepare('INSERT INTO warehouses(name,location,capacity) VALUES(?,?,?)');$st->execute([trim($in['name']),trim($in['location']),max(0,(int)$in['capacity'])]);json_response(true,['id'=>$pdo->lastInsertId()],'Warehouse created.');
        case 'warehouse_update':
            $st=$pdo->prepare('UPDATE warehouses SET name=?,location=?,capacity=? WHERE id=?');$st->execute([trim($in['name']),trim($in['location']),max(0,(int)$in['capacity']),(int)$in['id']]);json_response(true,null,'Warehouse updated.');
        case 'warehouse_delete': require_admin(); $st=$pdo->prepare('DELETE FROM warehouses WHERE id=?');$st->execute([(int)$in['id']]);json_response(true,null,'Warehouse deleted.');
        case 'product_create':
            $st=$pdo->prepare('INSERT INTO products(sku,name,category_id,supplier_id,price,reorder_level) VALUES(?,?,?,?,?,?)');$st->execute([trim($in['sku']),trim($in['name']),(int)$in['category_id'],(int)$in['supplier_id'],max(0,(float)$in['price']),max(0,(int)$in['reorder_level'])]);json_response(true,['id'=>$pdo->lastInsertId()],'Product created.');
        case 'product_update':
            $st=$pdo->prepare('UPDATE products SET sku=?,name=?,category_id=?,supplier_id=?,price=?,reorder_level=? WHERE id=?');$st->execute([trim($in['sku']),trim($in['name']),(int)$in['category_id'],(int)$in['supplier_id'],max(0,(float)$in['price']),max(0,(int)$in['reorder_level']),(int)$in['id']]);json_response(true,null,'Product updated.');
        case 'product_delete': require_admin(); $st=$pdo->prepare('UPDATE products SET active=0 WHERE id=?');$st->execute([(int)$in['id']]);json_response(true,null,'Product archived.');
        case 'stock_move':
            $product=(int)$in['product_id'];$warehouse=(int)$in['warehouse_id'];$qty=(int)$in['quantity'];$type=strtoupper($in['movement_type']??'IN');
            if($qty<=0) json_response(false,null,'Quantity must be greater than zero.',422);
            if(!in_array($type,['IN','OUT'],true)) json_response(false,null,'Movement type must be IN or OUT.',422);
            $pdo->beginTransaction();
            $st=$pdo->prepare('SELECT quantity FROM stock WHERE product_id=? AND warehouse_id=? FOR UPDATE');$st->execute([$product,$warehouse]);$current=$st->fetchColumn();
            if($current===false){$pdo->rollBack();json_response(false,null,'Stock record does not exist for this product and warehouse.',422);}
            $current=(int)$current;$new=$type==='IN'?$current+$qty:$current-$qty;
            if($new<0){$pdo->rollBack();json_response(false,null,'Insufficient stock for stock-out.',422);}
            $pdo->prepare('UPDATE stock SET quantity=? WHERE product_id=? AND warehouse_id=?')->execute([$new,$product,$warehouse]);
            $pdo->prepare('INSERT INTO stock_movements(product_id,warehouse_id,movement_type,quantity,reference_type,notes,created_by) VALUES(?,?,?,?,?,?,?)')->execute([$product,$warehouse,$type,''.$qty,'MANUAL',trim($in['notes']??''),user_id()]);
            $pdo->commit();json_response(true,['quantity'=>$new],'Stock updated successfully.');
        case 'order_create':
            $type=$in['type']??'';$warehouse=(int)$in['warehouse_id'];$items=$in['items']??[];
            if(!in_array($type,['purchase','sale'],true)||!$warehouse||!is_array($items)||!count($items)) json_response(false,null,'Order type, warehouse and at least one item are required.',422);
            $supplier=!empty($in['supplier_id'])?(int)$in['supplier_id']:null;$customer=!empty($in['customer_id'])?(int)$in['customer_id']:null;
            if($type==='purchase'&&!$supplier) json_response(false,null,'Supplier is required for purchase orders.',422);
            if($type==='sale'&&!$customer) json_response(false,null,'Customer is required for sales orders.',422);
            $pdo->beginTransaction();
            $orderNo=($type==='purchase'?'PO-':'SO-').date('YmdHis').'-'.random_int(100,999);
            $pdo->prepare('INSERT INTO orders(order_no,type,supplier_id,customer_id,warehouse_id,total,created_by) VALUES(?,?,?,?,?,?,?)')->execute([$orderNo,$type,$supplier,$customer,$warehouse,0,user_id()]);$orderId=(int)$pdo->lastInsertId();$total=0;
            foreach($items as $it){$pid=(int)($it['product_id']??0);$qty=(int)($it['quantity']??0);$price=max(0,(float)($it['unit_price']??0));if(!$pid||$qty<=0){throw new RuntimeException('Every order item needs a product and positive quantity.');}
                $st=$pdo->prepare('SELECT price FROM products WHERE id=? AND active=1');$st->execute([$pid]);$dbprice=$st->fetchColumn();if($dbprice===false)throw new RuntimeException('Invalid product in order.');if($price<=0)$price=(float)$dbprice;
                $st=$pdo->prepare('SELECT quantity FROM stock WHERE product_id=? AND warehouse_id=? FOR UPDATE');$st->execute([$pid,$warehouse]);$cur=$st->fetchColumn();if($cur===false)throw new RuntimeException('No stock record exists for one of the selected products in this warehouse.');$cur=(int)$cur;
                if($type==='sale'&&$cur<$qty)throw new RuntimeException("Insufficient stock for product ID $pid.");
                $pdo->prepare('INSERT INTO order_items(order_id,product_id,quantity,unit_price) VALUES(?,?,?,?)')->execute([$orderId,$pid,$qty,$price]);$total += $qty*$price;
                $new=$type==='purchase'?$cur+$qty:$cur-$qty;$move=$type==='purchase'?'IN':'OUT';
                $pdo->prepare('UPDATE stock SET quantity=? WHERE product_id=? AND warehouse_id=?')->execute([$new,$pid,$warehouse]);
                $pdo->prepare('INSERT INTO stock_movements(product_id,warehouse_id,movement_type,quantity,reference_type,reference_id,notes,created_by) VALUES(?,?,?,?,?,?,?,?)')->execute([$pid,$warehouse,$move,$qty,'ORDER',$orderId,$orderNo,user_id()]);
            }
            $pdo->prepare('UPDATE orders SET total=? WHERE id=?')->execute([$total,$orderId]);$pdo->commit();json_response(true,['order_id'=>$orderId,'order_no'=>$orderNo,'total'=>$total],'Order created and stock updated.');
        case 'user_create': require_admin(); $pass=$in['password']??'';if(strlen($pass)<6)json_response(false,null,'Password must be at least 6 characters.',422);$st=$pdo->prepare('INSERT INTO users(full_name,username,password_hash,role,active) VALUES(?,?,?,?,1)');$st->execute([trim($in['full_name']),trim($in['username']),password_hash($pass,PASSWORD_DEFAULT),$in['role']==='admin'?'admin':'staff']);json_response(true,null,'User created.');
        case 'user_toggle': require_admin(); $st=$pdo->prepare('UPDATE users SET active=IF(active=1,0,1) WHERE id=?');$st->execute([(int)$in['id']]);json_response(true,null,'User status updated.');
        default: json_response(false,null,'Unknown action.',404);
    }
} catch(Throwable $e){
    if(isset($pdo) && $pdo->inTransaction())$pdo->rollBack();
    $msg=$e instanceof PDOException && str_contains($e->getMessage(),'Duplicate') ? 'Duplicate value. Check unique fields.' : $e->getMessage();
    json_response(false,null,$msg,500);
}
