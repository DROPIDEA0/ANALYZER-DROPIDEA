-- SQLite Database Import Script
-- Converted from MySQL to SQLite format

-- Drop tables if they exist (in reverse order due to foreign keys)
DROP TABLE IF EXISTS website_analyses;
DROP TABLE IF EXISTS ai_api_settings;
DROP TABLE IF EXISTS personal_access_tokens;
DROP TABLE IF EXISTS password_reset_tokens;
DROP TABLE IF EXISTS failed_jobs;
DROP TABLE IF EXISTS migrations;
DROP TABLE IF EXISTS users;

-- Create users table
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at DATETIME,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100),
    created_at DATETIME,
    updated_at DATETIME
);

-- Create password_reset_tokens table
CREATE TABLE password_reset_tokens (
    email VARCHAR(255) PRIMARY KEY,
    token VARCHAR(255) NOT NULL,
    created_at DATETIME
);

-- Create failed_jobs table
CREATE TABLE failed_jobs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    uuid VARCHAR(255) UNIQUE NOT NULL,
    connection TEXT NOT NULL,
    queue TEXT NOT NULL,
    payload TEXT NOT NULL,
    exception TEXT NOT NULL,
    failed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Create personal_access_tokens table
CREATE TABLE personal_access_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tokenable_type VARCHAR(255) NOT NULL,
    tokenable_id INTEGER NOT NULL,
    name VARCHAR(255) NOT NULL,
    token VARCHAR(64) UNIQUE NOT NULL,
    abilities TEXT,
    last_used_at DATETIME,
    expires_at DATETIME,
    created_at DATETIME,
    updated_at DATETIME
);

-- Create index for personal_access_tokens
CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index 
ON personal_access_tokens (tokenable_type, tokenable_id);

-- Create migrations table
CREATE TABLE migrations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    migration VARCHAR(255) NOT NULL,
    batch INTEGER NOT NULL
);

-- Create ai_api_settings table
CREATE TABLE ai_api_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    provider VARCHAR(255) NOT NULL,
    api_key TEXT NOT NULL,
    api_base_url VARCHAR(255),
    model VARCHAR(255),
    is_active INTEGER NOT NULL DEFAULT 0,
    settings TEXT, -- JSON data stored as TEXT in SQLite
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE (user_id, provider)
);

-- Create website_analyses table
CREATE TABLE website_analyses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    url VARCHAR(255) NOT NULL,
    region VARCHAR(255) NOT NULL,
    analysis_type VARCHAR(20) NOT NULL CHECK (analysis_type IN ('full', 'seo', 'performance', 'competitors')),
    analysis_data TEXT NOT NULL, -- JSON data stored as TEXT in SQLite
    seo_score INTEGER,
    performance_score INTEGER,
    load_time DECIMAL(5,2),
    ai_score INTEGER,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create indexes for website_analyses
CREATE INDEX website_analyses_user_id_created_at_index ON website_analyses (user_id, created_at);
CREATE INDEX website_analyses_url_index ON website_analyses (url);

-- Insert migrations data
INSERT INTO migrations (id, migration, batch) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_reset_tokens_table', 1),
(3, '2019_08_19_000000_create_failed_jobs_table', 1),
(4, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(5, '2025_09_17_073327_create_website_analyses_table', 1),
(6, '2025_09_17_102522_add_ai_score_to_website_analyses_table', 2),
(8, '2025_09_17_105838_create_ai_api_settings_table', 3),
(9, '2025_09_17_130000_fix_api_key_column_length', 4),
(10, '2025_09_17_131205_alter_ai_api_settings_api_key_to_text', 4);

-- Insert user data
INSERT INTO users (id, name, email, email_verified_at, password, remember_token, created_at, updated_at) VALUES
(1, 'DIAA UDDIN ABABNEH', 'diaa.uddin.cv@gmail.com', NULL, '$2y$12$Nfly.fxdT3jk7c/HpgJaJOO9HTEN9g2O5UzrZVKkrdNbs5rLtR062', NULL, '2025-09-17 12:40:21', '2025-09-17 12:40:21');

-- Insert AI API settings data
INSERT INTO ai_api_settings (id, user_id, provider, api_key, api_base_url, model, is_active, settings, created_at, updated_at) VALUES
(1, 1, 'openai', 'eyJpdiI6ImVpakpBaU9WRGNWUnphZnRvb1NZNFE9PSIsInZhbHVlIjoiam5HaWQvT2t0aFRUU2toU1Vma0RiUnhycUorYmNWYmRkM3hia2VWdjhzWUxmVVVpcmwvMmNNK3J5Snk2aHhYbGlyZmdFcERTZ3M1dE9FaHo3bmFxZmhpV2dEY2p4c1RFNk1qKzlXS2pHcGJlSVJobk55RzhUdHRVZnJKVHNOTHRhcEl4OUFlRHMyR0w1bXVvOWs4d1lTdlpPcm51YW1YRjZTandOYWxycm1IcERoQk5zSVFYYmxZVXFyemtEVWdVcElHQ21CKzFDWm91WmhlZ1NBaFpSQzAyMDYwYWdWU1hmbHozT2ovQThVOD0iLCJtYWMiOiJiMjIzYzM3NDYzMWEyMTBhOTIyYWY3NjgwNGM1NTViZGRkNjhkMGU1MzE5M2Q1Yzg2NDQyZTE5NDI3ZmM5N2Q0In0=', 'https://api.openai.com/v1', 'gpt-4o-mini', 1, NULL, '2025-09-17 10:59:59', '2025-09-17 11:01:49');

-- Insert sample website analyses data (cleaned for SQLite)
INSERT INTO website_analyses (id, user_id, url, region, analysis_type, analysis_data, seo_score, performance_score, load_time, ai_score, created_at, updated_at) VALUES
(1, 1, 'https://dropidea.com', 'global', 'full', '{"url":"https://dropidea.com","region":"global","analysis_type":"full","basic_info":{"url":"https://dropidea.com","status_code":200,"response_time":106.59,"title":"الرئيسية - دروب أيديا","description":"منصة عربية متخصصة في ريادة الأعمال والتكنولوجيا"},"seo_analysis":{"title_length":25,"meta_description":"متوفر","h1_tags":1,"h2_tags":3,"internal_links":15,"external_links":8},"performance":{"load_time":106.59,"page_size":"2.1MB","requests":45},"technologies":{"cms":"WordPress","analytics":["Google Analytics"],"cdn":"Cloudflare"}}', 85, 78, 106.59, 82, '2025-09-17 14:30:15', '2025-09-17 14:30:15'),
(2, 1, 'https://dropidea.com', 'global', 'full', '{"url":"https://dropidea.com","region":"global","analysis_type":"full","basic_info":{"url":"https://dropidea.com","status_code":200,"response_time":99.7,"title":"الرئيسية - دروب أيديا","description":"منصة عربية متخصصة في ريادة الأعمال والتكنولوجيا"},"seo_analysis":{"title_length":25,"meta_description":"متوفر","h1_tags":1,"h2_tags":3,"internal_links":15,"external_links":8},"performance":{"load_time":99.7,"page_size":"2.1MB","requests":45},"technologies":{"cms":"WordPress","analytics":["Google Analytics"],"cdn":"Cloudflare"}}', 85, 82, 99.70, 84, '2025-09-17 15:15:28', '2025-09-17 15:15:28'),
(3, 1, 'https://dropidea.com', 'global', 'full', '{"url":"https://dropidea.com","region":"global","analysis_type":"full","basic_info":{"url":"https://dropidea.com","status_code":200,"response_time":114.88,"title":"الرئيسية - دروب أيديا","description":"منصة عربية متخصصة في ريادة الأعمال والتكنولوجيا"},"seo_analysis":{"title_length":25,"meta_description":"متوفر","h1_tags":1,"h2_tags":3,"internal_links":15,"external_links":8},"performance":{"load_time":114.88,"page_size":"2.1MB","requests":45},"technologies":{"cms":"WordPress","analytics":["Google Analytics"],"cdn":"Cloudflare"}}', 85, 75, 114.88, 80, '2025-09-17 16:22:41', '2025-09-17 16:22:41');