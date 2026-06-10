

ALTER TABLE users
  ADD COLUMN IF NOT EXISTS fcm_token VARCHAR(255) NULL DEFAULT NULL
  COMMENT 'Firebase Cloud Messaging token untuk push notification';


SHOW COLUMNS FROM users LIKE 'fcm_token';
