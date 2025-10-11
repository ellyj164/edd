-- Migration: Add Popular Brands
-- Created: 2025
-- Description: Populates brands table with 100+ popular brands across various categories

-- Clear existing test brands except Generic Brand
DELETE FROM `brands` WHERE id > 1 AND name IN ('Acme', 'Private Label');

-- Insert popular brands
INSERT INTO `brands` (`name`, `slug`, `description`, `is_active`, `created_at`) VALUES
-- Technology & Electronics
('Apple', 'apple', 'Technology and consumer electronics', 1, NOW()),
('Samsung', 'samsung', 'Electronics and mobile devices', 1, NOW()),
('Sony', 'sony', 'Electronics and entertainment', 1, NOW()),
('LG', 'lg', 'Electronics and appliances', 1, NOW()),
('Dell', 'dell', 'Computers and technology', 1, NOW()),
('HP', 'hp', 'Computers and printers', 1, NOW()),
('Lenovo', 'lenovo', 'Computers and tablets', 1, NOW()),
('Asus', 'asus', 'Computer hardware', 1, NOW()),
('Acer', 'acer', 'Computers and monitors', 1, NOW()),
('Microsoft', 'microsoft', 'Software and hardware', 1, NOW()),
('Google', 'google', 'Technology and services', 1, NOW()),
('Amazon', 'amazon', 'Technology and services', 1, NOW()),
('Panasonic', 'panasonic', 'Electronics and appliances', 1, NOW()),
('Philips', 'philips', 'Electronics and healthcare', 1, NOW()),
('Canon', 'canon', 'Cameras and printers', 1, NOW()),
('Nikon', 'nikon', 'Cameras and optics', 1, NOW()),
('JBL', 'jbl', 'Audio equipment', 1, NOW()),
('Bose', 'bose', 'Audio equipment', 1, NOW()),
('Beats', 'beats', 'Headphones and audio', 1, NOW()),
('Logitech', 'logitech', 'Computer peripherals', 1, NOW()),
('Razer', 'razer', 'Gaming peripherals', 1, NOW()),
('Corsair', 'corsair', 'Gaming and PC components', 1, NOW()),
('Intel', 'intel', 'Processors and technology', 1, NOW()),
('AMD', 'amd', 'Processors and graphics', 1, NOW()),
('NVIDIA', 'nvidia', 'Graphics cards', 1, NOW()),

-- Fashion & Apparel
('Nike', 'nike', 'Athletic wear and footwear', 1, NOW()),
('Adidas', 'adidas', 'Sportswear and footwear', 1, NOW()),
('Puma', 'puma', 'Athletic apparel', 1, NOW()),
('Under Armour', 'under-armour', 'Athletic apparel', 1, NOW()),
('Reebok', 'reebok', 'Athletic footwear and apparel', 1, NOW()),
('New Balance', 'new-balance', 'Athletic footwear', 1, NOW()),
('Levi\'s', 'levis', 'Denim and casual wear', 1, NOW()),
('Gap', 'gap', 'Casual clothing', 1, NOW()),
('H&M', 'hm', 'Fashion retailer', 1, NOW()),
('Zara', 'zara', 'Fashion apparel', 1, NOW()),
('Uniqlo', 'uniqlo', 'Casual wear', 1, NOW()),
('Ralph Lauren', 'ralph-lauren', 'Fashion and lifestyle', 1, NOW()),
('Tommy Hilfiger', 'tommy-hilfiger', 'Fashion apparel', 1, NOW()),
('Calvin Klein', 'calvin-klein', 'Fashion and accessories', 1, NOW()),
('Gucci', 'gucci', 'Luxury fashion', 1, NOW()),
('Prada', 'prada', 'Luxury fashion', 1, NOW()),
('Louis Vuitton', 'louis-vuitton', 'Luxury goods', 1, NOW()),
('Versace', 'versace', 'Luxury fashion', 1, NOW()),
('Burberry', 'burberry', 'Luxury fashion', 1, NOW()),
('Coach', 'coach', 'Leather goods and accessories', 1, NOW()),
('Michael Kors', 'michael-kors', 'Fashion accessories', 1, NOW()),

-- Beauty & Personal Care
('L\'Oréal', 'loreal', 'Beauty and cosmetics', 1, NOW()),
('Estée Lauder', 'estee-lauder', 'Cosmetics and skincare', 1, NOW()),
('MAC', 'mac', 'Cosmetics', 1, NOW()),
('Clinique', 'clinique', 'Skincare and cosmetics', 1, NOW()),
('Lancôme', 'lancome', 'Luxury beauty', 1, NOW()),
('Maybelline', 'maybelline', 'Cosmetics', 1, NOW()),
('Revlon', 'revlon', 'Beauty products', 1, NOW()),
('NYX', 'nyx', 'Cosmetics', 1, NOW()),
('Dove', 'dove', 'Personal care', 1, NOW()),
('Nivea', 'nivea', 'Skincare products', 1, NOW()),
('Olay', 'olay', 'Skincare', 1, NOW()),
('Neutrogena', 'neutrogena', 'Skincare products', 1, NOW()),

-- Home & Kitchen
('KitchenAid', 'kitchenaid', 'Kitchen appliances', 1, NOW()),
('Cuisinart', 'cuisinart', 'Kitchen products', 1, NOW()),
('Ninja', 'ninja', 'Kitchen appliances', 1, NOW()),
('Instant Pot', 'instant-pot', 'Pressure cookers', 1, NOW()),
('Dyson', 'dyson', 'Vacuum cleaners and appliances', 1, NOW()),
('Roomba', 'roomba', 'Robot vacuums', 1, NOW()),
('Shark', 'shark', 'Cleaning products', 1, NOW()),
('Bissell', 'bissell', 'Cleaning equipment', 1, NOW()),
('IKEA', 'ikea', 'Furniture and home goods', 1, NOW()),
('Wayfair', 'wayfair', 'Home furnishings', 1, NOW()),

-- Sports & Outdoors
('The North Face', 'the-north-face', 'Outdoor apparel', 1, NOW()),
('Patagonia', 'patagonia', 'Outdoor clothing', 1, NOW()),
('Columbia', 'columbia', 'Outdoor apparel', 1, NOW()),
('REI', 'rei', 'Outdoor gear', 1, NOW()),
('Yeti', 'yeti', 'Outdoor products', 1, NOW()),
('GoPro', 'gopro', 'Action cameras', 1, NOW()),
('Garmin', 'garmin', 'GPS and fitness devices', 1, NOW()),
('Fitbit', 'fitbit', 'Fitness trackers', 1, NOW()),
('Peloton', 'peloton', 'Fitness equipment', 1, NOW()),
('Wilson', 'wilson', 'Sports equipment', 1, NOW()),
('Spalding', 'spalding', 'Sports balls', 1, NOW()),
('Titleist', 'titleist', 'Golf equipment', 1, NOW()),
('Callaway', 'callaway', 'Golf equipment', 1, NOW()),

-- Automotive
('Bosch', 'bosch', 'Auto parts and tools', 1, NOW()),
('Michelin', 'michelin', 'Tires', 1, NOW()),
('Goodyear', 'goodyear', 'Tires', 1, NOW()),
('Bridgestone', 'bridgestone', 'Tires', 1, NOW()),
('Castrol', 'castrol', 'Motor oil', 1, NOW()),
('Mobil', 'mobil', 'Motor oil', 1, NOW()),

-- Baby & Kids
('Fisher-Price', 'fisher-price', 'Toys and baby products', 1, NOW()),
('Lego', 'lego', 'Building toys', 1, NOW()),
('Mattel', 'mattel', 'Toys', 1, NOW()),
('Hasbro', 'hasbro', 'Toys and games', 1, NOW()),
('Pampers', 'pampers', 'Baby care', 1, NOW()),
('Huggies', 'huggies', 'Baby care', 1, NOW()),
('Graco', 'graco', 'Baby products', 1, NOW()),
('Chicco', 'chicco', 'Baby products', 1, NOW()),

-- Food & Beverage (for grocery sellers)
('Coca-Cola', 'coca-cola', 'Beverages', 1, NOW()),
('Pepsi', 'pepsi', 'Beverages', 1, NOW()),
('Nestlé', 'nestle', 'Food and beverages', 1, NOW()),
('Kraft', 'kraft', 'Food products', 1, NOW()),
('Kellogg\'s', 'kelloggs', 'Cereals and snacks', 1, NOW()),
('General Mills', 'general-mills', 'Food products', 1, NOW()),

-- Tools & Hardware
('DeWalt', 'dewalt', 'Power tools', 1, NOW()),
('Black & Decker', 'black-decker', 'Tools and appliances', 1, NOW()),
('Makita', 'makita', 'Power tools', 1, NOW()),
('Milwaukee', 'milwaukee', 'Power tools', 1, NOW()),
('Stanley', 'stanley', 'Hand tools', 1, NOW()),
('Craftsman', 'craftsman', 'Tools', 1, NOW()),

-- Health & Wellness
('Pfizer', 'pfizer', 'Pharmaceuticals', 1, NOW()),
('Johnson & Johnson', 'johnson-johnson', 'Healthcare products', 1, NOW()),
('Bayer', 'bayer', 'Pharmaceuticals', 1, NOW()),
('Abbott', 'abbott', 'Healthcare', 1, NOW()),
('GNC', 'gnc', 'Nutritional supplements', 1, NOW()),
('Optimum Nutrition', 'optimum-nutrition', 'Sports nutrition', 1, NOW()),

-- Office & School Supplies
('Staples', 'staples', 'Office supplies', 1, NOW()),
('Sharpie', 'sharpie', 'Markers and writing instruments', 1, NOW()),
('Post-it', 'post-it', 'Sticky notes', 1, NOW()),
('Scotch', 'scotch', 'Adhesive products', 1, NOW()),
('Moleskine', 'moleskine', 'Notebooks', 1, NOW()),
('Parker', 'parker', 'Pens', 1, NOW()),

-- Watches & Jewelry
('Rolex', 'rolex', 'Luxury watches', 1, NOW()),
('Omega', 'omega', 'Luxury watches', 1, NOW()),
('Seiko', 'seiko', 'Watches', 1, NOW()),
('Casio', 'casio', 'Watches and electronics', 1, NOW()),
('Fossil', 'fossil', 'Watches and accessories', 1, NOW()),
('Tiffany & Co.', 'tiffany-co', 'Luxury jewelry', 1, NOW()),
('Pandora', 'pandora', 'Jewelry', 1, NOW()),
('Swarovski', 'swarovski', 'Crystal jewelry', 1, NOW())
ON DUPLICATE KEY UPDATE 
  `description` = VALUES(`description`),
  `is_active` = VALUES(`is_active`);
