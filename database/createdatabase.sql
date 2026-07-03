SET NAMES utf8mb4;

-- ====================================================================
-- 1. 古いテーブルの削除（初期化用：新しい単数形名に統一）
-- ====================================================================
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS cooking_history;
DROP TABLE IF EXISTS cooking_now;
DROP TABLE IF EXISTS ingredient;
DROP TABLE IF EXISTS storage_location;
DROP TABLE IF EXISTS category;
DROP TABLE IF EXISTS user;
SET FOREIGN_KEY_CHECKS = 1;


-- ====================================================================
-- 2. テーブル作成（構造定義）
-- ====================================================================

-- ユーザー管理テーブル
CREATE TABLE user (
    user_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL COMMENT 'ユーザー名',
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- 食材カテゴリマスタ
CREATE TABLE category (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- 保管場所マスタ
CREATE TABLE storage_location (
    location_id INT AUTO_INCREMENT PRIMARY KEY,
    location_name VARCHAR(50) NOT NULL COMMENT '冷蔵庫、冷凍庫、常温、野菜室 など' -- 💡 末尾の不要なカンマを削除
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- 🛒 在庫食材管理テーブル（一括管理）
CREATE TABLE ingredient (
    ingredient_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    category_id INT NOT NULL,
    storage_location_id INT NOT NULL, 
    food_name VARCHAR(100) NOT NULL,
    quantity INT NULL COMMENT '数量（数値のみ）',
    unit ENUM('g', '個', '本', '玉', 'パック', '枚', 'ml') NOT NULL DEFAULT '個' COMMENT '食材の単位',
    expiration_date DATE NULL,
    term_type ENUM('賞味期限', '消費期限') NOT NULL DEFAULT '賞味期限',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- 💡 外部キーの参照先を新しい単数形テーブル名・カラム名に修正
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES category(category_id) ON DELETE RESTRICT,
    FOREIGN KEY (storage_location_id) REFERENCES storage_location(location_id) ON DELETE RESTRICT
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- 🍳 調理中一時管理テーブル
CREATE TABLE cooking_now (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    original_ingredient_id BIGINT NOT NULL,
    food VARCHAR(100) NOT NULL,
    quantity INT NULL COMMENT '使用する数量',
    unit ENUM('g', '個', '本', '玉', 'パック', '枚', 'ml') NOT NULL DEFAULT '個',
    original_storage_location_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- 💡 外部キーの参照先を新しい単数形テーブル名・カラム名に修正
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE,
    FOREIGN KEY (original_ingredient_id) REFERENCES ingredient(ingredient_id) ON DELETE CASCADE,
    FOREIGN KEY (original_storage_location_id) REFERENCES storage_location(location_id) ON DELETE RESTRICT
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- 📜 調理履歴テーブル
CREATE TABLE cooking_history (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    dish_name VARCHAR(255) NOT NULL,
    used_food_name VARCHAR(100) NOT NULL,
    quantity INT NULL,
    unit VARCHAR(20) NOT NULL DEFAULT '個' COMMENT '削除対策のためVARCHARにしています',
    cooked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- 💡 外部キーの参照先を user(user_id) に修正
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

