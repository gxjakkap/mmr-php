-- MEMORii (PHP/MySQLi) · 001 core. MySQL 8.0.16+ / MariaDB 10.6+, InnoDB, utf8mb4.
-- UUIDs are generated in PHP (CHAR(36)). Money is BIGINT satang (1 THB = 100 satang).
-- MySQL has no row-level security: authorization is enforced in app/Services + app/Core/Policy.php.
-- The database still enforces integrity: FKs, CHECKs, uniqueness and append-only triggers.

CREATE TABLE users (
  id                CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  email             VARCHAR(254) NOT NULL,
  password_hash     VARCHAR(255) NOT NULL,
  email_verified_at DATETIME NULL,
  display_name      VARCHAR(80) NOT NULL DEFAULT '',
  phone             VARCHAR(20) NULL,
  locale            VARCHAR(2) NOT NULL DEFAULT 'th',
  is_active         TINYINT(1) NOT NULL DEFAULT 1,
  created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY users_email_uq (email),
  CONSTRAINT users_locale_ck CHECK (locale IN ('th','en'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_roles (
  id         CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  user_id    CHAR(36) CHARACTER SET ascii NOT NULL,
  role       VARCHAR(16) NOT NULL,
  granted_by CHAR(36) CHARACTER SET ascii NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY user_roles_uq (user_id, role),
  CONSTRAINT user_roles_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT user_roles_granter_fk FOREIGN KEY (granted_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT user_roles_role_ck CHECK (role IN ('customer','admin','designer','tailor','qc','support'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Email verification + password reset. Only a SHA-256 of the token is stored.
CREATE TABLE auth_tokens (
  id         CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  user_id    CHAR(36) CHARACTER SET ascii NOT NULL,
  purpose    VARCHAR(16) NOT NULL,
  token_hash CHAR(64) CHARACTER SET ascii NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at    DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY auth_tokens_hash_uq (token_hash),
  KEY auth_tokens_user_idx (user_id, purpose),
  CONSTRAINT auth_tokens_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT auth_tokens_purpose_ck CHECK (purpose IN ('verify_email','reset_password'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fixed-window rate limiting (login, register, reset, uploads).
CREATE TABLE rate_limits (
  bucket       VARCHAR(190) NOT NULL PRIMARY KEY,
  hits         INT UNSIGNED NOT NULL,
  window_start DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
  id          CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  actor_id    CHAR(36) CHARACTER SET ascii NULL,
  action      VARCHAR(80) NOT NULL,
  entity_type VARCHAR(40) NOT NULL,
  entity_id   CHAR(36) CHARACTER SET ascii NULL,
  detail      JSON NOT NULL,
  ip          VARCHAR(45) NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY audit_entity_idx (entity_type, entity_id),
  KEY audit_created_idx (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TRIGGER audit_logs_no_update BEFORE UPDATE ON audit_logs FOR EACH ROW
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'audit_logs is append-only';
CREATE TRIGGER audit_logs_no_delete BEFORE DELETE ON audit_logs FOR EACH ROW
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'audit_logs is append-only';
