SET NAMES utf8mb4;

SET FOREIGN_KEY_CHECKS = 0;
-- 一括クリア（storage_locations も追加）
TRUNCATE TABLE cooking_now; 
TRUNCATE TABLE ingredients;
TRUNCATE TABLE storage_locations;
TRUNCATE TABLE users;
TRUNCATE TABLE categories;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. カテゴリの登録
INSERT INTO categories (cid, category_name) VALUES 
(1, '肉類'),
(2, '野菜'),
(3, '魚介類'),
(4, 'その他');

-- 2. 🔥 保管場所マスタの登録（自動的に 冷蔵庫=1, 冷凍庫=2 ... になります）
INSERT INTO storage_locations (id, location_name) VALUES 
(1, '冷蔵庫'),
(2, '冷凍庫'),
(3, '常温（パントリー）'),
(4, '野菜室');

-- 3. テストユーザーの登録（自動的に uid = 1 になります）
-- ※お使いのテーブルの「name_id」または「username」に合わせて調整してください
INSERT INTO users (uid, name_id, email, password) VALUES 
(1, 'test2026', 'test@example.com', 'hashed_password_here');

-- 4. 🛒 在庫食材の登録（第3引数に storage_location_id を追加）
-- ここではすべて一旦 「1（冷蔵庫）」 に入るように設定しています。
-- もし「サーモン」を冷凍庫に入れたい場合は、第3引数の「1」を「2」に変えてください。
INSERT INTO ingredients (user_id, category_id, storage_location_id, food_name, quantity, expiration_date, term_type) VALUES 
(1, 1, 1, '豚ひき肉', 200, '2026-05-22', '消費期限'),
(1, 2, 1, 'キャベツ', 1,   '2026-05-23', '賞味期限'),
(1, 4, 1, '卵',       10,  '2026-05-24', '賞味期限'), 
(1, 3, 1, 'サーモン', 2,   '2026-05-26', '消費期限');