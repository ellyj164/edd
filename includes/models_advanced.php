<?php
/**
 * Additional Models for eCommerce Platform
 * Handles advanced eCommerce functionality
 */

/**
 * Transaction Model - Payment processing
 */
class TransactionAdvanced extends BaseModel {
    protected $table = 'transactions';
    
    public function getTotalVolume() {
        $stmt = $this->db->prepare("SELECT SUM(amount) FROM {$this->table} WHERE status = 'completed'");
        $stmt->execute();
        return $stmt->fetchColumn() ?: 0;
    }
    
    public function getRecent($limit = 50) {
        $stmt = $this->db->prepare("
            SELECT t.*, o.order_number, u.first_name, u.last_name,
                   CONCAT(u.first_name, ' ', u.last_name) as customer_name
            FROM {$this->table} t
            LEFT JOIN orders o ON t.order_id = o.id
            LEFT JOIN users u ON o.user_id = u.id
            ORDER BY t.created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    public function processRefund($transactionId, $amount, $reason = null) {
        $transaction = $this->find($transactionId);
        if (!$transaction) {
            throw new Exception('Transaction not found');
        }
        
        // Create refund record
        $refundData = [
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'reason' => $reason,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Insert refund and update transaction status
        $this->db->beginTransaction();
        try {
            $this->db->prepare("INSERT INTO refunds (transaction_id, amount, reason, status, created_at) VALUES (?, ?, ?, ?, ?)")
                     ->execute([$transactionId, $amount, $reason, 'pending', date('Y-m-d H:i:s')]);
            
            $this->update($transactionId, ['status' => 'refunded']);
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}

/**
 * PaymentToken Model - Tokenized payment methods
 */
class PaymentToken extends BaseModel {
    protected $table = 'payment_tokens';
    
    public function getUserTokens($userId) {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table} 
            WHERE user_id = ? AND is_active = 1 
            ORDER BY is_default DESC, created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    public function setDefault($tokenId, $userId) {
        $this->db->beginTransaction();
        try {
            // Remove default from all other tokens
            $this->db->prepare("UPDATE {$this->table} SET is_default = 0 WHERE user_id = ?")
                     ->execute([$userId]);
            
            // Set new default
            $this->update($tokenId, ['is_default' => 1]);
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}

/**
 * Wallet Model - Customer credits and wallet
 */
class Wallet extends BaseModel {
    protected $table = 'wallets';
    
    public function getUserWallet($userId, $currency = 'USD') {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table} 
            WHERE user_id = ? AND currency = ?
        ");
        $stmt->execute([$userId, $currency]);
        $wallet = $stmt->fetch();
        
        if (!$wallet) {
            // Create wallet if it doesn't exist
            $this->create([
                'user_id' => $userId,
                'currency' => $currency,
                'balance' => 0.00
            ]);
            return $this->getUserWallet($userId, $currency);
        }
        
        return $wallet;
    }
    
    public function addCredit($userId, $amount, $description = null, $referenceType = null, $referenceId = null) {
        $wallet = $this->getUserWallet($userId);
        $newBalance = $wallet['balance'] + $amount;
        
        $this->db->beginTransaction();
        try {
            // Update wallet balance
            $this->update($wallet['id'], ['balance' => $newBalance]);
            
            // Record transaction
            $this->db->prepare("
                INSERT INTO wallet_transactions (wallet_id, type, amount, balance_after, reference_type, reference_id, description, created_at)
                VALUES (?, 'credit', ?, ?, ?, ?, ?, NOW())
            ")->execute([$wallet['id'], $amount, $newBalance, $referenceType, $referenceId, $description]);
            
            $this->db->commit();
            return $newBalance;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    public function debitCredit($userId, $amount, $description = null, $referenceType = null, $referenceId = null) {
        $wallet = $this->getUserWallet($userId);
        
        if ($wallet['balance'] < $amount) {
            throw new Exception('Insufficient wallet balance');
        }
        
        $newBalance = $wallet['balance'] - $amount;
        
        $this->db->beginTransaction();
        try {
            // Update wallet balance
            $this->update($wallet['id'], ['balance' => $newBalance]);
            
            // Record transaction
            $this->db->prepare("
                INSERT INTO wallet_transactions (wallet_id, type, amount, balance_after, reference_type, reference_id, description, created_at)
                VALUES (?, 'debit', ?, ?, ?, ?, ?, NOW())
            ")->execute([$wallet['id'], $amount, $newBalance, $referenceType, $referenceId, $description]);
            
            $this->db->commit();
            return $newBalance;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}

/**
 * Return Model - Return requests
 */
class Returns extends BaseModel {
    protected $table = 'returns';
    
    public function createReturn($orderId, $userId, $reason, $description = null) {
        $returnNumber = 'RET-' . strtoupper(uniqid());
        
        return $this->create([
            'order_id' => $orderId,
            'user_id' => $userId,
            'return_number' => $returnNumber,
            'reason' => $reason,
            'description' => $description,
            'status' => 'requested'
        ]);
    }
    
    public function getUserReturns($userId, $limit = null) {
        $sql = "
            SELECT r.*, o.order_number, o.total as order_total
            FROM {$this->table} r
            JOIN orders o ON r.order_id = o.id
            WHERE r.user_id = ?
            ORDER BY r.created_at DESC
        ";
        
        if ($limit) {
            $sql .= " LIMIT ?";
        }
        
        $stmt = $this->db->prepare($sql);
        $params = [$userId];
        if ($limit) $params[] = $limit;
        
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function approveReturn($returnId, $refundAmount, $refundMethod = 'original') {
        $this->db->beginTransaction();
        try {
            $this->update($returnId, [
                'status' => 'approved',
                'refund_amount' => $refundAmount,
                'refund_method' => $refundMethod,
                'processed_at' => date('Y-m-d H:i:s')
            ]);
            
            // Create refund record would go here
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}

/**
 * Ticket Model - Customer support tickets
 */
class Ticket extends BaseModel {
    protected $table = 'tickets';
    
    public function createTicket($userId, $subject, $description, $category = 'general', $priority = 'medium') {
        $ticketNumber = 'TCK-' . strtoupper(uniqid());
        
        return $this->create([
            'ticket_number' => $ticketNumber,
            'user_id' => $userId,
            'subject' => $subject,
            'description' => $description,
            'category' => $category,
            'priority' => $priority,
            'status' => 'open'
        ]);
    }
    
    public function getUserTickets($userId, $limit = null) {
        $sql = "
            SELECT t.*, u.first_name, u.last_name
            FROM {$this->table} t
            LEFT JOIN users u ON t.assigned_to = u.id
            WHERE t.user_id = ?
            ORDER BY t.created_at DESC
        ";
        
        if ($limit) {
            $sql .= " LIMIT ?";
        }
        
        $stmt = $this->db->prepare($sql);
        $params = [$userId];
        if ($limit) $params[] = $limit;
        
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function assignTicket($ticketId, $assigneeId) {
        return $this->update($ticketId, [
            'assigned_to' => $assigneeId,
            'status' => 'in_progress'
        ]);
    }
    
    public function resolveTicket($ticketId) {
        return $this->update($ticketId, [
            'status' => 'resolved',
            'resolved_at' => date('Y-m-d H:i:s')
        ]);
    }
}

/**
 * Message Model - Customer/Vendor messaging
 */
class Message extends BaseModel {
    protected $table = 'messages';
    
    public function sendMessage($senderId, $recipientId, $subject, $content, $orderContext = null) {
        return $this->create([
            'sender_id' => $senderId,
            'recipient_id' => $recipientId,
            'subject' => $subject,
            'content' => $content,
            'order_context' => $orderContext,
            'status' => 'sent'
        ]);
    }
    
    public function getUserMessages($userId, $limit = 50) {
        $stmt = $this->db->prepare("
            SELECT m.*, 
                   s.first_name as sender_first_name, s.last_name as sender_last_name,
                   r.first_name as recipient_first_name, r.last_name as recipient_last_name
            FROM {$this->table} m
            JOIN users s ON m.sender_id = s.id
            JOIN users r ON m.recipient_id = r.id
            WHERE m.sender_id = ? OR m.recipient_id = ?
            ORDER BY m.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $userId, $limit]);
        return $stmt->fetchAll();
    }
    
    public function markAsRead($messageId, $userId) {
        $stmt = $this->db->prepare("
            UPDATE {$this->table} 
            SET read_at = NOW() 
            WHERE id = ? AND recipient_id = ? AND read_at IS NULL
        ");
        return $stmt->execute([$messageId, $userId]);
    }
    
    public function getUnreadCount($userId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM {$this->table} 
            WHERE recipient_id = ? AND read_at IS NULL
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }
}

/**
 * Settlement Model - Vendor payouts
 */
class Settlement extends BaseModel {
    protected $table = 'settlements';
    
    public function generateSettlement($vendorId, $periodStart, $periodEnd) {
        $settlementNumber = 'SET-' . strtoupper(uniqid());
        
        // Calculate settlement amounts
        $stats = $this->calculateSettlementStats($vendorId, $periodStart, $periodEnd);
        
        return $this->create([
            'vendor_id' => $vendorId,
            'settlement_number' => $settlementNumber,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'gross_sales' => $stats['gross_sales'],
            'commission_rate' => $stats['commission_rate'],
            'commission_amount' => $stats['commission_amount'],
            'fees' => $stats['fees'],
            'net_amount' => $stats['net_amount'],
            'status' => 'pending'
        ]);
    }
    
    private function calculateSettlementStats($vendorId, $periodStart, $periodEnd) {
        $stmt = $this->db->prepare("
            SELECT SUM(oi.price * oi.quantity) as gross_sales,
                   COUNT(DISTINCT o.id) as order_count
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            JOIN products p ON oi.product_id = p.id
            WHERE p.vendor_id = ? 
            AND o.placed_at BETWEEN ? AND ?
            AND o.status IN ('completed', 'delivered')
        ");
        $stmt->execute([$vendorId, $periodStart, $periodEnd]);
        $result = $stmt->fetch();
        
        $grossSales = $result['gross_sales'] ?: 0;
        $commissionRate = 5.00; // 5% default commission
        $commissionAmount = $grossSales * ($commissionRate / 100);
        $fees = 0; // Additional fees would be calculated here
        $netAmount = $grossSales - $commissionAmount - $fees;
        
        return [
            'gross_sales' => $grossSales,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount,
            'fees' => $fees,
            'net_amount' => $netAmount
        ];
    }
    
    public function getVendorSettlements($vendorId, $limit = null) {
        $sql = "
            SELECT * FROM {$this->table} 
            WHERE vendor_id = ? 
            ORDER BY created_at DESC
        ";
        
        if ($limit) {
            $sql .= " LIMIT ?";
        }
        
        $stmt = $this->db->prepare($sql);
        $params = [$vendorId];
        if ($limit) $params[] = $limit;
        
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}