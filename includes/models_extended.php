<?php
/**
 * Extended Models for E-Commerce Platform
 * Updated with full functionality support
 */

class UserProfile extends BaseModel {
    protected $table = 'user_profiles';
    public function findByUserId($userId) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    public function createProfile($userId, $data) {
        $stmt = $this->db->prepare("INSERT INTO {$this->table} (user_id, first_name, last_name, display_name, bio, phone, date_of_birth, gender, language, timezone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            $userId,
            $data['first_name'] ?? null,
            $data['last_name'] ?? null,
            $data['display_name'] ?? null,
            $data['bio'] ?? null,
            $data['phone'] ?? null,
            $data['date_of_birth'] ?? null,
            $data['gender'] ?? null,
            $data['language'] ?? 'en',
            $data['timezone'] ?? 'UTC'
        ]);
    }
    public function updateProfile($userId, $data) {
        $fields = [];
        $values = [];
        foreach (['first_name','last_name','display_name','bio','phone','date_of_birth','gender','language','timezone','avatar_url'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }
        if (empty($fields)) return false;
        $fields[] = "updated_at = CURRENT_TIMESTAMP";
        $values[] = $userId;
        $stmt = $this->db->prepare("UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE user_id = ?");
        return $stmt->execute($values);
    }
}

class Address extends BaseModel {
    protected $table = 'addresses';
    public function getUserAddresses($userId) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    public function createAddress($userId, $data) {
        if (!empty($data['is_default'])) {
            $this->db->prepare("UPDATE {$this->table} SET is_default = 0 WHERE user_id = ?")->execute([$userId]);
        }
        $stmt = $this->db->prepare("INSERT INTO {$this->table} (user_id, type, label, first_name, last_name, company, address_line1, address_line2, city, state, postal_code, country, phone, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            $userId,
            $data['type'] ?? 'both',
            $data['label'] ?? null,
            $data['first_name'] ?? null,
            $data['last_name'] ?? null,
            $data['company'] ?? null,
            $data['address_line1'],
            $data['address_line2'] ?? null,
            $data['city'],
            $data['state'],
            $data['postal_code'],
            $data['country'] ?? 'US',
            $data['phone'] ?? null,
            $data['is_default'] ?? 0
        ]);
    }
}

class Wishlist extends BaseModel {
    protected $table = 'wishlists';
    public function getUserWishlist($userId, $limit = null) {
        // Fix #5: Handle missing product_images table in wishlist queries
        $sql = "
            SELECT w.*, p.name, p.price, p.status, p.stock_quantity,
                   v.business_name as vendor_name, w.created_at as added_at
            FROM {$this->table} w
            JOIN products p ON w.product_id = p.id
            LEFT JOIN vendors v ON p.vendor_id = v.id
            WHERE w.user_id = ? AND p.status = 'active'
            ORDER BY w.created_at DESC
        ";
        if ($limit) {
            $sql .= " LIMIT $limit";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    public function addToWishlist($userId, $productId, $notes = null) {
        // Fix #6: Handle duplicate entries gracefully with proper error checking
        try {
            $stmt = $this->db->prepare("INSERT INTO {$this->table} (user_id, product_id, notes, created_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
            return $stmt->execute([$userId, $productId, $notes]);
        } catch (PDOException $e) {
            // Handle duplicate entry (already exists)
            if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
                return false; // Item already in wishlist
            }
            throw $e; // Re-throw other errors
        }
    }
    public function removeFromWishlist($userId, $productId) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE user_id = ? AND product_id = ?");
        return $stmt->execute([$userId, $productId]);
    }
    public function isInWishlist($userId, $productId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        return (int)$stmt->fetchColumn() > 0;
    }
}

class Review extends BaseModel {
    protected $table = 'reviews';
    public function getProductReviews($productId, $limit = null, $offset = 0) {
        $sql = "
            SELECT r.*, u.username,
                   COALESCE(u.username, 'Anonymous') as reviewer_name
            FROM {$this->table} r
            LEFT JOIN users u ON r.user_id = u.id
            WHERE r.product_id = ? AND r.status = 'approved'
            ORDER BY r.created_at DESC
        ";
        if ($limit) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }
    public function addReview($data) {
        $stmt = $this->db->prepare("INSERT INTO {$this->table} (product_id, user_id, order_id, rating, title, review_text, pros, cons, is_verified_purchase) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            $data['product_id'],
            $data['user_id'],
            $data['order_id'] ?? null,
            $data['rating'],
            $data['title'] ?? null,
            $data['review_text'] ?? null,
            $data['pros'] ?? null,
            $data['cons'] ?? null,
            $data['is_verified_purchase'] ?? 0
        ]);
    }
    public function getProductRatingStats($productId) {
        $stmt = $this->db->prepare("
            SELECT 
                AVG(rating) as average_rating,
                COUNT(*) as total_reviews,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
            FROM {$this->table} 
            WHERE product_id = ? AND status = 'approved'
        ");
        $stmt->execute([$productId]);
        return $stmt->fetch();
    }
    public function getUserReviews($userId, $limit = null, $offset = 0) {
        $sql = "
            SELECT r.*, p.name as product_name 
            FROM {$this->table} r 
            JOIN products p ON r.product_id = p.id 
            WHERE r.user_id = ? 
            ORDER BY r.created_at DESC
        ";
        $params = [$userId];
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    public function approve($reviewId) { return $this->update($reviewId, ['status' => 'approved']); }
    public function reject($reviewId) { return $this->update($reviewId, ['status' => 'rejected']); }
    public function getPending() {
        $stmt = $this->db->prepare("
            SELECT r.*, p.name as product_name, u.username
            FROM {$this->table} r 
            JOIN products p ON r.product_id = p.id 
            LEFT JOIN users u ON r.user_id = u.id
            WHERE r.status = 'pending' 
            ORDER BY r.created_at ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

class Notification extends BaseModel {
    protected $table = 'notifications';
    public function getUserNotifications($userId, $limit = 20, $unreadOnly = false) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ?";
        if ($unreadOnly) $sql .= " AND is_read = 0";
        $sql .= " ORDER BY created_at DESC LIMIT $limit";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    public function createNotification($userId, $type, $title, $message, $data = null, $actionUrl = null) {
        $stmt = $this->db->prepare("INSERT INTO {$this->table} (user_id, type, title, message, data, action_url) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$userId, $type, $title, $message, $data ? json_encode($data) : null, $actionUrl]);
    }
    public function markAsRead($notificationId, $userId) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET is_read = 1, read_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?");
        return $stmt->execute([$notificationId, $userId]);
    }
    public function markAllAsRead($userId) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET is_read = 1, read_at = CURRENT_TIMESTAMP WHERE user_id = ? AND is_read = 0");
        return $stmt->execute([$userId]);
    }
    public function getUnreadCount($userId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }
}

class Transaction extends BaseModel {
    protected $table = 'transactions';
    public function createTransaction($data) {
        $stmt = $this->db->prepare("INSERT INTO {$this->table} (user_id, order_id, type, amount, currency, status, payment_method, gateway, gateway_transaction_id, gateway_fee, platform_fee, net_amount, description, metadata) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            $data['user_id'],
            $data['order_id'] ?? null,
            $data['type'],
            $data['amount'],
            $data['currency'] ?? 'USD',
            $data['status'] ?? 'pending',
            $data['payment_method'] ?? null,
            $data['gateway'] ?? null,
            $data['gateway_transaction_id'] ?? null,
            $data['gateway_fee'] ?? 0,
            $data['platform_fee'] ?? 0,
            $data['net_amount'] ?? $data['amount'],
            $data['description'] ?? null,
            isset($data['metadata']) ? json_encode($data['metadata']) : null
        ]);
    }
    public function updateTransactionStatus($transactionId, $status, $processedAt = null) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status = ?, processed_at = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        return $stmt->execute([$status, $processedAt ?? date('Y-m-d H:i:s'), $transactionId]);
    }
    public function getUserTransactions($userId, $limit = 20) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY created_at DESC LIMIT $limit");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}

class Coupon extends BaseModel {
    protected $table = 'coupons';
    public function findByCode($code) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE code = ? AND is_active = 1");
        $stmt->execute([$code]);
        return $stmt->fetch();
    }
    public function validateCoupon($code, $userId, $cartTotal, $productIds = []) {
        $coupon = $this->findByCode($code);
        if (!$coupon) return ['valid' => false, 'message' => 'Coupon not found'];
        if ($coupon['starts_at'] && strtotime($coupon['starts_at']) > time()) return ['valid' => false, 'message' => 'Coupon not yet active'];
        if ($coupon['expires_at'] && strtotime($coupon['expires_at']) < time()) return ['valid' => false, 'message' => 'Coupon has expired'];
        if ($coupon['usage_limit'] && $coupon['usage_count'] >= $coupon['usage_limit']) return ['valid' => false, 'message' => 'Coupon usage limit reached'];
        if ($coupon['user_limit']) {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM coupon_usage WHERE coupon_id = ? AND user_id = ?");
            $stmt->execute([$coupon['id'], $userId]);
            if ($stmt->fetchColumn() >= $coupon['user_limit']) {
                return ['valid' => false, 'message' => 'You have reached the usage limit for this coupon'];
            }
        }
        if ($coupon['minimum_amount'] && $cartTotal < $coupon['minimum_amount']) {
            return ['valid' => false, 'message' => "Minimum order amount is $" . number_format($coupon['minimum_amount'], 2)];
        }
        return ['valid' => true, 'coupon' => $coupon];
    }
    public function calculateDiscount($coupon, $cartTotal) {
        switch ($coupon['type']) {
            case 'percentage':
                $discount = $cartTotal * ($coupon['value'] / 100);
                if ($coupon['maximum_discount'] && $discount > $coupon['maximum_discount']) {
                    $discount = $coupon['maximum_discount'];
                }
                break;
            case 'fixed_amount':
                $discount = min($coupon['value'], $cartTotal);
                break;
            case 'free_shipping':
                $discount = 0;
                break;
            default:
                $discount = 0;
        }
        return round($discount, 2);
    }
    public function recordUsage($couponId, $userId, $orderId, $discountAmount) {
        $stmt = $this->db->prepare("INSERT INTO coupon_usage (coupon_id, user_id, order_id, discount_amount) VALUES (?, ?, ?, ?)");
        $result = $stmt->execute([$couponId, $userId, $orderId, $discountAmount]);
        if ($result) {
            $stmt = $this->db->prepare("UPDATE {$this->table} SET usage_count = usage_count + 1 WHERE id = ?");
            $stmt->execute([$couponId]);
        }
        return $result;
    }
}

class Order extends BaseModel {
    protected $table = 'orders';
    public function createOrder($userId, $orderData) {
        try {
            $this->db->beginTransaction();
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            $stmt = $this->db->prepare("
                INSERT INTO {$this->table} 
                (user_id, order_number, status, total, created_at) 
                VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
            ");
            $stmt->execute([
                $userId,
                $orderNumber,
                $orderData['status'] ?? 'pending',
                $orderData['total']
            ]);
            $orderId = $this->db->lastInsertId();
            if (!empty($orderData['items'])) {
                $itemStmt = $this->db->prepare("
                    INSERT INTO order_items 
                    (order_id, product_id, vendor_id, qty, price, subtotal, product_name, sku) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $productModel = new Product();
                foreach ($orderData['items'] as $item) {
                    // Decrement stock for each item
                    $stockDecreased = $productModel->decreaseStock($item['product_id'], $item['quantity']);
                    if (!$stockDecreased) {
                        throw new Exception("Insufficient stock for product ID: {$item['product_id']}");
                    }
                    
                    $itemStmt->execute([
                        $orderId,
                        $item['product_id'],
                        $item['vendor_id'] ?? null,
                        $item['quantity'],
                        $item['unit_price'],
                        $item['quantity'] * $item['unit_price'],
                        $item['product_name'],
                        $item['product_sku'] ?? null
                    ]);
                }
            } else {
                $cart = new Cart();
                $cartItems = $cart->getCartItems($userId);
                
                // Validate cart is not empty
                if (empty($cartItems)) {
                    throw new Exception('Cart is empty');
                }
                
                $productModel = new Product();
                foreach ($cartItems as $item) {
                    // Decrement stock for each item - this will fail if insufficient stock
                    $stockDecreased = $productModel->decreaseStock($item['product_id'], $item['quantity']);
                    if (!$stockDecreased) {
                        throw new Exception("Insufficient stock for product: {$item['name']}");
                    }
                    
                    $this->addOrderItem($orderId, $item);
                }
                $cart->clearCart($userId);
            }
            $this->db->commit();
            return $orderId;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    private function addOrderItem($orderId, $item) {
        // Insert order item with correct column names matching schema
        $stmt = $this->db->prepare("
            INSERT INTO order_items 
            (order_id, product_id, vendor_id, qty, price, subtotal, product_name, sku) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $product = new Product();
        $productData = $product->find($item['product_id']);
        return $stmt->execute([
            $orderId,
            $item['product_id'],
            $productData['vendor_id'] ?? 1,
            $item['quantity'],
            $item['price'],
            $item['quantity'] * $item['price'],
            $item['name'] ?? $productData['name'],
            $productData['sku'] ?? null
        ]);
    }
    public function getOrderWithItems($orderId, $userId = null) {
        $sql = "SELECT o.* FROM {$this->table} o WHERE o.id = ?";
        $params = [$orderId];
        if ($userId) {
            $sql .= " AND o.user_id = ?";
            $params[] = $userId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $order = $stmt->fetch();
        if ($order) {
            $itemStmt = $this->db->prepare("SELECT * FROM order_items WHERE order_id = ?");
            $itemStmt->execute([$orderId]);
            $order['items'] = $itemStmt->fetchAll();
        }
        return $order;
    }
    public function getUserOrders($userId, $limit = 20, $offset = 0) {
        $sql = "
            SELECT o.*, COUNT(oi.id) as item_count
            FROM {$this->table} o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE o.user_id = ?
            GROUP BY o.id
            ORDER BY o.created_at DESC
        ";
        $params = [$userId];
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    public function findByOrderNumber($orderNumber) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE order_number = ? OR order_reference = ? LIMIT 1");
        $stmt->execute([$orderNumber, $orderNumber]);
        return $stmt->fetch();
    }
    public function getOrderItems($orderId) {
        $stmt = $this->db->prepare("
            SELECT oi.*, p.name as current_product_name 
            FROM order_items oi 
            LEFT JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }
    public function updateStatus($orderId, $status) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        return $stmt->execute([$status, $orderId]);
    }
    public function updatePaymentStatus($orderId, $status, $transactionId = null) {
        $sql = "UPDATE {$this->table} SET payment_status = ?, updated_at = CURRENT_TIMESTAMP";
        $params = [$status];
        if ($transactionId) {
            $sql .= ", payment_transaction_id = ?";
            $params[] = $transactionId;
        }
        $sql .= " WHERE id = ?";
        $params[] = $orderId;
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    public function getVendorOrders($vendorId, $limit = null, $offset = 0) {
        $sql = "
            SELECT DISTINCT o.*, u.username, u.email,
                   COALESCE(u.username, 'Customer') as customer_name
            FROM {$this->table} o 
            JOIN order_items oi ON o.id = oi.order_id 
            JOIN users u ON o.user_id = u.id 
            WHERE oi.vendor_id = ? 
            ORDER BY o.created_at DESC
        ";
        $params = [$vendorId];
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    public function getOrderStats($vendorId = null) {
        $whereClause = $vendorId ? "WHERE oi.vendor_id = ?" : "";
        $params = $vendorId ? [$vendorId] : [];
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(DISTINCT o.id) as total_orders,
                SUM(oi.total_price) as total_revenue,
                AVG(oi.total_price) as average_order_value,
                COUNT(CASE WHEN o.status = 'pending' THEN 1 END) as pending_orders,
                COUNT(CASE WHEN o.status = 'processing' THEN 1 END) as processing_orders,
                COUNT(CASE WHEN o.status = 'shipped' THEN 1 END) as shipped_orders,
                COUNT(CASE WHEN o.status = 'delivered' THEN 1 END) as delivered_orders
            FROM orders o 
            JOIN order_items oi ON o.id = oi.order_id 
            {$whereClause}
        ");
        $stmt->execute($params);
        return $stmt->fetch();
    }
}

class Vendor extends BaseModel {
    protected $table = 'vendors';
    
    public function findByUserId($userId) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    public function createVendorApplication($userId, $vendorData) {
        $vendorData['user_id'] = $userId;
        $vendorData['status'] = 'pending';
        return $this->create($vendorData);
    }

    /**
     * NEW: Provide legacy-compatible method expected by admin/products/index.php
     * @param string|null $status Optional status filter ('approved','pending','suspended', etc.)
     * @param int|null $limit Optional limit
     * @param int $offset Offset for pagination
     */
    public function getAll($status = null, $limit = null, $offset = 0) {
        $sql = "
            SELECT v.*, u.username, u.email
            FROM {$this->table} v
            LEFT JOIN users u ON v.user_id = u.id
        ";
        $params = [];
        if ($status !== null) {
            $sql .= " WHERE v.status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY v.business_name ASC";
        if ($limit !== null) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getApproved($limit = null, $offset = 0) {
        $sql = "
            SELECT v.*, u.username, u.email
            FROM {$this->table} v 
            JOIN users u ON v.user_id = u.id 
            WHERE v.status = 'approved'
            ORDER BY v.created_at DESC
        ";
        $params = [];
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getPending() {
        $stmt = $this->db->prepare("
            SELECT v.*, u.username, u.email
            FROM {$this->table} v 
            JOIN users u ON v.user_id = u.id 
            WHERE v.status = 'pending'
            ORDER BY v.created_at ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function approve($vendorId) {
        return $this->update($vendorId, ['status' => 'approved']);
    }
    public function suspend($vendorId) {
        return $this->update($vendorId, ['status' => 'suspended']);
    }
    public function getVendorStats($vendorId) {
        $product = new Product();
        $order = new Order();
        $productCount = $product->count("vendor_id = {$vendorId}");
        $orderStats = $order->getOrderStats($vendorId);
        return [
            'product_count' => $productCount,
            'total_orders' => $orderStats['total_orders'] ?? 0,
            'total_revenue' => $orderStats['total_revenue'] ?? 0,
            'average_order_value' => $orderStats['average_order_value'] ?? 0
        ];
    }
}

class SupportTicket extends BaseModel {
    protected $table = 'support_tickets';
    public function createTicket($data) {
        $stmt = $this->db->prepare("INSERT INTO {$this->table} (user_id, subject, message, priority, category, related_order_id, related_product_id, attachments) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            $data['user_id'] ?? null,
            $data['subject'],
            $data['message'],
            $data['priority'] ?? 'normal',
            $data['category'] ?? null,
            $data['related_order_id'] ?? null,
            $data['related_product_id'] ?? null,
            isset($data['attachments']) ? json_encode($data['attachments']) : null
        ]);
    }
    public function getUserTickets($userId, $limit = 20) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY created_at DESC LIMIT $limit");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    public function getTicketWithMessages($ticketId, $userId = null) {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $params = [$ticketId];
        if ($userId) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $ticket = $stmt->fetch();
        if ($ticket) {
            $stmt = $this->db->prepare("
                SELECT sm.*, u.username
                FROM support_messages sm
                LEFT JOIN users u ON sm.user_id = u.id
                WHERE sm.ticket_id = ?
                ORDER BY sm.created_at ASC
            ");
            $stmt->execute([$ticketId]);
            $ticket['messages'] = $stmt->fetchAll();
        }
        return $ticket;
    }
}

class UserActivity extends BaseModel {
    protected $table = 'user_activities';
    public function logActivity($userId, $activityType, $data = null, $ipAddress = null, $userAgent = null) {
        $stmt = $this->db->prepare("INSERT INTO {$this->table} (user_id, activity_type, activity_data, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([
            $userId,
            $activityType,
            $data ? json_encode($data) : null,
            $ipAddress,
            $userAgent
        ]);
    }
    public function getUserActivities($userId, $limit = 50) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY created_at DESC LIMIT $limit");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}

class Setting extends BaseModel {
    protected $table = 'settings';
    public function getSetting($key, $default = null) {
        $stmt = $this->db->prepare("SELECT value, type FROM {$this->table} WHERE `key` = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        if (!$result) return $default;
        $value = $result['value'];
        return match ($result['type']) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int)$value,
            'json' => json_decode($value, true),
            default => $value
        };
    }
    public function setSetting($key, $value, $type = 'string', $description = null, $isPublic = false, $updatedBy = null) {
        switch ($type) {
            case 'boolean': $value = $value ? '1' : '0'; break;
            case 'json': $value = json_encode($value); break;
        }
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} 
            (`key`, `value`, type, description, is_public, updated_by, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
            ON DUPLICATE KEY UPDATE
            `value` = VALUES(`value`),
            type = VALUES(type),
            description = VALUES(description),
            is_public = VALUES(is_public),
            updated_by = VALUES(updated_by),
            updated_at = CURRENT_TIMESTAMP
        ");
        return $stmt->execute([$key, $value, $type, $description, $isPublic ? 1 : 0, $updatedBy]);
    }
    public function getPublicSettings() {
        $stmt = $this->db->prepare("SELECT `key`, value, type FROM {$this->table} WHERE is_public = 1");
        $stmt->execute();
        $results = $stmt->fetchAll();
        $settings = [];
        foreach ($results as $row) {
            $value = $row['value'];
            switch ($row['type']) {
                case 'boolean': $value = filter_var($value, FILTER_VALIDATE_BOOLEAN); break;
                case 'integer': $value = (int)$value; break;
                case 'json': $value = json_decode($value, true); break;
            }
            $settings[$row['key']] = $value;
        }
        return $settings;
    }
}

class Recommendation extends BaseModel {
    protected $table = 'user_activities';
    const ALLOWED_ACTIVITY_TYPES = ['view_product', 'add_to_cart', 'purchase', 'search', 'review'];
    public function logActivity($userId, $productId, $activityType, $metadata = []) {
        if (!in_array($activityType, self::ALLOWED_ACTIVITY_TYPES, true)) {
            $aliasMap = [
                'view' => 'view_product',
                'view_item' => 'view_product',
                'view_homepage' => 'view_product',
                'cart' => 'add_to_cart',
                'add' => 'add_to_cart',
                'buy' => 'purchase',
                'order' => 'purchase'
            ];
            $activityType = $aliasMap[$activityType] ?? null;
        }
        if ($activityType === null) {
            error_log("Invalid activity_type provided to logActivity()");
            return false;
        }
        try {
            $stmt = $this->db->prepare("
                INSERT INTO {$this->table} 
                (user_id, activity_type, activity_data, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $metadataJson = json_encode(array_merge($metadata, ['product_id' => $productId]));
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            return $stmt->execute([$userId, $activityType, $metadataJson, $ipAddress, $userAgent]);
        } catch (PDOException $e) {
            error_log("PDO Error in logActivity(): " . $e->getMessage());
            return false;
        }
    }
    public function getViewedTogether($productId, $limit = 6) {
        $stmt = $this->db->prepare("
            SELECT p.*, COUNT(*) as view_count, pi.image_path as image_url
            FROM {$this->table} ua1 
            JOIN {$this->table} ua2 ON ua1.user_id = ua2.user_id 
                AND JSON_EXTRACT(ua1.activity_data, '$.product_id') != JSON_EXTRACT(ua2.activity_data, '$.product_id')
                AND ua1.activity_type = 'view_product'
                AND ua2.activity_type = 'view_product'
            JOIN products p ON CAST(JSON_EXTRACT(ua2.activity_data, '$.product_id') AS INTEGER) = p.id
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_thumbnail = 1
            WHERE JSON_EXTRACT(ua1.activity_data, '$.product_id') = ? AND p.status = 'active'
            GROUP BY p.id 
            ORDER BY view_count DESC, p.created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$productId, $limit]);
        return $stmt->fetchAll();
    }
    public function getPurchasedTogether($productId, $limit = 6) {
        $stmt = $this->db->prepare("
            SELECT p.*, COUNT(*) as purchase_count, pi.image_path as image_url
            FROM order_items oi1 
            JOIN order_items oi2 ON oi1.order_id = oi2.order_id 
                AND oi1.product_id != oi2.product_id 
            JOIN products p ON oi2.product_id = p.id 
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_thumbnail = 1
            WHERE oi1.product_id = ? AND p.status = 'active'
            GROUP BY p.id 
            ORDER BY purchase_count DESC, p.created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$productId, $limit]);
        return $stmt->fetchAll();
    }
    public function getTrendingProducts($limit = 8) {
        $sql = "
            SELECT p.id, p.name AS title, p.price,
                   COALESCE(SUM(oi.quantity), 0) AS sold,
                   pi.image_path AS image_url
            FROM products p 
            LEFT JOIN order_items oi ON oi.product_id = p.id
            LEFT JOIN orders o ON o.id = oi.order_id 
              AND o.created_at >= date('now', '-7 days')
              AND o.status IN ('paid','shipped','delivered')
            LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_thumbnail = 1
            WHERE p.status = 'active'
            GROUP BY p.id, p.name, p.price, pi.image_path
            ORDER BY sold DESC, p.created_at DESC 
            LIMIT ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    public function getPersonalizedRecommendations($userId, $limit = 8) {
        $stmt = $this->db->prepare("
            SELECT p.*, 
                   COUNT(DISTINCT ua.id) as user_interest_score,
                   AVG(r.rating) as avg_rating,
                   pi.image_path as image_url
            FROM products p 
            LEFT JOIN {$this->table} ua ON JSON_EXTRACT(ua.activity_data, '$.product_id') = p.id
                AND ua.user_id = ?
            LEFT JOIN reviews r ON p.id = r.product_id AND r.status = 'approved'
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_thumbnail = 1
            WHERE p.status = 'active'
                AND p.id NOT IN (
                    SELECT oi.product_id 
                    FROM order_items oi 
                    JOIN orders o ON oi.order_id = o.id 
                    WHERE o.user_id = ?
                )
            GROUP BY p.id 
            ORDER BY user_interest_score DESC, avg_rating DESC, p.featured DESC 
            LIMIT ?
        ");
        $stmt->execute([$userId, $userId, $limit]);
        return $stmt->fetchAll();
    }
    public function getRecommendations($userId, $productId, $type = 'viewed_together', $limit = 6) {
        return match ($type) {
            'viewed_together' => $this->getViewedTogether($productId, $limit),
            'purchased_together' => $this->getPurchasedTogether($productId, $limit),
            'trending' => $this->getTrendingProducts($limit),
            'personalized' => $userId ? $this->getPersonalizedRecommendations($userId, $limit) : [],
            default => $this->getViewedTogether($productId, $limit),
        };
    }
}

class Watchlist extends BaseModel {
    protected $table = 'watchlist';
    
    public function getUserWatchlist($userId, $limit = null) {
        $sql = "
            SELECT w.*, p.name, p.price, p.status, p.stock_quantity,
                   v.business_name as vendor_name, w.created_at as added_at
            FROM {$this->table} w
            JOIN products p ON w.product_id = p.id
            LEFT JOIN vendors v ON p.vendor_id = v.id
            WHERE w.user_id = ? AND p.status = 'active'
            ORDER BY w.created_at DESC
        ";
        if ($limit) {
            $sql .= " LIMIT $limit";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    public function addToWatchlist($userId, $productId) {
        try {
            $stmt = $this->db->prepare("INSERT INTO {$this->table} (user_id, product_id, created_at) VALUES (?, ?, CURRENT_TIMESTAMP)");
            return $stmt->execute([$userId, $productId]);
        } catch (PDOException $e) {
            // Handle duplicate entry (already exists)
            if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false || strpos($e->getMessage(), 'Duplicate entry') !== false) {
                return false; // Item already in watchlist
            }
            throw $e; // Re-throw other errors
        }
    }
    
    public function removeFromWatchlist($userId, $productId) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE user_id = ? AND product_id = ?");
        return $stmt->execute([$userId, $productId]);
    }
    
    public function isInWatchlist($userId, $productId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        return (int)$stmt->fetchColumn() > 0;
    }
}

class Offer extends BaseModel {
    protected $table = 'offers';
    
    public function createOffer($productId, $userId, $offerPrice, $message = null, $expiresAt = null) {
        $stmt = $this->db->prepare("INSERT INTO {$this->table} (product_id, user_id, offer_price, message, expires_at, created_at) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)");
        return $stmt->execute([$productId, $userId, $offerPrice, $message, $expiresAt]);
    }
    
    public function getUserOffers($userId, $limit = null) {
        $sql = "
            SELECT o.*, p.name as product_name, p.price as current_price
            FROM {$this->table} o
            JOIN products p ON o.product_id = p.id
            WHERE o.user_id = ?
            ORDER BY o.created_at DESC
        ";
        if ($limit) {
            $sql .= " LIMIT $limit";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    public function getProductOffers($productId, $limit = null) {
        $sql = "
            SELECT o.*, u.username
            FROM {$this->table} o
            LEFT JOIN users u ON o.user_id = u.id
            WHERE o.product_id = ? AND o.status = 'pending'
            ORDER BY o.offer_price DESC, o.created_at ASC
        ";
        if ($limit) {
            $sql .= " LIMIT $limit";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }
    
    public function acceptOffer($offerId) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status = 'accepted', accepted_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        return $stmt->execute([$offerId]);
    }
    
    public function rejectOffer($offerId) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status = 'rejected', rejected_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        return $stmt->execute([$offerId]);
    }
    
    public function expireOldOffers() {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status = 'expired', updated_at = CURRENT_TIMESTAMP WHERE status = 'pending' AND expires_at < NOW()");
        return $stmt->execute();
    }
}

class LiveStream extends BaseModel {
    protected $table = 'live_streams';
    
    public function getActiveStreams($limit = 10) {
        $sql = "
            SELECT ls.*, v.business_name as vendor_name, v.id as vendor_id,
                   COUNT(DISTINCT sv.id) as current_viewers
            FROM {$this->table} ls
            JOIN vendors v ON ls.vendor_id = v.id
            LEFT JOIN stream_viewers sv ON ls.id = sv.stream_id
            WHERE ls.status = 'live'
            GROUP BY ls.id
            ORDER BY current_viewers DESC, ls.started_at DESC
            LIMIT ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    public function getStreamById($streamId) {
        $sql = "
            SELECT ls.*, v.business_name as vendor_name, v.id as vendor_id
            FROM {$this->table} ls
            JOIN vendors v ON ls.vendor_id = v.id
            WHERE ls.id = ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$streamId]);
        return $stmt->fetch();
    }
    
    public function createStream($vendorId, $data) {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} 
            (vendor_id, title, description, thumbnail_url, stream_url, chat_enabled, status, scheduled_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $vendorId,
            $data['title'],
            $data['description'] ?? null,
            $data['thumbnail_url'] ?? null,
            $data['stream_url'] ?? null,
            $data['chat_enabled'] ?? 1,
            $data['status'] ?? 'scheduled',
            $data['scheduled_at'] ?? null
        ]);
    }
    
    public function startStream($streamId) {
        $stmt = $this->db->prepare("
            UPDATE {$this->table} 
            SET status = 'live', started_at = CURRENT_TIMESTAMP 
            WHERE id = ? AND status = 'scheduled'
        ");
        return $stmt->execute([$streamId]);
    }
    
    public function endStream($streamId) {
        $stmt = $this->db->prepare("
            UPDATE {$this->table} 
            SET status = 'ended', ended_at = CURRENT_TIMESTAMP 
            WHERE id = ? AND status = 'live'
        ");
        return $stmt->execute([$streamId]);
    }
    
    public function updateViewerCount($streamId, $count) {
        $stmt = $this->db->prepare("
            UPDATE {$this->table} 
            SET viewer_count = ?, max_viewers = GREATEST(max_viewers, ?)
            WHERE id = ?
        ");
        return $stmt->execute([$count, $count, $streamId]);
    }
    
    public function getStreamStats($streamId) {
        $sql = "
            SELECT 
                ls.*,
                COUNT(DISTINCT CASE WHEN si.interaction_type = 'like' THEN si.id END) as likes_count,
                COUNT(DISTINCT CASE WHEN si.interaction_type = 'dislike' THEN si.id END) as dislikes_count,
                COUNT(DISTINCT CASE WHEN si.interaction_type = 'comment' THEN si.id END) as comments_count,
                COUNT(DISTINCT sv.id) as total_viewers,
                COUNT(DISTINCT so.id) as orders_count,
                COALESCE(SUM(so.amount), 0) as total_revenue
            FROM {$this->table} ls
            LEFT JOIN stream_interactions si ON ls.id = si.stream_id
            LEFT JOIN stream_viewers sv ON ls.id = sv.stream_id
            LEFT JOIN stream_orders so ON ls.id = so.stream_id
            WHERE ls.id = ?
            GROUP BY ls.id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$streamId]);
        return $stmt->fetch();
    }
    
    public function getStreamProducts($streamId) {
        $sql = "
            SELECT p.*, lsp.special_price, lsp.discount_percentage
            FROM live_stream_products lsp
            JOIN products p ON lsp.product_id = p.id
            WHERE lsp.stream_id = ?
            ORDER BY lsp.display_order
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$streamId]);
        return $stmt->fetchAll();
    }
}

class SavedStream extends BaseModel {
    protected $table = 'saved_streams';
    
    public function saveStream($streamData) {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} 
            (stream_id, vendor_id, title, description, video_url, thumbnail_url, 
             duration, viewer_count, total_revenue, streamed_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $streamData['stream_id'],
            $streamData['vendor_id'],
            $streamData['title'],
            $streamData['description'] ?? null,
            $streamData['video_url'],
            $streamData['thumbnail_url'] ?? null,
            $streamData['duration'],
            $streamData['viewer_count'] ?? 0,
            $streamData['total_revenue'] ?? 0.00,
            $streamData['streamed_at']
        ]);
    }
    
    public function getVendorSavedStreams($vendorId, $limit = null) {
        $sql = "
            SELECT * FROM {$this->table}
            WHERE vendor_id = ?
            ORDER BY saved_at DESC
        ";
        if ($limit) {
            $sql .= " LIMIT $limit";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$vendorId]);
        return $stmt->fetchAll();
    }
    
    public function getSavedStreamById($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function deleteSavedStream($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }
}

class StreamInteraction extends BaseModel {
    protected $table = 'stream_interactions';
    
    public function addInteraction($streamId, $userId, $type, $commentText = null) {
        if (in_array($type, ['like', 'dislike'])) {
            // Remove previous like/dislike
            $stmt = $this->db->prepare("
                DELETE FROM {$this->table} 
                WHERE stream_id = ? AND user_id = ? 
                AND interaction_type IN ('like', 'dislike')
            ");
            $stmt->execute([$streamId, $userId]);
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} 
            (stream_id, user_id, interaction_type, comment_text)
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$streamId, $userId, $type, $commentText]);
    }
    
    public function removeInteraction($streamId, $userId, $type) {
        $stmt = $this->db->prepare("
            DELETE FROM {$this->table} 
            WHERE stream_id = ? AND user_id = ? AND interaction_type = ?
        ");
        return $stmt->execute([$streamId, $userId, $type]);
    }
    
    public function getStreamComments($streamId, $limit = 100) {
        $sql = "
            SELECT si.*, u.username, u.email
            FROM {$this->table} si
            LEFT JOIN users u ON si.user_id = u.id
            WHERE si.stream_id = ? AND si.interaction_type = 'comment'
            ORDER BY si.created_at DESC
        ";
        if ($limit) {
            $sql .= " LIMIT $limit";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$streamId]);
        return $stmt->fetchAll();
    }
    
    public function getUserInteraction($streamId, $userId) {
        $stmt = $this->db->prepare("
            SELECT interaction_type 
            FROM {$this->table} 
            WHERE stream_id = ? AND user_id = ? 
            AND interaction_type IN ('like', 'dislike')
        ");
        $stmt->execute([$streamId, $userId]);
        $result = $stmt->fetch();
        return $result ? $result['interaction_type'] : null;
    }
}

class StreamViewer extends BaseModel {
    protected $table = 'stream_viewers';
    
    public function addViewer($streamId, $userId = null, $sessionId = null, $ipAddress = null, $userAgent = null) {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} 
            (stream_id, user_id, session_id, ip_address, user_agent, joined_at, is_active)
            VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP, 1)
        ");
        return $stmt->execute([$streamId, $userId, $sessionId, $ipAddress, $userAgent]);
    }
    
    public function markViewerLeft($viewerId) {
        $stmt = $this->db->prepare("
            UPDATE {$this->table} 
            SET is_active = 0, left_at = CURRENT_TIMESTAMP,
                watch_duration = TIMESTAMPDIFF(SECOND, joined_at, CURRENT_TIMESTAMP)
            WHERE id = ?
        ");
        return $stmt->execute([$viewerId]);
    }
    
    public function getActiveViewers($streamId) {
        $sql = "
            SELECT sv.*, u.username, u.email
            FROM {$this->table} sv
            LEFT JOIN users u ON sv.user_id = u.id
            WHERE sv.stream_id = ? AND sv.is_active = 1
            ORDER BY sv.joined_at ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$streamId]);
        return $stmt->fetchAll();
    }
    
    public function cleanupInactiveViewers($streamId, $inactiveMinutes = 5) {
        $stmt = $this->db->prepare("
            UPDATE {$this->table} 
            SET is_active = 0, left_at = CURRENT_TIMESTAMP,
                watch_duration = TIMESTAMPDIFF(SECOND, joined_at, CURRENT_TIMESTAMP)
            WHERE stream_id = ? AND is_active = 1 
            AND joined_at < DATE_SUB(NOW(), INTERVAL ? MINUTE)
        ");
        return $stmt->execute([$streamId, $inactiveMinutes]);
    }
}

class StreamOrder extends BaseModel {
    protected $table = 'stream_orders';
    
    public function recordStreamOrder($streamId, $orderId, $productId, $vendorId, $amount) {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} 
            (stream_id, order_id, product_id, vendor_id, amount)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$streamId, $orderId, $productId, $vendorId, $amount]);
    }
    
    public function getStreamOrders($streamId) {
        $sql = "
            SELECT so.*, p.name as product_name, o.status as order_status, u.username
            FROM {$this->table} so
            JOIN orders o ON so.order_id = o.id
            JOIN products p ON so.product_id = p.id
            LEFT JOIN users u ON o.user_id = u.id
            WHERE so.stream_id = ?
            ORDER BY so.created_at DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$streamId]);
        return $stmt->fetchAll();
    }
}
?>