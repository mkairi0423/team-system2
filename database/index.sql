
SET NAMES utf8mb4;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE ingredients;
TRUNCATE TABLE users;
TRUNCATE TABLE categories;
SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO categories (cname) VALUES 
('肉・魚類'),
('野菜・果物類'),
('卵・乳製品・大豆'),
('炭水化物'),
('調味料'),
('甘いもの・お菓子');

INSERT INTO users (name_id, email, password) VALUES 
('test2026', 'test@example.com', 'hashed_password_here');

INSERT INTO ingredients (user_id, category_id, food, quantity, expiration_date) VALUES 
(1, 1, '豚ひき肉', '200g', '2026-05-22'),
(1, 2, 'キャベツ', '1玉', '2026-05-23'),
(1, 3, '卵', '1パック', '2026-05-24'),
(1, 1, 'サーモン', '2切れ', '2026-05-26');