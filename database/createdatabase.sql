-- ====================================================================
-- 食材・在庫一括管理システム データベース設計
-- ====================================================================

-- 1. ユーザー管理テーブル (既存の想定)
-- ※すでに存在している場合はこのCREATE文はスキップしてください。
CREATE TABLE users (
    uid BIGINT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- 2. 食材カテゴリマスタ (野菜、肉、魚、調味料など)
-- ※すでに存在している場合はこのCREATE文はスキップしてください。
CREATE TABLE categories (
    cid INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- 3. 🔥 保管場所マスタ（冷蔵庫、冷凍庫、常温などを管理）
-- ここで場所を一括定義することで、将来「野菜室」や「パントリー」が増えても対応できます。
CREATE TABLE storage_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    location_name VARCHAR(50) NOT NULL COMMENT '冷蔵庫、冷凍庫、常温、野菜室、チルド室 など',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- 4. 🛒 在庫食材管理テーブル（すべての食材をここで一括管理）
-- `storage_location_id` カラムによって、その食材がどこにあるかを判別します。
CREATE TABLE ingredients (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL COMMENT '所有しているユーザーのID',
    category_id INT NOT NULL COMMENT '食材のカテゴリID',
    storage_location_id INT NOT NULL COMMENT '🔥 保管場所マスタ(冷蔵庫/冷凍庫など)への外部キー',
    food_name VARCHAR(100) NOT NULL COMMENT '食材名（例: ニンジン、豚バラ肉）',
    quantity INT NULL COMMENT 'グラム数または個数',
    expiration_date DATE NULL COMMENT '期限の年月日',
    term_type VARCHAR(20) NOT NULL DEFAULT '賞味期限' COMMENT '賞味期限 または 消費期限',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- 外部キー制約（紐づく親データが消えたときの挙動を設定）
    FOREIGN KEY (user_id) REFERENCES users(uid) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(cid) ON DELETE RESTRICT,
    FOREIGN KEY (storage_location_id) REFERENCES storage_locations(id) ON DELETE RESTRICT
) ENGINE=InnoDB;