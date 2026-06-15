SET NAMES utf8mb4;

-- ====================================================================
-- 1. 古いテーブルの削除（初期化用）
-- ====================================================================
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS cooking_history;
DROP TABLE IF EXISTS cooking_now;
DROP TABLE IF EXISTS ingredients;
DROP TABLE IF EXISTS storage_locations;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;


-- ====================================================================
-- 2. テーブル作成（構造定義）
-- ====================================================================

-- ユーザー管理テーブル
CREATE TABLE users (
    uid BIGINT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL COMMENT 'ユーザー名',
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL ,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- 食材カテゴリマスタ
CREATE TABLE categories (
    cid INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- 保管場所マスタ
CREATE TABLE storage_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    location_name VARCHAR(50) NOT NULL COMMENT '冷蔵庫、冷凍庫、常温、野菜室 など',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- 🛒 在庫食材管理テーブル（一括管理）
CREATE TABLE ingredients (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    category_id INT NOT NULL,
    storage_location_id INT NOT NULL, 
    food_name VARCHAR(100) NOT NULL,
    quantity INT NULL COMMENT '数量（数値のみ）',
    -- 🔥 単位カラムを新設！ これで「g」や「個」をバラバラに管理できます
    unit ENUM('g', '個', '本', '玉', 'パック', '枚', 'ml') NOT NULL DEFAULT '個' COMMENT '食材の単位',
    expiration_date DATE NULL,
    term_type ENUM('賞味期限', '消費期限') NOT NULL DEFAULT '賞味期限',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(uid) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(cid) ON DELETE RESTRICT,
    FOREIGN KEY (storage_location_id) REFERENCES storage_locations(id) ON DELETE RESTRICT
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- 🍳 調理中一時管理テーブル
CREATE TABLE cooking_now (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    original_ingredient_id BIGINT NOT NULL,
    food VARCHAR(100) NOT NULL,
    quantity INT NULL COMMENT '使用する数量',
    -- 🔥 調理中テーブルにも単位を合わせて持たせます
    unit ENUM('g', '個', '本', '玉', 'パック', '枚', 'ml') NOT NULL DEFAULT '個',
    original_storage_location_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(uid) ON DELETE CASCADE,
    FOREIGN KEY (original_ingredient_id) REFERENCES ingredients(id) ON DELETE CASCADE,
    FOREIGN KEY (original_storage_location_id) REFERENCES storage_locations(id) ON DELETE RESTRICT
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- 📜 調理履歴テーブル
CREATE TABLE cooking_history (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    dish_name VARCHAR(255) NOT NULL,
    used_food_name VARCHAR(100) NOT NULL,
    quantity INT NULL,
    -- 🔥 履歴にも「何の単位でどれだけ使ったか」を残せるようにします
    unit VARCHAR(20) NOT NULL DEFAULT '個' COMMENT '削除対策のためVARCHARにしています',
    cooked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(uid) ON DELETE CASCADE
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
