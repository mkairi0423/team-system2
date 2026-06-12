


-- 中身が綺麗にクリアされてからインサートされるため、重複エラーにならずに済むはずです。

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE cooking_now;
TRUNCATE TABLE ingredients;
TRUNCATE TABLE storage_locations;
TRUNCATE TABLE categories;
TRUNCATE TABLE users;
SET FOREIGN_KEY_CHECKS = 1;


-- ====================================================================
-- 3. 初期・テストデータの投入
-- ====================================================================

-- ====================================================================
-- 3. 初期・テストデータの投入（インデックス・テスト用）
-- ====================================================================

-- ① カテゴリの登録
INSERT INTO categories (cid, category_name) VALUES 
(1, '肉類'), (2, '野菜'), (3, '魚介類'), (4, 'その他');

INSERT INTO storage_locations (id, location_name) VALUES 
(1, '冷蔵庫'), (2, '冷凍庫'), (3, '常温（パントリー）'), (4, '野菜室');

INSERT INTO users (uid, name, email, password) VALUES 
(1, 'test2026', 'test@example.com', 'hashed_password_here');

INSERT INTO ingredients (user_id, category_id, storage_location_id, food_name, quantity, expiration_date, term_type) VALUES 
(1, 1, 1, '豚ひき肉', 200, '2026-05-22', '消費期限'),
(1, 2, 1, 'キャベツ', 1,   '2026-05-23', '賞味期限'),
(1, 4, 1, '卵',       10,  '2026-05-24', '賞味期限'), 
(1, 3, 1, 'サーモン', 2,   '2026-05-26', '消費期限');