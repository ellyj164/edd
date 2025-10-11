<?php
/**
 * Seed Categories - 80+ comprehensive product categories
 */

require_once __DIR__ . '/../includes/init.php';

try {
    $db = Database::getInstance();
    
    // Begin transaction
    $db->beginTransaction();
    
    // Categories with hierarchical structure
    $categories = [
        // Electronics & Technology (25 categories)
        ['name' => 'Electronics & Technology', 'parent_id' => null, 'description' => 'Electronic devices and technology products'],
        ['name' => 'Smartphones & Accessories', 'parent_id' => 1, 'description' => 'Mobile phones, cases, chargers, and accessories'],
        ['name' => 'Computers & Laptops', 'parent_id' => 1, 'description' => 'Desktop computers, laptops, and computer accessories'],
        ['name' => 'Gaming & Consoles', 'parent_id' => 1, 'description' => 'Video game consoles, games, and gaming accessories'],
        ['name' => 'Audio & Headphones', 'parent_id' => 1, 'description' => 'Speakers, headphones, earbuds, and audio equipment'],
        ['name' => 'Smart Home & IoT', 'parent_id' => 1, 'description' => 'Smart home devices, IoT products, and automation'],
        ['name' => 'Cameras & Photography', 'parent_id' => 1, 'description' => 'Digital cameras, lenses, and photography equipment'],
        ['name' => 'Tablets & E-readers', 'parent_id' => 1, 'description' => 'Tablets, e-readers, and digital reading devices'],
        ['name' => 'Wearable Technology', 'parent_id' => 1, 'description' => 'Smartwatches, fitness trackers, and wearables'],
        ['name' => 'Home Appliances', 'parent_id' => 1, 'description' => 'Kitchen appliances, vacuum cleaners, and home electronics'],
        ['name' => 'TV & Home Theater', 'parent_id' => 1, 'description' => 'Televisions, projectors, and home entertainment systems'],
        ['name' => 'Computer Components', 'parent_id' => 1, 'description' => 'CPUs, GPUs, RAM, and computer parts'],
        ['name' => 'Networking Equipment', 'parent_id' => 1, 'description' => 'Routers, modems, switches, and networking gear'],
        ['name' => 'Printers & Scanners', 'parent_id' => 1, 'description' => 'Printers, scanners, and office electronics'],
        ['name' => 'Drones & RC', 'parent_id' => 1, 'description' => 'Drones, remote control devices, and accessories'],
        
        // Fashion & Apparel (15 categories)
        ['name' => 'Fashion & Apparel', 'parent_id' => null, 'description' => 'Clothing, shoes, and fashion accessories'],
        ['name' => 'Men\'s Clothing', 'parent_id' => 16, 'description' => 'Men\'s shirts, pants, suits, and casual wear'],
        ['name' => 'Women\'s Clothing', 'parent_id' => 16, 'description' => 'Women\'s dresses, tops, bottoms, and formal wear'],
        ['name' => 'Kids & Baby Clothing', 'parent_id' => 16, 'description' => 'Children\'s and baby clothing and accessories'],
        ['name' => 'Shoes & Footwear', 'parent_id' => 16, 'description' => 'Athletic shoes, dress shoes, boots, and sandals'],
        ['name' => 'Bags & Accessories', 'parent_id' => 16, 'description' => 'Handbags, backpacks, jewelry, and fashion accessories'],
        ['name' => 'Watches & Jewelry', 'parent_id' => 16, 'description' => 'Watches, necklaces, rings, and fine jewelry'],
        ['name' => 'Swimwear & Beachwear', 'parent_id' => 16, 'description' => 'Swimsuits, bikinis, and beach accessories'],
        ['name' => 'Underwear & Lingerie', 'parent_id' => 16, 'description' => 'Undergarments, sleepwear, and intimate apparel'],
        ['name' => 'Sunglasses & Eyewear', 'parent_id' => 16, 'description' => 'Sunglasses, reading glasses, and eyewear accessories'],
        ['name' => 'Hats & Caps', 'parent_id' => 16, 'description' => 'Baseball caps, beanies, fedoras, and headwear'],
        ['name' => 'Belts & Suspenders', 'parent_id' => 16, 'description' => 'Leather belts, fabric belts, and suspenders'],
        ['name' => 'Gloves & Mittens', 'parent_id' => 16, 'description' => 'Winter gloves, work gloves, and mittens'],
        ['name' => 'Scarves & Wraps', 'parent_id' => 16, 'description' => 'Fashion scarves, shawls, and wraps'],
        ['name' => 'Socks & Hosiery', 'parent_id' => 16, 'description' => 'Socks, stockings, and hosiery products'],
        
        // Home & Garden (15 categories)
        ['name' => 'Home & Garden', 'parent_id' => null, 'description' => 'Home improvement, furniture, and garden supplies'],
        ['name' => 'Furniture & Decor', 'parent_id' => 32, 'description' => 'Living room, bedroom, and office furniture'],
        ['name' => 'Kitchen & Dining', 'parent_id' => 32, 'description' => 'Cookware, appliances, and dining accessories'],
        ['name' => 'Bedding & Bath', 'parent_id' => 32, 'description' => 'Bed sheets, towels, and bathroom accessories'],
        ['name' => 'Tools & Hardware', 'parent_id' => 32, 'description' => 'Power tools, hand tools, and hardware supplies'],
        ['name' => 'Garden & Outdoor', 'parent_id' => 32, 'description' => 'Gardening tools, plants, and outdoor furniture'],
        ['name' => 'Home Security', 'parent_id' => 32, 'description' => 'Security cameras, alarms, and safety equipment'],
        ['name' => 'Lighting & Electrical', 'parent_id' => 32, 'description' => 'Light fixtures, bulbs, and electrical supplies'],
        ['name' => 'Flooring & Rugs', 'parent_id' => 32, 'description' => 'Carpets, rugs, laminate, and flooring materials'],
        ['name' => 'Storage & Organization', 'parent_id' => 32, 'description' => 'Storage boxes, shelving, and organizing solutions'],
        ['name' => 'Window Treatments', 'parent_id' => 32, 'description' => 'Curtains, blinds, and window coverings'],
        ['name' => 'Home Fragrance', 'parent_id' => 32, 'description' => 'Candles, air fresheners, and home scents'],
        ['name' => 'Cleaning Supplies', 'parent_id' => 32, 'description' => 'Cleaning products, vacuums, and maintenance tools'],
        ['name' => 'Paint & Wall Covering', 'parent_id' => 32, 'description' => 'Paint, wallpaper, and wall decoration supplies'],
        ['name' => 'Outdoor Living', 'parent_id' => 32, 'description' => 'Patio furniture, grills, and outdoor equipment'],
        
        // Sports & Outdoors (10 categories) 
        ['name' => 'Sports & Outdoors', 'parent_id' => null, 'description' => 'Sports equipment and outdoor recreation'],
        ['name' => 'Fitness & Exercise', 'parent_id' => 47, 'description' => 'Gym equipment, yoga mats, and fitness accessories'],
        ['name' => 'Team Sports', 'parent_id' => 47, 'description' => 'Football, basketball, soccer, and team sport equipment'],
        ['name' => 'Outdoor Recreation', 'parent_id' => 47, 'description' => 'Camping, hiking, and outdoor adventure gear'],
        ['name' => 'Water Sports', 'parent_id' => 47, 'description' => 'Swimming, surfing, and water sport equipment'],
        ['name' => 'Winter Sports', 'parent_id' => 47, 'description' => 'Skiing, snowboarding, and winter sport gear'],
        ['name' => 'Cycling & Biking', 'parent_id' => 47, 'description' => 'Bicycles, bike accessories, and cycling gear'],
        ['name' => 'Hunting & Fishing', 'parent_id' => 47, 'description' => 'Fishing rods, tackle, hunting equipment'],
        ['name' => 'Golf Equipment', 'parent_id' => 47, 'description' => 'Golf clubs, balls, bags, and golf accessories'],
        ['name' => 'Tennis & Racquet Sports', 'parent_id' => 47, 'description' => 'Tennis rackets, badminton, and racquet sports'],
        
        // Health & Beauty (8 categories)
        ['name' => 'Health & Beauty', 'parent_id' => null, 'description' => 'Personal care, beauty, and health products'],
        ['name' => 'Skincare & Cosmetics', 'parent_id' => 56, 'description' => 'Skincare products, makeup, and beauty tools'],
        ['name' => 'Hair Care & Styling', 'parent_id' => 56, 'description' => 'Shampoo, conditioner, and hair styling products'],
        ['name' => 'Personal Care', 'parent_id' => 56, 'description' => 'Toiletries, oral care, and personal hygiene'],
        ['name' => 'Health & Wellness', 'parent_id' => 56, 'description' => 'Vitamins, supplements, and wellness products'],
        ['name' => 'Fragrances', 'parent_id' => 56, 'description' => 'Perfumes, colognes, and body sprays'],
        ['name' => 'Medical Equipment', 'parent_id' => 56, 'description' => 'Blood pressure monitors, thermometers, medical aids'],
        ['name' => 'Massage & Relaxation', 'parent_id' => 56, 'description' => 'Massage chairs, aromatherapy, relaxation products'],
        
        // Automotive (6 categories)
        ['name' => 'Automotive', 'parent_id' => null, 'description' => 'Car parts, accessories, and automotive supplies'],
        ['name' => 'Car Parts & Accessories', 'parent_id' => 64, 'description' => 'Replacement parts and car accessories'],
        ['name' => 'Motorcycle & ATV', 'parent_id' => 64, 'description' => 'Motorcycle parts and ATV accessories'],
        ['name' => 'Car Care & Maintenance', 'parent_id' => 64, 'description' => 'Car cleaning, oil, and maintenance supplies'],
        ['name' => 'Car Electronics', 'parent_id' => 64, 'description' => 'Car stereos, GPS, and electronic accessories'],
        ['name' => 'Tires & Wheels', 'parent_id' => 64, 'description' => 'Car tires, rims, and wheel accessories'],
        
        // Books & Media (5 categories)
        ['name' => 'Books & Media', 'parent_id' => null, 'description' => 'Books, movies, music, and educational content'],
        ['name' => 'Books & Literature', 'parent_id' => 70, 'description' => 'Fiction, non-fiction, and educational books'],
        ['name' => 'Movies & TV Shows', 'parent_id' => 70, 'description' => 'DVDs, Blu-rays, and digital media'],
        ['name' => 'Music & Vinyl', 'parent_id' => 70, 'description' => 'CDs, vinyl records, and music accessories'],
        ['name' => 'Educational & Textbooks', 'parent_id' => 70, 'description' => 'Textbooks, study guides, and educational materials'],
        
        // Toys & Games (5 categories)
        ['name' => 'Toys & Games', 'parent_id' => null, 'description' => 'Toys, games, and entertainment for all ages'],
        ['name' => 'Action Figures & Collectibles', 'parent_id' => 75, 'description' => 'Action figures, collectibles, and memorabilia'],
        ['name' => 'Board Games & Puzzles', 'parent_id' => 75, 'description' => 'Board games, card games, and puzzles'],
        ['name' => 'Educational Toys', 'parent_id' => 75, 'description' => 'Learning toys and educational games'],
        ['name' => 'Remote Control & Drones', 'parent_id' => 75, 'description' => 'RC cars, drones, and remote control toys'],
        
        // Food & Beverages (4 categories)
        ['name' => 'Food & Beverages', 'parent_id' => null, 'description' => 'Food, beverages, and culinary products'],
        ['name' => 'Gourmet & Specialty Foods', 'parent_id' => 80, 'description' => 'Artisanal and specialty food products'],
        ['name' => 'Beverages & Drinks', 'parent_id' => 80, 'description' => 'Coffee, tea, soft drinks, and specialty beverages'],
        ['name' => 'Snacks & Candy', 'parent_id' => 80, 'description' => 'Snack foods, candy, and confectionery'],
        
        // Additional categories to reach 80+
        ['name' => 'Arts & Crafts', 'parent_id' => null, 'description' => 'Art supplies, crafting materials, and creative tools'],
        ['name' => 'Pet Supplies', 'parent_id' => null, 'description' => 'Pet food, toys, and accessories'],
        ['name' => 'Office & Business', 'parent_id' => null, 'description' => 'Office supplies, business equipment, and professional tools']
    ];
    
    // Clear existing categories (optional - only for fresh setup)
    $db->exec("DELETE FROM categories WHERE id > 0");
    $db->exec("ALTER TABLE categories AUTO_INCREMENT = 1");
    
    // Insert categories
    $insertStmt = $db->prepare("
        INSERT INTO categories (name, description, parent_id, slug, status, created_at, updated_at) 
        VALUES (?, ?, ?, ?, 'active', NOW(), NOW())
    ");
    
    foreach ($categories as $category) {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', trim($category['name'])));
        $slug = trim($slug, '-');
        
        $insertStmt->execute([
            $category['name'],
            $category['description'],
            $category['parent_id'],
            $slug
        ]);
    }
    
    // Commit transaction
    $db->commit();
    
    echo "✅ Successfully populated " . count($categories) . " categories!\n";
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollback();
    }
    echo "❌ Error populating categories: " . $e->getMessage() . "\n";
}
?>