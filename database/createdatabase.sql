-- 既存のテーブルを依存関係の逆順で安全に削除
DROP TABLE IF EXISTS cooking_now;
DROP TABLE IF EXISTS ingredients;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

-- 1. ユーザー管理
CREATE TABLE users (
    uid BIGINT AUTO_INCREMENT PRIMARY KEY,
    name_id VARCHAR(255) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. 食材カテゴリ管理
CREATE TABLE categories (
    cid INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) NOT NULL
) ENGINE=InnoDB;

-- 3. 在庫食材管理（冷蔵庫・冷凍庫の中身）
CREATE TABLE ingredients (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    category_id INT NOT NULL,
    food VARCHAR(100) NOT NULL,
    quantity INT NULL COMMENT 'グラム数または個数',
    expiration_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(uid) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(cid) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 4. 🍳 調理中専用の一時テーブル（短縮版）
CREATE TABLE cooking_now (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,                           -- 誰が調理中か
    original_ingredient_id BIGINT NOT NULL,           -- キャンセル時の復元用ID
    food VARCHAR(100) NOT NULL,                        -- 食材名
    quantity INT NULL,                                 -- 使う分量
    original_storage_type VARCHAR(20) NOT NULL,       -- 元の場所 ('fridge' / 'freezer')
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,   -- 24時間判定用
    FOREIGN KEY (user_id) REFERENCES users(uid) ON DELETE CASCADE
) ENGINE=InnoDB;