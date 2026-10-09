-- ============================================================
--  Mpulule AgroMarket — Full Database Schema + Seed Data
--  Gold Theme | PHP 8+ | MySQL 5.7+
--  ALL display text stored in DB — no hardcoded strings
-- ============================================================

CREATE DATABASE IF NOT EXISTS mpulule_agro
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mpulule_agro;

-- ──────────────────────────────────────────────────────────
--  SETTINGS — every display text, contact, config value
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS settings (
    `key`   VARCHAR(120) PRIMARY KEY,
    `value` TEXT DEFAULT NULL
) ENGINE=InnoDB;

INSERT INTO settings (`key`,`value`) VALUES
('site_name',           'Mpulule AgroMarket'),
('site_tagline',        'Tanzania\'s premier agricultural marketplace connecting farmers and agribusinesses with quality crops, fertilizers, equipment, and financing.'),
('hero_title',          'Tanzania\'s #1 Agricultural Marketplace'),
('hero_subtitle',       'Browse quality crops, fertilizers & equipment. Order from trusted suppliers. Apply for agricultural loans.'),
('contact_email',       'mpululefransisco@gmail.com'),
('whatsapp_number',     '+255700000000'),
('contact_address',     'Tanzania — Serving all regions nationwide'),
('working_hours',       'Monday – Saturday, 7:00 AM – 6:00 PM EAT'),
('currency',            'TSh'),
('why_section_title',   'Why choose Mpulule AgroMarket?'),
('why_section_subtitle','Trusted by farmers across Tanzania'),
('why1_title',          'Quality assured'),
('why1_text',           'All products are certified and tested before listing on our platform.'),
('why2_title',          'Nationwide delivery'),
('why2_text',           'We deliver to all regions across Tanzania, fast and reliably.'),
('why3_title',          'Agri financing'),
('why3_text',           'Flexible agricultural loans tailored to your farm size and production.'),
('why4_title',          'Expert support'),
('why4_text',           'Reach us via email or WhatsApp, six days a week, 7AM–6PM.'),
('loan_rate_small',     '12'),
('loan_rate_medium',    '10'),
('loan_rate_large',     '8'),
('loan_max_small',      '500000'),
('loan_max_medium',     '2000000'),
('loan_max_large',      '10000000'),
('loan_tier_small_label',  'Small farm (0.5–2 acres)'),
('loan_tier_medium_label', 'Medium farm (2–10 acres)'),
('loan_tier_large_label',  'Large farm (10+ acres)'),
('required_docs',       'National ID or passport|Farm ownership or lease proof|Previous season harvest records|2 guarantors (local leaders accepted)');

-- ──────────────────────────────────────────────────────────
--  USERS
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    firstname   VARCHAR(80)  NOT NULL,
    lastname    VARCHAR(80)  NOT NULL,
    email       VARCHAR(180) NOT NULL UNIQUE,
    phone       VARCHAR(30)  DEFAULT NULL,
    region      VARCHAR(100) DEFAULT NULL,
    password    VARCHAR(255) NOT NULL,
    user_role   ENUM('admin','user') NOT NULL DEFAULT 'user',
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Admin: mpululefransisco@gmail.com / Admin@1234
INSERT INTO users (firstname,lastname,email,phone,region,password,user_role) VALUES
('Francisco','Mpulule','mpululefransisco@gmail.com','+255700000000','Dar es Salaam',
 '$2y$12$8K1p/a0dL1LXMIgoEDFrwOd8y1dSKY5NHEjr0L1nGSxGvPhVNqMqy','admin');

-- ──────────────────────────────────────────────────────────
--  CATEGORIES
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS categories (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    slug        VARCHAR(100) NOT NULL UNIQUE,
    icon        VARCHAR(60)  DEFAULT 'fa-leaf',
    description TEXT         DEFAULT NULL,
    sort_order  INT          DEFAULT 0
) ENGINE=InnoDB;

INSERT INTO categories (name,slug,icon,description,sort_order) VALUES
('Crops & Seeds',         'crops',       'fa-seedling',
 'Certified hybrid and open-pollinated seeds for all major crops', 1),
('Nitrogenous Fertilizer','nitrogen',    'fa-flask',
 'Urea, CAN and other nitrogen-based fertilizers for healthy leaf growth', 2),
('Phosphorus Fertilizer', 'phosphorus',  'fa-atom',
 'DAP, SSP and TSP for strong root development and early growth', 3),
('Potassium Fertilizer',  'potassium',   'fa-vial',
 'MOP, SOP and other potassium fertilizers for fruit and grain quality', 4),
('Farm Equipment',        'equipment',   'fa-tractor',
 'Tractors, pumps, sprayers and post-harvest machinery', 5);

-- ──────────────────────────────────────────────────────────
--  PRODUCTS
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS products (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    category_id  INT           NOT NULL,
    name         VARCHAR(200)  NOT NULL,
    description  TEXT          DEFAULT NULL,
    price        DECIMAL(12,2) NOT NULL,
    unit         VARCHAR(60)   NOT NULL DEFAULT 'kg',
    stock_qty    INT           NOT NULL DEFAULT 0,
    image        VARCHAR(255)  DEFAULT NULL,
    featured     TINYINT(1)    DEFAULT 0,
    active       TINYINT(1)    DEFAULT 1,
    created_at   DATETIME      DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO products
  (category_id,name,description,price,unit,stock_qty,featured) VALUES
-- Crops (cat 1)
(1,'Maize Seeds – DK8031 Hybrid',
 'High-yield DK8031 hybrid maize, drought-tolerant, 90-day maturity. Recommended for altitudes up to 1800m.',
 8500.00,'2 kg bag',350,1),
(1,'Rice Seeds – TXD306',
 'Tanzania-bred TXD306 paddy rice. Suitable for lowland irrigation. Matures in 120 days.',
 12000.00,'5 kg bag',180,1),
(1,'Sunflower Seeds – Record',
 'Open-pollinated sunflower for oil and food. 120-day maturity. Tolerates dry conditions.',
 6000.00,'kg',200,0),
(1,'Bean Seeds – Lyamungu 85',
 'Red kidney beans, bush type, high protein. 75-day maturity, performs well in highland areas.',
 4500.00,'kg',400,0),
(1,'Soybean Seeds – Maksoy 1N',
 'Improved soybean variety that nodulates well without inoculant. 110-day maturity.',
 5500.00,'kg',300,0),
(1,'Sweet Potato Vines – Vitaa',
 'Vitamin A-rich orange-flesh sweet potato planting material. High yield, disease-resistant.',
 3000.00,'bundle (100 vines)',150,0),
-- Nitrogenous (cat 2)
(2,'Urea 46% N',
 'Prilled urea with 46% nitrogen content. Best for topdressing maize, wheat and vegetables.',
 55000.00,'50 kg bag',500,1),
(2,'CAN (Calcium Ammonium Nitrate)',
 '27% N, neutralises soil acidity. Ideal for tea, wheat and maize. Safest N source for acidic soils.',
 48000.00,'50 kg bag',420,0),
(2,'Ammonium Sulphate',
 '21% N + 24% S. Excellent for sulphur-deficient soils and paddy rice.',
 42000.00,'50 kg bag',280,0),
(2,'Liquid Nitrogen – UAN 28%',
 'Urea-Ammonium Nitrate solution, 28% N. Easy for foliar application or fertigation.',
 38000.00,'20 L container',90,0),
-- Phosphorus (cat 3)
(3,'DAP (Di-Ammonium Phosphate)',
 '18-46-0 analysis. Best basal dressing at planting for most crops. High phosphate availability.',
 62000.00,'50 kg bag',450,1),
(3,'SSP (Single Super Phosphate)',
 '16% P₂O₅ + 11% S. Affordable basal phosphate with sulphur benefit.',
 38000.00,'50 kg bag',320,0),
(3,'TSP (Triple Super Phosphate)',
 '46% P₂O₅. High-analysis, chloride-free phosphate for broadcast or banded application.',
 68000.00,'50 kg bag',180,0),
-- Potassium (cat 4)
(4,'MOP (Muriate of Potash)',
 '60% K₂O. Standard potassium source for maize, cotton, sugarcane and vegetables.',
 58000.00,'50 kg bag',350,1),
(4,'SOP (Sulphate of Potash)',
 '50% K₂O + 17% S. Chloride-free. Ideal for tobacco, tea, fruits and potatoes.',
 72000.00,'50 kg bag',120,0),
(4,'Potassium Nitrate',
 '13% N + 46% K₂O. Premium fertigation-grade fertilizer for high-value crops.',
 95000.00,'25 kg bag',80,0),
-- Equipment (cat 5)
(5,'Hand Tractor – Dongfeng 12HP',
 '12HP diesel hand tractor with rotary tiller and trailer attachment. Ideal for 1–5 acre farms.',
 1850000.00,'unit',15,1),
(5,'Water Pump – 3" Diesel',
 'Centrifugal pump, 3" inlet/outlet, 30 m head, 60 m³/hr flow. Honda GX160-powered.',
 420000.00,'unit',22,1),
(5,'Knapsack Sprayer – 16 L',
 'Manual pressure sprayer, 16 L capacity. Padded shoulder straps and adjustable lance nozzle.',
 35000.00,'unit',200,0),
(5,'Motorised Sprayer – 20 L',
 'Petrol-engine backpack sprayer, 20 L, 15-bar pressure, anti-drip nozzle. Covers 1 acre/hr.',
 185000.00,'unit',40,0),
(5,'Maize Shelling Machine',
 'Electric/diesel combination sheller. 500 kg/hr capacity, steel drum. Reduces post-harvest loss.',
 580000.00,'unit',8,0),
(5,'Platform Weighing Scale – 300 kg',
 'Heavy-duty digital scale, 300 kg capacity. IP54-rated display, RS232 output.',
 145000.00,'unit',30,0);

-- ──────────────────────────────────────────────────────────
--  ORDERS
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS orders (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    order_ref      VARCHAR(24)   NOT NULL UNIQUE,
    user_id        INT           DEFAULT NULL,
    customer_name  VARCHAR(180)  NOT NULL,
    email          VARCHAR(180)  NOT NULL,
    phone          VARCHAR(30)   NOT NULL,
    region         VARCHAR(100)  NOT NULL,
    delivery_notes TEXT          DEFAULT NULL,
    total_amount   DECIMAL(14,2) DEFAULT 0.00,
    status         ENUM('pending','approved','processing','shipped','delivered','rejected')
                   DEFAULT 'pending',
    admin_note     TEXT          DEFAULT NULL,
    created_at     DATETIME      DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ──────────────────────────────────────────────────────────
--  ORDER ITEMS
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS order_items (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    order_id    INT           NOT NULL,
    product_id  INT           NOT NULL,
    qty         INT           NOT NULL DEFAULT 1,
    unit_price  DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (order_id)  REFERENCES orders(id)   ON DELETE CASCADE,
    FOREIGN KEY (product_id)REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ──────────────────────────────────────────────────────────
--  LOAN APPLICATIONS
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS loan_applications (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    loan_ref         VARCHAR(24)   NOT NULL UNIQUE,
    user_id          INT           DEFAULT NULL,
    applicant_name   VARCHAR(180)  NOT NULL,
    email            VARCHAR(180)  NOT NULL,
    phone            VARCHAR(30)   NOT NULL,
    farm_size_acres  DECIMAL(8,2)  NOT NULL,
    primary_crop     VARCHAR(100)  NOT NULL,
    annual_prod_kg   DECIMAL(10,2) DEFAULT NULL,
    loan_purpose     VARCHAR(255)  NOT NULL,
    repay_months     INT           NOT NULL DEFAULT 12,
    requested_amount DECIMAL(14,2) NOT NULL,
    interest_rate    DECIMAL(5,2)  NOT NULL,
    monthly_payment  DECIMAL(14,2) NOT NULL,
    status           ENUM('pending','approved','rejected','disbursed') DEFAULT 'pending',
    admin_note       TEXT          DEFAULT NULL,
    created_at       DATETIME      DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ──────────────────────────────────────────────────────────
--  LOAN CROP TYPES — dropdown options from DB
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS loan_crops (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    sort_order INT DEFAULT 0
) ENGINE=InnoDB;

INSERT INTO loan_crops (name, sort_order) VALUES
('Maize',1),('Rice',2),('Cassava',3),('Sunflower',4),
('Tobacco',5),('Coffee',6),('Sugarcane',7),('Beans',8),
('Soybeans',9),('Sweet Potato',10),('Vegetables',11),('Other',12);

-- ──────────────────────────────────────────────────────────
--  LOAN PURPOSES — dropdown options from DB
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS loan_purposes (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    label      VARCHAR(200) NOT NULL,
    sort_order INT DEFAULT 0
) ENGINE=InnoDB;

INSERT INTO loan_purposes (label, sort_order) VALUES
('Buy seeds & inputs',1),
('Purchase equipment',2),
('Land preparation & ploughing',3),
('Irrigation setup',4),
('Post-harvest storage',5),
('Agro-processing machinery',6),
('Other',7);

-- ──────────────────────────────────────────────────────────
--  CONTACT MESSAGES
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS contact_messages (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(180) NOT NULL,
    email      VARCHAR(180) NOT NULL,
    phone      VARCHAR(30)  DEFAULT NULL,
    subject    VARCHAR(255) NOT NULL,
    message    TEXT         NOT NULL,
    is_read    TINYINT(1)   DEFAULT 0,
    created_at DATETIME     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ──────────────────────────────────────────────────────────
--  CONTACT SUBJECTS — dropdown from DB
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS contact_subjects (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    label      VARCHAR(200) NOT NULL,
    sort_order INT DEFAULT 0
) ENGINE=InnoDB;

INSERT INTO contact_subjects (label,sort_order) VALUES
('Product inquiry',1),
('Order support',2),
('Loan application',3),
('Delivery inquiry',4),
('Partnership',5),
('Other',6);

-- ──────────────────────────────────────────────────────────
--  ORDER STATUS LABELS — from DB
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS order_statuses (
    slug  VARCHAR(30) PRIMARY KEY,
    label VARCHAR(60) NOT NULL,
    color VARCHAR(30) DEFAULT 'badge-gold'
) ENGINE=InnoDB;

INSERT INTO order_statuses (slug,label,color) VALUES
('pending',    'Pending',    'badge-warn'),
('approved',   'Approved',   'badge-success'),
('processing', 'Processing', 'badge-info'),
('shipped',    'Shipped',    'badge-info'),
('delivered',  'Delivered',  'badge-success'),
('rejected',   'Rejected',   'badge-danger');

-- ──────────────────────────────────────────────────────────
--  NOTIFICATIONS TABLE
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notifications (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT          DEFAULT NULL,
    email       VARCHAR(180) DEFAULT NULL,
    phone       VARCHAR(30)  DEFAULT NULL,
    type        ENUM('order','loan') NOT NULL,
    ref_id      INT          NOT NULL COMMENT 'order or loan application id',
    ref_code    VARCHAR(24)  NOT NULL COMMENT 'order_ref or loan_ref',
    old_status  VARCHAR(30)  DEFAULT NULL,
    new_status  VARCHAR(30)  NOT NULL,
    message     TEXT         NOT NULL,
    channel     ENUM('email','sms','both') DEFAULT 'both',
    sent_email  TINYINT(1)   DEFAULT 0,
    sent_sms    TINYINT(1)   DEFAULT 0,
    is_read     TINYINT(1)   DEFAULT 0,
    created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ──────────────────────────────────────────────────────────
--  NOTIFICATION TEMPLATES (stored in DB, editable by admin)
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notification_templates (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    type        ENUM('order','loan') NOT NULL,
    status      VARCHAR(30)  NOT NULL,
    subject     VARCHAR(255) NOT NULL,
    body        TEXT         NOT NULL,
    sms_body    VARCHAR(160) NOT NULL,
    UNIQUE KEY uniq_type_status (type, status)
) ENGINE=InnoDB;

INSERT INTO notification_templates (type, status, subject, body, sms_body) VALUES
-- ORDER templates
('order','approved',
 'Your Order {ref} Has Been Approved ✓',
 'Dear {name},\n\nGreat news! Your order {ref} has been APPROVED.\n\nOrder total: {amount}\nItems: {items}\n\nOur team will now process your order and contact you to arrange delivery to {region}.\n\nIf you have any questions, reply to this email or WhatsApp us at {whatsapp}.\n\nThank you for choosing {site_name}!\n\nBest regards,\n{site_name} Team\n{email}',
 'Hi {name}, your order {ref} ({amount}) is APPROVED. We will contact you for delivery. - {site_name}'),

('order','rejected',
 'Update on Your Order {ref}',
 'Dear {name},\n\nWe regret to inform you that your order {ref} could not be processed at this time.\n\nReason: {admin_note}\n\nPlease contact us for more information:\nEmail: {email}\nWhatsApp: {whatsapp}\n\nWe apologise for any inconvenience.\n\nBest regards,\n{site_name} Team',
 'Hi {name}, your order {ref} was not approved. Reason: {admin_note}. Contact us: {whatsapp} - {site_name}'),

('order','processing',
 'Your Order {ref} Is Being Processed',
 'Dear {name},\n\nYour order {ref} is now being PROCESSED and prepared for dispatch.\n\nOrder total: {amount}\n\nYou will receive another notification once your order is shipped.\n\nThank you,\n{site_name} Team',
 'Hi {name}, order {ref} is now PROCESSING and being prepared. Watch for shipping update. - {site_name}'),

('order','shipped',
 'Your Order {ref} Has Been Shipped! 🚚',
 'Dear {name},\n\nExcellent news! Your order {ref} has been SHIPPED and is on its way to {region}.\n\nOrder total: {amount}\n\nPlease ensure someone is available to receive the delivery. Contact us if needed:\nWhatsApp: {whatsapp}\n\nThank you,\n{site_name} Team',
 'Hi {name}, order {ref} has been SHIPPED to {region}. Contact {whatsapp} for updates. - {site_name}'),

('order','delivered',
 'Your Order {ref} Has Been Delivered ✓',
 'Dear {name},\n\nYour order {ref} has been marked as DELIVERED.\n\nWe hope you are satisfied with your purchase. Please contact us if you have any concerns:\nEmail: {email}\nWhatsApp: {whatsapp}\n\nThank you for your business!\n\n{site_name} Team',
 'Hi {name}, order {ref} marked DELIVERED. Questions? Contact {whatsapp}. Thank you! - {site_name}'),

-- LOAN templates
('loan','approved',
 'Your Loan Application {ref} Has Been Approved! 🎉',
 'Dear {name},\n\nCongratulations! Your agricultural loan application {ref} has been APPROVED.\n\nLoan details:\n- Amount approved: {amount}\n- Repayment period: {months} months\n- Monthly payment: {monthly}\n- Interest rate: {rate}% p.a.\n\nOur team will contact you at {phone} within 48 hours to complete the disbursement process.\n\nRequired at the office:\n- National ID or Passport\n- Farm ownership/lease documents\n\nContact us:\nEmail: {email}\nWhatsApp: {whatsapp}\n\nCongratulations again!\n\n{site_name} Team',
 'Congratulations {name}! Loan {ref} APPROVED: {amount} over {months} months. We will call {phone} within 48hrs. - {site_name}'),

('loan','rejected',
 'Update on Your Loan Application {ref}',
 'Dear {name},\n\nThank you for applying for an agricultural loan with {site_name}.\n\nUnfortunately, your loan application {ref} for {amount} was not approved at this time.\n\nReason: {admin_note}\n\nYou are welcome to apply again in the future or contact us to discuss alternative options:\nEmail: {email}\nWhatsApp: {whatsapp}\n\nWe appreciate your interest in {site_name}.\n\nBest regards,\n{site_name} Team',
 'Hi {name}, loan application {ref} was not approved. Reason: {admin_note}. Contact {whatsapp} for help. - {site_name}'),

('loan','disbursed',
 'Your Loan {ref} Has Been Disbursed! 💰',
 'Dear {name},\n\nYour loan {ref} of {amount} has been DISBURSED to your account.\n\nRepayment starts next month. Monthly installment: {monthly}\n\nPlease ensure timely repayment to maintain your credit record.\n\nContact us if you need assistance:\nEmail: {email}\nWhatsApp: {whatsapp}\n\nThank you,\n{site_name} Team',
 'Hi {name}, loan {ref} of {amount} has been DISBURSED. Monthly payment: {monthly}. - {site_name}');

-- ──────────────────────────────────────────────────────────
--  NOTIFICATIONS — all customer/user notifications logged here
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notifications (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT           DEFAULT NULL,
    email          VARCHAR(180)  NOT NULL,
    phone          VARCHAR(30)   DEFAULT NULL,
    subject        VARCHAR(255)  NOT NULL,
    body           TEXT          NOT NULL,
    type           ENUM('order','loan','general') DEFAULT 'order',
    sent_email     TINYINT(1)    DEFAULT 0,
    whatsapp_link  TEXT          DEFAULT NULL,
    is_read        TINYINT(1)    DEFAULT 0,
    sent_at        DATETIME      DEFAULT NULL,
    created_at     DATETIME      DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
