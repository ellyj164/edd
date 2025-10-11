<?php
/**
 * Populate Product Categories
 * Adds comprehensive product categories for the e-commerce platform
 */

require_once __DIR__ . '/../includes/init.php';

try {
    $db = db();
    
    // Comprehensive categories with hierarchical structure
    $categories = [
        // Electronics & Technology
        ['name' => 'Electronics & Technology', 'parent_id' => null, 'description' => 'Electronic devices and technology products'],
        ['name' => 'Smartphones & Accessories', 'parent_id' => 1, 'description' => 'Mobile phones, cases, chargers, and accessories'],
        ['name' => 'Computers & Laptops', 'parent_id' => 1, 'description' => 'Desktop computers, laptops, and computer accessories'],
        ['name' => 'Gaming & Consoles', 'parent_id' => 1, 'description' => 'Video game consoles, games, and gaming accessories'],
        ['name' => 'Audio & Headphones', 'parent_id' => 1, 'description' => 'Speakers, headphones, earbuds, and audio equipment'],
        ['name' => 'Smart Home & IoT', 'parent_id' => 1, 'description' => 'Smart home devices, IoT products, and automation'],
        ['name' => 'Cameras & Photography', 'parent_id' => 1, 'description' => 'Digital cameras, lenses, and photography equipment'],
        
        // Fashion & Apparel
        ['name' => 'Fashion & Apparel', 'parent_id' => null, 'description' => 'Clothing, shoes, and fashion accessories'],
        ['name' => 'Men\'s Clothing', 'parent_id' => 8, 'description' => 'Men\'s shirts, pants, suits, and casual wear'],
        ['name' => 'Women\'s Clothing', 'parent_id' => 8, 'description' => 'Women\'s dresses, tops, bottoms, and formal wear'],
        ['name' => 'Kids & Baby Clothing', 'parent_id' => 8, 'description' => 'Children\'s and baby clothing and accessories'],
        ['name' => 'Shoes & Footwear', 'parent_id' => 8, 'description' => 'Athletic shoes, dress shoes, boots, and sandals'],
        ['name' => 'Bags & Accessories', 'parent_id' => 8, 'description' => 'Handbags, backpacks, jewelry, and fashion accessories'],
        ['name' => 'Watches & Jewelry', 'parent_id' => 8, 'description' => 'Watches, necklaces, rings, and fine jewelry'],
        
        // Home & Garden
        ['name' => 'Home & Garden', 'parent_id' => null, 'description' => 'Home improvement, furniture, and garden supplies'],
        ['name' => 'Furniture & Decor', 'parent_id' => 15, 'description' => 'Living room, bedroom, and office furniture'],
        ['name' => 'Kitchen & Dining', 'parent_id' => 15, 'description' => 'Cookware, appliances, and dining accessories'],
        ['name' => 'Bedding & Bath', 'parent_id' => 15, 'description' => 'Bed sheets, towels, and bathroom accessories'],
        ['name' => 'Tools & Hardware', 'parent_id' => 15, 'description' => 'Power tools, hand tools, and hardware supplies'],
        ['name' => 'Garden & Outdoor', 'parent_id' => 15, 'description' => 'Gardening tools, plants, and outdoor furniture'],
        ['name' => 'Home Security', 'parent_id' => 15, 'description' => 'Security cameras, alarms, and safety equipment'],
        
        // Sports & Outdoors
        ['name' => 'Sports & Outdoors', 'parent_id' => null, 'description' => 'Sports equipment and outdoor recreation'],
        ['name' => 'Fitness & Exercise', 'parent_id' => 22, 'description' => 'Gym equipment, yoga mats, and fitness accessories'],
        ['name' => 'Team Sports', 'parent_id' => 22, 'description' => 'Football, basketball, soccer, and team sport equipment'],
        ['name' => 'Outdoor Recreation', 'parent_id' => 22, 'description' => 'Camping, hiking, and outdoor adventure gear'],
        ['name' => 'Water Sports', 'parent_id' => 22, 'description' => 'Swimming, surfing, and water sport equipment'],
        ['name' => 'Winter Sports', 'parent_id' => 22, 'description' => 'Skiing, snowboarding, and winter sport gear'],
        
        // Health & Beauty
        ['name' => 'Health & Beauty', 'parent_id' => null, 'description' => 'Personal care, beauty, and health products'],
        ['name' => 'Skincare & Cosmetics', 'parent_id' => 28, 'description' => 'Skincare products, makeup, and beauty tools'],
        ['name' => 'Hair Care & Styling', 'parent_id' => 28, 'description' => 'Shampoo, conditioner, and hair styling products'],
        ['name' => 'Personal Care', 'parent_id' => 28, 'description' => 'Toiletries, oral care, and personal hygiene'],
        ['name' => 'Health & Wellness', 'parent_id' => 28, 'description' => 'Vitamins, supplements, and wellness products'],
        ['name' => 'Fragrances', 'parent_id' => 28, 'description' => 'Perfumes, colognes, and body sprays'],
        
        // Automotive
        ['name' => 'Automotive', 'parent_id' => null, 'description' => 'Car parts, accessories, and automotive supplies'],
        ['name' => 'Car Parts & Accessories', 'parent_id' => 34, 'description' => 'Replacement parts and car accessories'],
        ['name' => 'Motorcycle & ATV', 'parent_id' => 34, 'description' => 'Motorcycle parts and ATV accessories'],
        ['name' => 'Car Care & Maintenance', 'parent_id' => 34, 'description' => 'Car cleaning, oil, and maintenance supplies'],
        ['name' => 'Car Electronics', 'parent_id' => 34, 'description' => 'Car stereos, GPS, and electronic accessories'],
        
        // Books & Media
        ['name' => 'Books & Media', 'parent_id' => null, 'description' => 'Books, movies, music, and educational content'],
        ['name' => 'Books & Literature', 'parent_id' => 39, 'description' => 'Fiction, non-fiction, and educational books'],
        ['name' => 'Movies & TV Shows', 'parent_id' => 39, 'description' => 'DVDs, Blu-rays, and digital media'],
        ['name' => 'Music & Vinyl', 'parent_id' => 39, 'description' => 'CDs, vinyl records, and music accessories'],
        ['name' => 'Educational & Textbooks', 'parent_id' => 39, 'description' => 'Textbooks, study guides, and educational materials'],
        
        // Toys & Games
        ['name' => 'Toys & Games', 'parent_id' => null, 'description' => 'Toys, games, and entertainment for all ages'],
        ['name' => 'Action Figures & Collectibles', 'parent_id' => 44, 'description' => 'Action figures, collectibles, and memorabilia'],
        ['name' => 'Board Games & Puzzles', 'parent_id' => 44, 'description' => 'Board games, card games, and puzzles'],
        ['name' => 'Educational Toys', 'parent_id' => 44, 'description' => 'Learning toys and educational games'],
        ['name' => 'Remote Control & Drones', 'parent_id' => 44, 'description' => 'RC cars, drones, and remote control toys'],
        
        // Food & Beverages
        ['name' => 'Food & Beverages', 'parent_id' => null, 'description' => 'Food, beverages, and culinary products'],
        ['name' => 'Gourmet & Specialty Foods', 'parent_id' => 49, 'description' => 'Artisanal and specialty food products'],
        ['name' => 'Beverages & Drinks', 'parent_id' => 49, 'description' => 'Coffee, tea, soft drinks, and specialty beverages'],
        ['name' => 'Snacks & Candy', 'parent_id' => 49, 'description' => 'Snack foods, candy, and confectionery'],
        ['name' => 'Organic & Natural', 'parent_id' => 49, 'description' => 'Organic and natural food products'],
        
        // Arts & Crafts
        ['name' => 'Arts & Crafts', 'parent_id' => null, 'description' => 'Art supplies, crafting materials, and creative tools'],
        ['name' => 'Drawing & Painting', 'parent_id' => 54, 'description' => 'Paints, brushes, canvases, and drawing supplies'],
        ['name' => 'Crafting Supplies', 'parent_id' => 54, 'description' => 'Crafting materials, tools, and DIY supplies'],
        ['name' => 'Sewing & Fabric', 'parent_id' => 54, 'description' => 'Fabrics, sewing machines, and textile supplies'],
        ['name' => 'Musical Instruments', 'parent_id' => 54, 'description' => 'Guitars, keyboards, drums, and musical accessories'],
        
        // Pet Supplies
        ['name' => 'Pet Supplies', 'parent_id' => null, 'description' => 'Pet food, toys, and accessories'],
        ['name' => 'Dog Supplies', 'parent_id' => 59, 'description' => 'Dog food, toys, beds, and accessories'],
        ['name' => 'Cat Supplies', 'parent_id' => 59, 'description' => 'Cat food, litter, toys, and accessories'],
        ['name' => 'Bird & Small Pet Supplies', 'parent_id' => 59, 'description' => 'Supplies for birds, rabbits, and small pets'],
        ['name' => 'Aquarium & Fish Supplies', 'parent_id' => 59, 'description' => 'Fish tanks, fish food, and aquarium accessories'],
        
        // Office & Business
        ['name' => 'Office & Business', 'parent_id' => null, 'description' => 'Office supplies, business equipment, and professional tools'],
        ['name' => 'Office Supplies', 'parent_id' => 64, 'description' => 'Pens, paper, folders, and office essentials'],
        ['name' => 'Business Equipment', 'parent_id' => 64, 'description' => 'Printers, scanners, and office equipment'],
        ['name' => 'Professional Tools', 'parent_id' => 64, 'description' => 'Specialized tools for professional use'],
    ];
    
    // Begin transaction
    $db->beginTransaction();
    
    // Clear existing categories (optional - only for fresh setup)
    // $db->exec("DELETE FROM categories WHERE id > 0");
    
    // Insert categories
    $insertStmt = $db->prepare("
        INSERT INTO categories (name, description, parent_id, slug, status, created_at, updated_at) 
        VALUES (?, ?, ?, ?, 'active', NOW(), NOW())
    ");
    
    foreach ($categories as $index => $category) {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', trim($category['name'])));
        $slug = trim($slug, '-');
        
        // Adjust parent_id if needed (since we're inserting sequentially)
        $parentId = $category['parent_id'];
        if ($parentId !== null) {
            $parentId = $index; // This assumes sequential insertion, might need adjustment
        }
        
        $insertStmt->execute([
            $category['name'],
            $category['description'],
            $category['parent_id'],
            $slug
        ]);
    }
    
    // Commit transaction
    $db->commit();
    
    echo "Successfully populated " . count($categories) . " categories!\n";
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollback();
    }
    echo "Error populating categories: " . $e->getMessage() . "\n";
}
?>