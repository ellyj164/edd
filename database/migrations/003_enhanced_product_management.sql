-- Enhanced Product Management Database Schema
-- This migration adds comprehensive product management features

-- Create brands table if not exists
CREATE TABLE IF NOT EXISTS brands (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(160) NOT NULL UNIQUE,
    description TEXT,
    logo_path VARCHAR(255),
    website_url VARCHAR(255),
    is_active INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create product_media table for comprehensive media management
CREATE TABLE IF NOT EXISTS product_media (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    media_type VARCHAR(20) DEFAULT 'image', -- 'image', 'video', '360_image'
    file_path VARCHAR(500),
    thumbnail_path VARCHAR(500),
    alt_text VARCHAR(255),
    title VARCHAR(255),
    description TEXT,
    youtube_url VARCHAR(500),
    is_primary INTEGER DEFAULT 0,
    is_thumbnail INTEGER DEFAULT 0,
    sort_order INTEGER DEFAULT 0,
    file_size INTEGER,
    mime_type VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Create product_attributes table for custom attributes
CREATE TABLE IF NOT EXISTS product_attributes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    attribute_name VARCHAR(100) NOT NULL,
    attribute_value TEXT NOT NULL,
    attribute_type VARCHAR(20) DEFAULT 'text', -- 'text', 'number', 'boolean', 'date', 'json'
    is_searchable INTEGER DEFAULT 0,
    display_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Create product_shipping table for shipping configuration
CREATE TABLE IF NOT EXISTS product_shipping (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    weight DECIMAL(10,3),
    length DECIMAL(10,2),
    width DECIMAL(10,2),
    height DECIMAL(10,2),
    shipping_class VARCHAR(50),
    handling_time INTEGER DEFAULT 1,
    free_shipping INTEGER DEFAULT 0,
    shipping_rules TEXT, -- JSON for complex rules
    hs_code VARCHAR(20),
    country_of_origin VARCHAR(2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Create product_seo table for SEO optimization
CREATE TABLE IF NOT EXISTS product_seo (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    meta_title VARCHAR(60),
    meta_description VARCHAR(160),
    meta_keywords TEXT,
    focus_keyword VARCHAR(100),
    canonical_url VARCHAR(500),
    og_title VARCHAR(60),
    og_description VARCHAR(160),
    og_image VARCHAR(500),
    twitter_title VARCHAR(60),
    twitter_description VARCHAR(160),
    schema_markup TEXT, -- JSON-LD structured data
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Create product_pricing table for advanced pricing
CREATE TABLE IF NOT EXISTS product_pricing (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    sale_price DECIMAL(10,2),
    sale_start_date DATETIME,
    sale_end_date DATETIME,
    bulk_pricing TEXT, -- JSON for bulk pricing rules
    tier_pricing TEXT, -- JSON for tier pricing
    currency_code VARCHAR(3) DEFAULT 'USD',
    tax_class VARCHAR(50),
    margin_percentage DECIMAL(5,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Create product_inventory table for advanced inventory management
CREATE TABLE IF NOT EXISTS product_inventory (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    sku VARCHAR(100),
    stock_quantity INTEGER DEFAULT 0,
    reserved_quantity INTEGER DEFAULT 0,
    low_stock_threshold INTEGER DEFAULT 5,
    out_of_stock_threshold INTEGER DEFAULT 0,
    backorder_limit INTEGER,
    reorder_point INTEGER,
    reorder_quantity INTEGER,
    location VARCHAR(100),
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Create product_certificates table for compliance documents
CREATE TABLE IF NOT EXISTS product_certificates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    certificate_type VARCHAR(100),
    certificate_name VARCHAR(255),
    file_path VARCHAR(500),
    issue_date DATE,
    expiry_date DATE,
    issuing_authority VARCHAR(255),
    certificate_number VARCHAR(100),
    is_active INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Create product_relations table for cross-sell and upsell
CREATE TABLE IF NOT EXISTS product_relations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    related_product_id INTEGER NOT NULL,
    relation_type VARCHAR(20) NOT NULL, -- 'cross_sell', 'upsell', 'related', 'bundle'
    priority INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (related_product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Create product_analytics table for performance tracking
CREATE TABLE IF NOT EXISTS product_analytics (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    metric_date DATE NOT NULL,
    views INTEGER DEFAULT 0,
    clicks INTEGER DEFAULT 0,
    conversions INTEGER DEFAULT 0,
    revenue DECIMAL(15,2) DEFAULT 0.00,
    profit_margin DECIMAL(5,2),
    competitor_price DECIMAL(10,2),
    search_ranking INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Create product_drafts table for draft management
CREATE TABLE IF NOT EXISTS product_drafts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    draft_name VARCHAR(255),
    product_data TEXT, -- JSON serialized product data
    auto_save INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create product_bulk_operations table for bulk management
CREATE TABLE IF NOT EXISTS product_bulk_operations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    operation_type VARCHAR(20) NOT NULL, -- 'import', 'export', 'update', 'delete'
    file_path VARCHAR(500),
    status VARCHAR(20) DEFAULT 'pending', -- 'pending', 'processing', 'completed', 'failed'
    total_records INTEGER DEFAULT 0,
    processed_records INTEGER DEFAULT 0,
    error_records INTEGER DEFAULT 0,
    error_log TEXT,
    started_at TIMESTAMP,
    completed_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Add indexes for performance
CREATE INDEX IF NOT EXISTS idx_product_media_product_id ON product_media(product_id);
CREATE INDEX IF NOT EXISTS idx_product_media_type ON product_media(media_type);
CREATE INDEX IF NOT EXISTS idx_product_attributes_product_id ON product_attributes(product_id);
CREATE INDEX IF NOT EXISTS idx_product_attributes_searchable ON product_attributes(is_searchable);
CREATE INDEX IF NOT EXISTS idx_product_seo_product_id ON product_seo(product_id);
CREATE INDEX IF NOT EXISTS idx_product_pricing_product_id ON product_pricing(product_id);
CREATE INDEX IF NOT EXISTS idx_product_inventory_product_id ON product_inventory(product_id);
CREATE INDEX IF NOT EXISTS idx_product_inventory_sku ON product_inventory(sku);
CREATE INDEX IF NOT EXISTS idx_product_relations_product_id ON product_relations(product_id);
CREATE INDEX IF NOT EXISTS idx_product_relations_type ON product_relations(relation_type);
CREATE INDEX IF NOT EXISTS idx_product_analytics_product_date ON product_analytics(product_id, metric_date);
CREATE INDEX IF NOT EXISTS idx_brands_slug ON brands(slug);
CREATE INDEX IF NOT EXISTS idx_brands_active ON brands(is_active);

-- Update products table with new fields if they don't exist
-- Note: SQLite doesn't support ADD COLUMN IF NOT EXISTS, so we'll use a safer approach

-- Insert default brands
INSERT OR IGNORE INTO brands (id, name, slug, is_active) VALUES 
(1, 'Generic', 'generic', 1),
(2, 'House Brand', 'house-brand', 1),
(3, 'Private Label', 'private-label', 1);