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

-- 食材カテゴリマスタ
INSERT INTO category (category_id, category_name) VALUES 
(1, '肉類'), 
(2, '魚介類'), 
(3, '卵・大豆・乳製品'),
(4, '野菜・果物'),       
(5, '甘いもの'), 
(6, 'その他');

-- 保管場所マスタ
INSERT INTO storage_location (location_id, location_name) VALUES 
(1, '冷蔵庫'), 
(2, '冷凍庫'), 
(3, '野菜室'), 
(4, '常温');

-- ユーザー管理
INSERT INTO user (user_id, name, email, password) VALUES 
(1, 'test2026', 'test@example.com', 'hashed_password_here');


-- ====================================================================
-- 4. 🛒 在庫食材テストデータの投入（カテゴリIDの不整合を修正！）
-- ====================================================================
INSERT INTO ingredient (user_id, category_id, storage_location_id, food_name, quantity, unit, expiration_date, term_type) VALUES 
(1, 1, 1, '豚ひき肉', 200, 'g',    '2026-06-22', '消費期限'), -- 1: 肉類 (OK)
(1, 4, 1, 'キャベツ', 1,   '玉',   '2026-06-23', '賞味期限'), -- 🟢 4: 野菜・果物 に修正
(1, 3, 1, '卵',       10,  '個',   '2026-06-24', '賞味期限'), -- 🟢 3: 卵・大豆・乳製品 に修正
(1, 2, 1, 'サーモン', 2,   '枚',   '2026-06-26', '消費期限'); -- 2: 魚介類 (OK)