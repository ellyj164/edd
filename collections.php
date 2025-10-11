<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Collections</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; margin: 0; background-color: #f8f9fa; color: #343a40; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        header { text-align: center; padding: 50px 20px; background-color: #ffffff; border-bottom: 1px solid #dee2e6; }
        header h1 { margin: 0; font-size: 3em; font-weight: 300; }
        header p { font-size: 1.2em; color: #6c757d; max-width: 600px; margin: 10px auto 0; }
        .section { padding: 60px 20px; margin-bottom: 20px; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .section-title { text-align: center; font-size: 2.2em; font-weight: 400; margin-bottom: 40px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; }
        .grid-item, .brand-item, .product-item { border: 1px solid #dee2e6; border-radius: 8px; text-align: center; padding: 20px; transition: transform 0.2s, box-shadow 0.2s; cursor: pointer; }
        .grid-item:hover, .brand-item:hover, .product-item:hover { transform: translateY(-5px); box-shadow: 0 8px 15px rgba(0,0,0,0.1); }
        .grid-item img, .brand-item img, .product-item img { max-width: 100%; height: 150px; object-fit: cover; border-radius: 4px; margin-bottom: 15px; }
        .grid-item h3, .product-item h3 { margin: 10px 0 5px; font-size: 1.4em; font-weight: 500; }
        .grid-item p, .product-item p { margin: 0; color: #6c757d; }
        .product-item .price { font-weight: bold; color: #007bff; margin-top: 10px; font-size: 1.2em; }
        .banner { background-color: #007bff; color: white; padding: 40px; text-align: center; border-radius: 8px; margin-bottom: 20px; }
        .banner h2 { margin: 0 0 10px; font-size: 2em; }
        .banner p { margin: 0; }
        .brand-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 20px; }
        .brand-item img { height: 60px; object-fit: contain; }
        .cta-section { text-align: center; padding: 50px 20px; }
        .cta-button { background-color: #28a745; color: white; border: none; padding: 15px 30px; font-size: 1.2em; border-radius: 50px; cursor: pointer; text-decoration: none; transition: background-color 0.3s; }
        .cta-button:hover { background-color: #218838; }
        .placeholder-note { text-align: center; padding: 20px; background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; border-radius: 8px; }
    </style>
</head>
<body>

    <header>
        <h1>Our Collections</h1>
        <p>Explore curated categories and trending products tailored for you.</p>
    </header>

    <div class="container">

        <!-- Main Category Blocks -->
        <section class="section">
            <h2 class="section-title">Shop by Category</h2>
            <div class="grid">
                <?php
                $categories = [
                    ["name" => "Fashion", "subtitle" => "Clothing, Shoes, Accessories"],
                    ["name" => "Electronics", "subtitle" => "Phones, Laptops, Smart Devices"],
                    ["name" => "Home & Furniture", "subtitle" => "Furniture, Kitchen, Décor"],
                    ["name" => "Beauty & Wellness", "subtitle" => "Skincare, Cosmetics, Health"],
                    ["name" => "Sports & Outdoors", "subtitle" => "Fitness gear, Bikes, Equipment"],
                    ["name" => "Kids & Toys", "subtitle" => "Fun and educational toys"],
                ];
                foreach ($categories as $category) {
                    echo '<div class="grid-item">';
                    echo '<img src="https://via.placeholder.com/300x150.png?text=' . urlencode($category['name']) . '" alt="' . htmlspecialchars($category['name']) . '">';
                    echo '<h3>' . htmlspecialchars($category['name']) . '</h3>';
                    echo '<p>' . htmlspecialchars($category['subtitle']) . '</p>';
                    echo '</div>';
                }
                ?>
            </div>
        </section>

        <!-- Featured Collections -->
        <section class="section">
            <h2 class="section-title">Featured Collections</h2>
            <div class="banner">
                <h2>Back to School Deals</h2>
                <p>Save big on essentials for the new school year!</p>
            </div>
        </section>

        <!-- Featured Products -->
        <section class="section">
            <h2 class="section-title">Featured Products</h2>
            <div class="grid">
                <?php
                $products = [
                    ["name" => "Smart Watch", "price" => "$199.99", "image" => "Smart+Watch"],
                    ["name" => "Wireless Headphones", "price" => "$89.99", "image" => "Headphones"],
                    ["name" => "Modern Sofa", "price" => "$799.00", "image" => "Sofa"],
                    ["name" => "Running Shoes", "price" => "$120.00", "image" => "Shoes"],
                ];
                foreach ($products as $product) {
                    echo '<div class="product-item">';
                    echo '<img src="https://via.placeholder.com/300x150.png?text=' . urlencode($product['image']) . '" alt="' . htmlspecialchars($product['name']) . '">';
                    echo '<h3>' . htmlspecialchars($product['name']) . '</h3>';
                    echo '<p class="price">' . htmlspecialchars($product['price']) . '</p>';
                    echo '</div>';
                }
                ?>
            </div>
        </section>

        <!-- Promotional Banner -->
        <section class="section banner" style="background-color: #dc3545;">
            <h2>Up to 50% off Electronics</h2>
            <p>Limited time offer on select gadgets and devices.</p>
        </section>

        <!-- Shop by Brands -->
        <section class="section">
            <h2 class="section-title">Shop by Brands</h2>
            <div class="brand-grid">
                <?php
                $brands = ["Brand A", "Brand B", "Brand C", "Brand D", "Brand E", "Brand F"];
                foreach ($brands as $brand) {
                    echo '<div class="brand-item">';
                    echo '<img src="https://via.placeholder.com/150x60.png?text=' . urlencode($brand) . '" alt="' . htmlspecialchars($brand) . '">';
                    echo '</div>';
                }
                ?>
            </div>
        </section>

        <!-- Personalized Recommendations -->
        <section class="section">
            <h2 class="section-title">Recommended for You</h2>
            <div class="placeholder-note">
                <p>Future implementation: This section will show personalized suggestions based on your browsing history.</p>
            </div>
        </section>

        <!-- Call to Action -->
        <section class="cta-section">
            <p>Don’t miss out – explore our latest arrivals today!</p>
            <a href="#" class="cta-button">Explore All Products</a>
        </section>

    </div>

</body>
</html>