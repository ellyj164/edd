<?php
/**
 * Seed Brands - 80+ comprehensive product brands
 */

require_once __DIR__ . '/../includes/init.php';

try {
    $db = Database::getInstance();
    
    // Begin transaction
    $db->beginTransaction();
    
    // 80+ Popular brands across different categories
    $brands = [
        // Technology Brands (20)
        ['name' => 'Apple', 'slug' => 'apple', 'description' => 'Premium technology products and devices'],
        ['name' => 'Samsung', 'slug' => 'samsung', 'description' => 'Electronics and mobile device manufacturer'],
        ['name' => 'Google', 'slug' => 'google', 'description' => 'Search, software, and hardware products'],
        ['name' => 'Microsoft', 'slug' => 'microsoft', 'description' => 'Software, cloud services, and devices'],
        ['name' => 'Sony', 'slug' => 'sony', 'description' => 'Electronics, gaming, and entertainment'],
        ['name' => 'LG', 'slug' => 'lg', 'description' => 'Home appliances and electronics'],
        ['name' => 'Dell', 'slug' => 'dell', 'description' => 'Computers and technology solutions'],
        ['name' => 'HP', 'slug' => 'hp', 'description' => 'Computing and printing solutions'],
        ['name' => 'Intel', 'slug' => 'intel', 'description' => 'Semiconductor and computing technology'],
        ['name' => 'AMD', 'slug' => 'amd', 'description' => 'Computer processors and graphics'],
        ['name' => 'NVIDIA', 'slug' => 'nvidia', 'description' => 'Graphics processing and AI technology'],
        ['name' => 'Canon', 'slug' => 'canon', 'description' => 'Cameras, printers, and imaging solutions'],
        ['name' => 'Nikon', 'slug' => 'nikon', 'description' => 'Camera and optical equipment manufacturer'],
        ['name' => 'Bose', 'slug' => 'bose', 'description' => 'Premium audio equipment and speakers'],
        ['name' => 'JBL', 'slug' => 'jbl', 'description' => 'Professional and consumer audio equipment'],
        ['name' => 'Logitech', 'slug' => 'logitech', 'description' => 'Computer peripherals and accessories'],
        ['name' => 'Philips', 'slug' => 'philips', 'description' => 'Health technology and consumer products'],
        ['name' => 'Panasonic', 'slug' => 'panasonic', 'description' => 'Electronics and home appliances'],
        ['name' => 'Xiaomi', 'slug' => 'xiaomi', 'description' => 'Smartphones and smart home products'],
        ['name' => 'OnePlus', 'slug' => 'oneplus', 'description' => 'Premium smartphone manufacturer'],

        // Fashion & Apparel Brands (15)
        ['name' => 'Nike', 'slug' => 'nike', 'description' => 'Athletic footwear and apparel'],
        ['name' => 'Adidas', 'slug' => 'adidas', 'description' => 'Sports clothing and footwear'],
        ['name' => 'Puma', 'slug' => 'puma', 'description' => 'Athletic and casual footwear'],
        ['name' => 'Under Armour', 'slug' => 'under-armour', 'description' => 'Performance athletic apparel'],
        ['name' => 'Levi\'s', 'slug' => 'levis', 'description' => 'Denim jeans and casual clothing'],
        ['name' => 'Calvin Klein', 'slug' => 'calvin-klein', 'description' => 'Fashion and luxury apparel'],
        ['name' => 'Tommy Hilfiger', 'slug' => 'tommy-hilfiger', 'description' => 'Premium fashion and accessories'],
        ['name' => 'Ralph Lauren', 'slug' => 'ralph-lauren', 'description' => 'Luxury fashion and lifestyle'],
        ['name' => 'Gap', 'slug' => 'gap', 'description' => 'Casual clothing and accessories'],
        ['name' => 'H&M', 'slug' => 'hm', 'description' => 'Fast fashion and affordable clothing'],
        ['name' => 'Zara', 'slug' => 'zara', 'description' => 'Contemporary fashion retailer'],
        ['name' => 'Uniqlo', 'slug' => 'uniqlo', 'description' => 'Casual wear and basics'],
        ['name' => 'The North Face', 'slug' => 'the-north-face', 'description' => 'Outdoor clothing and equipment'],
        ['name' => 'Patagonia', 'slug' => 'patagonia', 'description' => 'Outdoor and environmental clothing'],
        ['name' => 'Converse', 'slug' => 'converse', 'description' => 'Classic sneakers and casual footwear'],

        // Home & Garden Brands (10)
        ['name' => 'IKEA', 'slug' => 'ikea', 'description' => 'Furniture and home accessories'],
        ['name' => 'Home Depot', 'slug' => 'home-depot', 'description' => 'Home improvement and hardware'],
        ['name' => 'Lowe\'s', 'slug' => 'lowes', 'description' => 'Home improvement retailer'],
        ['name' => 'KitchenAid', 'slug' => 'kitchenaid', 'description' => 'Kitchen appliances and tools'],
        ['name' => 'Cuisinart', 'slug' => 'cuisinart', 'description' => 'Kitchen appliances and cookware'],
        ['name' => 'Black & Decker', 'slug' => 'black-decker', 'description' => 'Power tools and home products'],
        ['name' => 'DeWalt', 'slug' => 'dewalt', 'description' => 'Professional power tools'],
        ['name' => 'Makita', 'slug' => 'makita', 'description' => 'Power tools and equipment'],
        ['name' => 'Dyson', 'slug' => 'dyson', 'description' => 'Vacuum cleaners and home appliances'],
        ['name' => 'Roomba', 'slug' => 'roomba', 'description' => 'Robotic vacuum cleaners'],

        // Health & Beauty Brands (8)
        ['name' => 'L\'Oréal', 'slug' => 'loreal', 'description' => 'Cosmetics and beauty products'],
        ['name' => 'Maybelline', 'slug' => 'maybelline', 'description' => 'Makeup and cosmetics'],
        ['name' => 'Revlon', 'slug' => 'revlon', 'description' => 'Beauty and personal care products'],
        ['name' => 'Clinique', 'slug' => 'clinique', 'description' => 'Skincare and cosmetics'],
        ['name' => 'Olay', 'slug' => 'olay', 'description' => 'Skincare and anti-aging products'],
        ['name' => 'Neutrogena', 'slug' => 'neutrogena', 'description' => 'Dermatologist-recommended skincare'],
        ['name' => 'Dove', 'slug' => 'dove', 'description' => 'Personal care and beauty products'],
        ['name' => 'Johnson & Johnson', 'slug' => 'johnson-johnson', 'description' => 'Healthcare and consumer products'],

        // Automotive Brands (8)
        ['name' => 'Toyota', 'slug' => 'toyota', 'description' => 'Automotive manufacturer and parts'],
        ['name' => 'Honda', 'slug' => 'honda', 'description' => 'Automotive and motorcycle manufacturer'],
        ['name' => 'Ford', 'slug' => 'ford', 'description' => 'American automotive manufacturer'],
        ['name' => 'BMW', 'slug' => 'bmw', 'description' => 'Luxury automotive manufacturer'],
        ['name' => 'Mercedes-Benz', 'slug' => 'mercedes-benz', 'description' => 'Premium automotive manufacturer'],
        ['name' => 'Audi', 'slug' => 'audi', 'description' => 'Luxury automotive manufacturer'],
        ['name' => 'Bosch', 'slug' => 'bosch', 'description' => 'Automotive parts and technology'],
        ['name' => 'Michelin', 'slug' => 'michelin', 'description' => 'Tire manufacturer'],

        // Sports & Outdoors Brands (6)
        ['name' => 'Wilson', 'slug' => 'wilson', 'description' => 'Sports equipment and gear'],
        ['name' => 'Spalding', 'slug' => 'spalding', 'description' => 'Basketball and sports equipment'],
        ['name' => 'Callaway', 'slug' => 'callaway', 'description' => 'Golf equipment and accessories'],
        ['name' => 'TaylorMade', 'slug' => 'taylormade', 'description' => 'Golf clubs and equipment'],
        ['name' => 'Coleman', 'slug' => 'coleman', 'description' => 'Outdoor and camping equipment'],
        ['name' => 'REI', 'slug' => 'rei', 'description' => 'Outdoor recreation equipment'],

        // Food & Beverage Brands (5)
        ['name' => 'Coca-Cola', 'slug' => 'coca-cola', 'description' => 'Beverages and soft drinks'],
        ['name' => 'Pepsi', 'slug' => 'pepsi', 'description' => 'Soft drinks and beverages'],
        ['name' => 'Nestlé', 'slug' => 'nestle', 'description' => 'Food and beverage products'],
        ['name' => 'Kellogg\'s', 'slug' => 'kelloggs', 'description' => 'Breakfast cereals and snacks'],
        ['name' => 'Starbucks', 'slug' => 'starbucks', 'description' => 'Coffee and beverage products'],

        // Generic/Other Brands (8)
        ['name' => 'Amazon Basics', 'slug' => 'amazon-basics', 'description' => 'Affordable everyday essentials'],
        ['name' => 'Kirkland Signature', 'slug' => 'kirkland-signature', 'description' => 'Costco private label brand'],
        ['name' => 'Great Value', 'slug' => 'great-value', 'description' => 'Walmart private label brand'],
        ['name' => 'Target Brand', 'slug' => 'target-brand', 'description' => 'Target private label products'],
        ['name' => 'Generic Brand', 'slug' => 'generic-brand', 'description' => 'Unbranded or generic products'],
        ['name' => 'Store Brand', 'slug' => 'store-brand', 'description' => 'Retailer private label products'],
        ['name' => 'No Brand', 'slug' => 'no-brand', 'description' => 'Products without specific brand'],
        ['name' => 'Universal', 'slug' => 'universal', 'description' => 'Universal or compatible products']
    ];
    
    // Clear existing brands (optional - only for fresh setup)
    $db->exec("DELETE FROM brands WHERE id > 0");
    $db->exec("ALTER TABLE brands AUTO_INCREMENT = 1");
    
    // Insert brands
    $insertStmt = $db->prepare("
        INSERT INTO brands (name, slug, description, is_active, created_at, updated_at) 
        VALUES (?, ?, ?, 1, NOW(), NOW())
    ");
    
    foreach ($brands as $brand) {
        $insertStmt->execute([
            $brand['name'],
            $brand['slug'],
            $brand['description']
        ]);
    }
    
    // Commit transaction
    $db->commit();
    
    echo "✅ Successfully populated " . count($brands) . " brands!\n";
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollback();
    }
    echo "❌ Error populating brands: " . $e->getMessage() . "\n";
}
?>