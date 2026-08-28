USE minipos;
CREATE TABLE IF NOT EXISTS user_permission_switch_states (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  switch_key VARCHAR(100) NOT NULL,
  is_enabled TINYINT(1) DEFAULT 1,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_switch (user_id, switch_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SHOW TABLES LIKE 'user_permission_switch_states';
