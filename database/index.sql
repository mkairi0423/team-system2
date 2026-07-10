SET NAMES utf8mb4;

-- ====================================================================
-- 1. 文字コードのutf8mb4化
-- ====================================================================
ALTER DATABASE team_system2 CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE ingredient CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE ingredient MODIFY food_name VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;


-- ====================================================================
-- 2. データの全クリア（TRUNCATE）
-- ====================================================================
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE favorite_recipe_ingredient;
TRUNCATE TABLE favorite_recipe;
TRUNCATE TABLE cooking_history_ingredient;
TRUNCATE TABLE cooking_history;
TRUNCATE TABLE cooking_now;
TRUNCATE TABLE ingredient;
TRUNCATE TABLE storage_location;
TRUNCATE TABLE category;
TRUNCATE TABLE user;
SET FOREIGN_KEY_CHECKS = 1;


-- ====================================================================
-- 3. 初期・マスタデータの投入
-- ====================================================================

-- 食材カテゴリマスタ ★冷凍限界日数（目安）を設定
INSERT INTO category (category_id, category_name, frozen_expiry_days) VALUES 
(1, '肉類', 30),        -- 肉類は冷凍で約1ヶ月
(2, '魚介類', 21),      -- 魚は少し早めの約3週間
(3, '卵・大豆・乳製品', 21),
(4, '野菜・果物', 30),       
(5, '甘いもの', 14), 
(6, 'その他', 30);

-- 保管場所マスタ ★冷凍庫（location_id = 2）の is_frozen を 1 に設定
INSERT INTO storage_location (location_id, location_name, is_frozen) VALUES 
(1, '冷蔵庫', 0), 
(2, '冷凍庫', 1), -- 💡 ここを1にすることで冷凍焼け計算対象になります
(3, '野菜室', 0), 
(4, '常温', 0);

