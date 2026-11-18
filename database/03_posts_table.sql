-- =====================================================================
-- ACTIVITY 3 – posts + audit_logs tables with sample posts
-- Run in phpMyAdmin > wst2_auth > SQL.  Re-run any time to RESET the posts.
-- Needs the users table from Activity 1 with at least 2 student accounts.
-- =====================================================================
USE wst2_auth;

DROP TABLE IF EXISTS posts;
CREATE TABLE posts (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    title       VARCHAR(150) NOT NULL,
    body        TEXT NOT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- For "accounting" (Slide 23). You will write to this table in your fix.
CREATE TABLE IF NOT EXISTS audit_logs (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NULL,
    action      VARCHAR(50)  NOT NULL,
    details     VARCHAR(255) NULL,
    ip_address  VARCHAR(45)  NOT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Sample posts: two for the FIRST student account, two for the LAST student account.
SET @s1 = (SELECT MIN(id) FROM users WHERE role = 'student');
SET @s2 = (SELECT MAX(id) FROM users WHERE role = 'student');

INSERT INTO posts (user_id, title, body) VALUES
(@s1, 'XAMPP will not start Apache', 'Port 80 is already used. Any tips?'),
(@s1, 'Study group for the quiz',    'Who wants to review authorization tonight?'),
(@s2, 'password_hash vs md5',        'Why is md5 not enough for passwords?'),
(@s2, 'Sharing my notes on sessions','Remember to call session_regenerate_id(true) after login.');

-- Check who owns what:
SELECT p.id, p.title, u.username AS owner FROM posts p JOIN users u ON u.id = p.user_id;
