SET NAMES utf8mb4;

-- ====================================================================
-- 1. 古いテーブルの削除（初期化用）
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
    location_name VARCHAR(50) NOT NULL COMMENT '冷蔵庫、冷凍庫、常温、野菜室 など'
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


-- ====================================================================
-- 3. 在庫・調理中管理テーブル
-- ====================================================================

-- 🛒 在庫食材管理テーブル
CREATE TABLE ingredient (
    ingredient_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    category_id INT NOT NULL,
    storage_location_id INT NOT NULL, 
    food_name VARCHAR(100) NOT NULL,
    quantity INT NULL COMMENT '数量',
    unit ENUM('g', '個', '本', '玉', 'パック', '枚', 'ml') NOT NULL DEFAULT '個',
    expiration_date DATE NULL,
    term_type ENUM('賞味期限', '消費期限') NOT NULL DEFAULT '賞味期限',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
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
    
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE,
    FOREIGN KEY (original_ingredient_id) REFERENCES ingredient(ingredient_id) ON DELETE CASCADE,
    FOREIGN KEY (original_storage_location_id) REFERENCES storage_location(location_id) ON DELETE RESTRICT
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


-- ====================================================================
-- 4. 📜 調理履歴テーブル（料理単位で管理できるよう親子に分割）
-- ====================================================================

-- 履歴（親）：作った料理そのものの記録
CREATE TABLE cooking_history (
    history_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    dish_name VARCHAR(255) NOT NULL COMMENT '作った料理名（AIが提案した料理名）',
    cooked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- 履歴（子）：その料理で使った食材の明細
CREATE TABLE cooking_history_ingredient (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    history_id BIGINT NOT NULL,
    category_id INT NOT NULL,
    used_food_name VARCHAR(100) NOT NULL COMMENT '実際に使った具体的な食材名',
    quantity INT NULL,
    unit VARCHAR(20) NOT NULL DEFAULT '個',
    
    FOREIGN KEY (history_id) REFERENCES cooking_history(history_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES category(category_id) ON DELETE RESTRICT
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


-- ====================================================================
-- 5. ⭐️ お気に入りレシピテーブル（履歴からの完全連動型）
-- ====================================================================

-- お気に入り（親）
CREATE TABLE favorite_recipe (
    recipe_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    recipe_name VARCHAR(255) NOT NULL COMMENT '履歴の dish_name からコピー',
    recipe_url VARCHAR(2048) NULL COMMENT '参考にしたURLなどがあれば保存可能',
    memo TEXT NULL COMMENT '自由なメモ（例：「AI提案からお気に入り登録」など自動挿入も可）',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- お気に入り（子）：必要食材
CREATE TABLE favorite_recipe_ingredient (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    recipe_id BIGINT NOT NULL,
    category_id INT NOT NULL COMMENT '履歴の category_id からコピー',
    food_name_hint VARCHAR(100) NULL COMMENT '履歴の used_food_name からコピー',
    quantity INT NULL,
    unit ENUM('g', '個', '本', '玉', 'パック', '枚', 'ml') NOT NULL DEFAULT '個',
    
    FOREIGN KEY (recipe_id) REFERENCES favorite_recipe(recipe_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES category(category_id) ON DELETE RESTRICT
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;