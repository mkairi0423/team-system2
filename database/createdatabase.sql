SET NAMES utf8mb4;

-- ====================================================================
-- 1. 古いテーブルの削除（完全リセット）
-- ====================================================================
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS favorite_recipe_ingredient;
DROP TABLE IF EXISTS favorite_recipe;
DROP TABLE IF EXISTS cooking_history_ingredient;
DROP TABLE IF EXISTS cooking_history;
DROP TABLE IF EXISTS cooking_now;
DROP TABLE IF EXISTS ingredient;
DROP TABLE IF EXISTS storage_location;
DROP TABLE IF EXISTS category;
DROP TABLE IF EXISTS user;
SET FOREIGN_KEY_CHECKS = 1;

-- ====================================================================
-- 2. 基本・マスタテーブル作成
-- ====================================================================

-- ユーザー管理
CREATE TABLE user (
    user_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    verification_token VARCHAR(64) NULL,
    token_expires DATETIME NULL,
    is_verified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- 食材カテゴリ
CREATE TABLE category (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) NOT NULL,
    frozen_expiry_days INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- 保管場所
CREATE TABLE storage_location (
    location_id INT AUTO_INCREMENT PRIMARY KEY,
    location_name VARCHAR(50) NOT NULL,
    is_frozen TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- ====================================================================
-- 3. 在庫・調理中管理（DECIMAL(10,2)とVARCHAR(20)で柔軟に対応）
-- ====================================================================

CREATE TABLE ingredient (
    ingredient_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    category_id INT NOT NULL,
    storage_location_id INT NOT NULL, 
    food_name VARCHAR(100) NOT NULL,
    quantity DECIMAL(10,2) NULL, -- 小数点対応
    unit VARCHAR(20) NOT NULL DEFAULT '個', -- 自由入力に変更
    expiration_date DATE NULL,
    term_type ENUM('賞味期限', '消費期限') NOT NULL DEFAULT '賞味期限',
    status ENUM('未消費', '消費済', '廃棄') NOT NULL DEFAULT '未消費' COMMENT '食品ロス分析用ステータス',
    frozen_at DATE NULL COMMENT '冷凍庫に保管（または移動）した日付。冷凍焼け計算の起点。',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES category(category_id) ON DELETE RESTRICT,
    FOREIGN KEY (storage_location_id) REFERENCES storage_location(location_id) ON DELETE RESTRICT
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

CREATE TABLE cooking_now (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    original_ingredient_id BIGINT NULL, 
    food VARCHAR(100) NOT NULL,
    quantity DECIMAL(10,2) NULL, -- 小数点対応
    unit VARCHAR(20) NOT NULL DEFAULT '個', -- 自由入力に変更
    original_storage_location_id INT NULL, 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE,
    FOREIGN KEY (original_ingredient_id) REFERENCES ingredient(ingredient_id) ON DELETE SET NULL,
    FOREIGN KEY (original_storage_location_id) REFERENCES storage_location(location_id) ON DELETE SET NULL
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- ====================================================================
-- 4. 📜 調理履歴
-- ====================================================================

CREATE TABLE cooking_history (
    history_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    dish_name VARCHAR(255) NOT NULL,
    cooked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

CREATE TABLE cooking_history_ingredient (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    history_id BIGINT NOT NULL,
    category_id INT NOT NULL,
    used_food_name VARCHAR(100) NOT NULL,
    quantity DECIMAL(10,2) NULL, -- 小数点対応
    unit VARCHAR(20) NOT NULL DEFAULT '個', -- 自由入力に変更
    FOREIGN KEY (history_id) REFERENCES cooking_history(history_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES category(category_id) ON DELETE RESTRICT
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- ====================================================================
-- 5. ⭐️ お気に入りレシピ
-- ====================================================================

CREATE TABLE favorite_recipe (
    recipe_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    recipe_name VARCHAR(255) NOT NULL,
    recipe_url VARCHAR(2048) NULL,
    memo TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

CREATE TABLE favorite_recipe_ingredient (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    recipe_id BIGINT NOT NULL,
    category_id INT NOT NULL,
    food_name_hint VARCHAR(100) NULL,
    quantity DECIMAL(10,2) NULL, -- 小数点対応
    unit VARCHAR(20) NOT NULL DEFAULT '個', -- 自由入力に変更
    FOREIGN KEY (recipe_id) REFERENCES favorite_recipe(recipe_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES category(category_id) ON DELETE RESTRICT
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- パフォーマンス向上のインデックス
ALTER TABLE ingredient ADD INDEX idx_ingredient_frozen (storage_location_id, frozen_at);