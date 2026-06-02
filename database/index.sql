SET NAMES utf8mb4;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE cooking_now; -- 追加した一時テーブルも一緒にクリアしておくと安全です
TRUNCATE TABLE ingredients;
TRUNCATE TABLE users;
TRUNCATE TABLE categories;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. カテゴリの登録
INSERT INTO categories (cid, category_name) VALUES 
(1, '肉類'),
(2, '野菜'),
(3, '魚介類'),
(4, 'その他');

-- 2. テストユーザーの登録（自動的に uid = 1 になります）
INSERT INTO users (name_id, email, password) VALUES 
('test2026', 'test@example.com', 'hashed_password_here');

-- 3. 在庫食材の登録（quantity を数値に変更、卵のカテゴリを4に修正）
INSERT INTO ingredients (user_id, category_id, food, quantity, expiration_date) VALUES 
(1, 1, '豚ひき肉', 200,  '2026-05-22'),
(1, 2, 'キャベツ', 1,    '2026-05-23'),
(1, 4, '卵',       10,   '2026-05-24'), -- カテゴリを「その他」、数量を10（個）にしました
(1, 3, 'サーモン', 2,    '2026-05-26'); -- カテゴリを「魚介類」にスッキリ修正