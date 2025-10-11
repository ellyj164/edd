<?php
/**
 * Wallet Service
 * Handles wallet operations, transfers, and balance management
 */

class WalletService {
    private $db;
    
    public function __construct($db = null) {
        $this->db = $db ?? db();
    }
    
    /**
     * Get or create user wallet
     */
    public function getWallet($userId, $currency = 'USD') {
        $stmt = $this->db->prepare("
            SELECT * FROM wallets WHERE user_id = ? AND currency = ?
        ");
        $stmt->execute([$userId, $currency]);
        $wallet = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$wallet) {
            // Create wallet
            $stmt = $this->db->prepare("
                INSERT INTO wallets (user_id, balance, currency, created_at)
                VALUES (?, 0.00, ?, NOW())
            ");
            $stmt->execute([$userId, $currency]);
            
            return $this->getWallet($userId, $currency);
        }
        
        return $wallet;
    }
    
    /**
     * Credit wallet
     */
    public function credit($userId, $amount, $reference = null, $description = null, $meta = null) {
        if ($amount <= 0) {
            throw new Exception('Amount must be positive');
        }
        
        $this->db->beginTransaction();
        try {
            $wallet = $this->getWallet($userId);
            $newBalance = $wallet['balance'] + $amount;
            
            // Update balance
            $stmt = $this->db->prepare("
                UPDATE wallets SET balance = ?, updated_at = NOW() WHERE id = ?
            ");
            $stmt->execute([$newBalance, $wallet['id']]);
            
            // Record transaction
            $stmt = $this->db->prepare("
                INSERT INTO wallet_transactions 
                (user_id, type, amount, balance_after, reference, description, meta, created_at)
                VALUES (?, 'credit', ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $userId, 
                $amount, 
                $newBalance, 
                $reference, 
                $description, 
                $meta ? json_encode($meta) : null
            ]);
            
            $this->db->commit();
            return $newBalance;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Debit wallet
     */
    public function debit($userId, $amount, $reference = null, $description = null, $meta = null) {
        if ($amount <= 0) {
            throw new Exception('Amount must be positive');
        }
        
        $this->db->beginTransaction();
        try {
            $wallet = $this->getWallet($userId);
            
            if ($wallet['balance'] < $amount) {
                throw new Exception('Insufficient wallet balance');
            }
            
            $newBalance = $wallet['balance'] - $amount;
            
            // Update balance
            $stmt = $this->db->prepare("
                UPDATE wallets SET balance = ?, updated_at = NOW() WHERE id = ?
            ");
            $stmt->execute([$newBalance, $wallet['id']]);
            
            // Record transaction
            $stmt = $this->db->prepare("
                INSERT INTO wallet_transactions 
                (user_id, type, amount, balance_after, reference, description, meta, created_at)
                VALUES (?, 'debit', ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $userId, 
                $amount, 
                $newBalance, 
                $reference, 
                $description, 
                $meta ? json_encode($meta) : null
            ]);
            
            $this->db->commit();
            return $newBalance;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Transfer between users
     */
    public function transfer($fromUserId, $toUserId, $amount, $note = null) {
        if ($amount <= 0) {
            throw new Exception('Amount must be positive');
        }
        
        if ($fromUserId == $toUserId) {
            throw new Exception('Cannot transfer to yourself');
        }
        
        // Verify recipient exists
        $user = new User();
        $recipient = $user->find($toUserId);
        if (!$recipient) {
            throw new Exception('Recipient not found');
        }
        
        $this->db->beginTransaction();
        try {
            // Debit sender
            $this->debit($fromUserId, $amount, "transfer_to_{$toUserId}", $note, [
                'transfer_type' => 'send',
                'recipient_id' => $toUserId
            ]);
            
            // Credit recipient
            $this->credit($toUserId, $amount, "transfer_from_{$fromUserId}", $note, [
                'transfer_type' => 'receive',
                'sender_id' => $fromUserId
            ]);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Get transaction history
     */
    public function getTransactions($userId, $limit = 50, $offset = 0) {
        $stmt = $this->db->prepare("
            SELECT * FROM wallet_transactions 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$userId, $limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
