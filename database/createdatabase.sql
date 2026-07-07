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

-- 食材カテゴリマスタ ★冷凍限界日数を追加
CREATE TABLE category (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) NOT NULL,
    frozen_expiry_days INT NULL COMMENT '冷凍保存時の品質保持目安日数（例:ひき肉なら14日、豚肉なら30日など）',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- 保管場所マスタ ★冷凍環境フラグを追加
CREATE TABLE storage_location (
    location_id INT AUTO_INCREMENT PRIMARY KEY,
    location_name VARCHAR(50) NOT NULL COMMENT '冷蔵庫、冷凍庫、常温、野菜室 など',
    is_frozen TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1:冷凍環境（冷凍焼け計算対象）、0:非冷凍環境'
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


-- ====================================================================
-- 3. 在庫・調理中管理テーブル
-- ====================================================================

-- 🛒 在庫食材管理テーブル ★冷凍開始日を追加
CREATE TABLE ingredient (
    ingredient_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    category_id INT NOT NULL,
    storage_location_id INT NOT NULL, 
    food_name VARCHAR(100) NOT NULL,
    quantity INT NULL COMMENT '数量',
    unit ENUM('g', '個', '本', '玉', 'パック', '枚', 'ml') NOT NULL DEFAULT '個',
    expiration_date DATE NULL COMMENT '通常の賞味・消費期限（主に冷蔵・常温用）',
    term_type ENUM('賞味期限', '消費期限') NOT NULL DEFAULT '賞味期限',
    frozen_at DATE NULL COMMENT '冷凍庫に保管（または移動）した日付。冷凍焼け計算の起点。',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES category(category_id) ON DELETE RESTRICT,
    FOREIGN KEY (storage_location_id) REFERENCES storage_location(location_id) ON DELETE RESTRICT
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- 🍳 調理中一時管理テーブル
CREATE TABLE cooking_now (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    -- 💡 NULL を許可（NOT NULL を削除）
    original_ingredient_id BIGINT NULL, 
    food VARCHAR(100) NOT NULL,
    quantity INT NULL COMMENT '使用する数量',
    unit ENUM('g', '個', '本', '玉', 'パック', '枚', 'ml') NOT NULL DEFAULT '個',
    -- 💡 NULL を許可（NOT NULL を削除）
    original_storage_location_id INT NULL, 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- 👤 ユーザーIDの紐付け（これは必須のまま）
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE,
    
    -- 💡 外部キー制約の削除、または「ON DELETE SET NULL」への変更
    -- ※ 在庫が削除されても、調理中リスト（cooking_now）のレコードごと消えないように SET NULL にするのが安全です。
    FOREIGN KEY (original_ingredient_id) REFERENCES ingredient(ingredient_id) ON DELETE SET NULL,
    FOREIGN KEY (original_storage_location_id) REFERENCES storage_location(location_id) ON DELETE SET NULL
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


-- ====================================================================
-- 4. 📜 調理履歴テーブル
-- ====================================================================

-- 履歴（親）
CREATE TABLE cooking_history (
    history_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    dish_name VARCHAR(255) NOT NULL COMMENT '作った料理名',
    cooked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- 履歴（子）
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
-- 5. ⭐️ お気に入りレシピテーブル
-- ====================================================================

-- お気に入り（親）
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

-- お気に入り（子）
CREATE TABLE favorite_recipe_ingredient (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    recipe_id BIGINT NOT NULL,
    category_id INT NOT NULL,
    food_name_hint VARCHAR(100) NULL,
    quantity INT NULL,
    unit ENUM('g', '個', '本', '玉', 'パック', '枚', 'ml') NOT NULL DEFAULT '個',
    
    FOREIGN KEY (recipe_id) REFERENCES favorite_recipe(recipe_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES category(category_id) ON DELETE RESTRICT
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


-- ====================================================================
-- 6. パフォーマンス向上のためのインデックス（追加推奨）
-- ====================================================================
ALTER TABLE ingredient ADD INDEX idx_ingredient_frozen (storage_location_id, frozen_at);

-- ====================================================================
-- 7. 小数点対応のための型変更（追加）
-- ====================================================================
ALTER TABLE ingredient MODIFY quantity DECIMAL(10,2) NULL;
ALTER TABLE cooking_now MODIFY quantity DECIMAL(10,2) NULL;