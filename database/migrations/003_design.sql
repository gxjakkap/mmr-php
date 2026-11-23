-- MEMORii · 003 design: staff records, templates, requests, proposals, conversations
CREATE TABLE designers (
  id          CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  user_id     CHAR(36) CHARACTER SET ascii NOT NULL,
  public_name VARCHAR(80) NOT NULL,
  bio         TEXT NULL,
  specialties JSON NOT NULL,
  is_active   TINYINT(1) NOT NULL DEFAULT 1,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY designers_user_uq (user_id),
  CONSTRAINT designers_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contact details are internal: never selected by customer-facing queries.
CREATE TABLE production_partners (
  id            CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  user_id       CHAR(36) CHARACTER SET ascii NOT NULL,
  business_name VARCHAR(120) NOT NULL,
  contact_phone VARCHAR(30) NULL,
  contact_email VARCHAR(254) NULL,
  address       TEXT NULL,
  is_verified   TINYINT(1) NOT NULL DEFAULT 0,
  is_active     TINYINT(1) NOT NULL DEFAULT 1,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY partners_user_uq (user_id),
  CONSTRAINT partners_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE partner_skills (
  id          CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  partner_id  CHAR(36) CHARACTER SET ascii NOT NULL,
  skill       VARCHAR(30) NOT NULL,
  verified_at DATETIME NULL,
  verified_by CHAR(36) CHARACTER SET ascii NULL,
  UNIQUE KEY partner_skills_uq (partner_id, skill),
  CONSTRAINT partner_skills_partner_fk FOREIGN KEY (partner_id) REFERENCES production_partners(id) ON DELETE CASCADE,
  CONSTRAINT partner_skills_skill_ck CHECK (skill IN ('basic_alteration','denim_repair','pattern_making','garment_reconstruction','advanced_upcycling','complex_dressmaking'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE partner_capacity (
  id             CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  partner_id     CHAR(36) CHARACTER SET ascii NOT NULL,
  week_start     DATE NOT NULL,
  max_jobs       SMALLINT NOT NULL,
  committed_jobs SMALLINT NOT NULL DEFAULT 0,
  UNIQUE KEY partner_capacity_uq (partner_id, week_start),
  CONSTRAINT partner_capacity_partner_fk FOREIGN KEY (partner_id) REFERENCES production_partners(id) ON DELETE CASCADE,
  CONSTRAINT partner_capacity_ck CHECK (max_jobs BETWEEN 0 AND 200 AND committed_jobs >= 0 AND committed_jobs <= max_jobs)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE design_templates (
  id                    CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  slug                  VARCHAR(60) NOT NULL,
  name_th               VARCHAR(120) NOT NULL,
  name_en               VARCHAR(120) NOT NULL,
  description_th        TEXT NOT NULL,
  description_en        TEXT NOT NULL,
  compatible_categories JSON NOT NULL,
  difficulty            VARCHAR(6) NOT NULL,
  required_skill        VARCHAR(30) NOT NULL,
  extra_materials       JSON NOT NULL,
  price_min_thb         INT NOT NULL,
  price_max_thb         INT NOT NULL,
  feasibility_note_th   TEXT NOT NULL,
  feasibility_note_en   TEXT NOT NULL,
  is_active             TINYINT(1) NOT NULL DEFAULT 1,
  sort_order            SMALLINT NOT NULL DEFAULT 0,
  UNIQUE KEY design_templates_slug_uq (slug),
  CONSTRAINT design_templates_diff_ck CHECK (difficulty IN ('low','medium','high')),
  CONSTRAINT design_templates_price_ck CHECK (price_min_thb >= 0 AND price_max_thb >= price_min_thb)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE design_projects (
  id          CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  owner_id    CHAR(36) CHARACTER SET ascii NOT NULL,
  garment_id  CHAR(36) CHARACTER SET ascii NOT NULL,
  template_id CHAR(36) CHARACTER SET ascii NULL,
  mode        VARCHAR(12) NOT NULL,
  title       VARCHAR(120) NOT NULL,
  notes       TEXT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT design_projects_owner_fk FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT design_projects_garment_fk FOREIGN KEY (garment_id) REFERENCES garments(id) ON DELETE CASCADE,
  CONSTRAINT design_projects_template_fk FOREIGN KEY (template_id) REFERENCES design_templates(id),
  CONSTRAINT design_projects_mode_ck CHECK (mode IN ('template','ai_assisted','custom'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE design_requests (
  id                           CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  customer_id                  CHAR(36) CHARACTER SET ascii NOT NULL,
  garment_id                   CHAR(36) CHARACTER SET ascii NOT NULL,
  template_id                  CHAR(36) CHARACTER SET ascii NULL,
  mode                         VARCHAR(12) NOT NULL,
  title                        VARCHAR(120) NOT NULL,
  requirements                 TEXT NOT NULL,
  budget_max_thb               INT NULL,
  status                       VARCHAR(20) NOT NULL DEFAULT 'DRAFT',
  assigned_designer_id         CHAR(36) CHARACTER SET ascii NULL,
  requires_physical_inspection TINYINT(1) NOT NULL DEFAULT 1,
  submitted_at                 DATETIME NULL,
  created_at                   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at                   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY design_requests_customer_idx (customer_id, created_at),
  KEY design_requests_status_idx (status, submitted_at),
  KEY design_requests_designer_idx (assigned_designer_id),
  CONSTRAINT design_requests_customer_fk FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT design_requests_garment_fk FOREIGN KEY (garment_id) REFERENCES garments(id) ON DELETE RESTRICT,
  CONSTRAINT design_requests_template_fk FOREIGN KEY (template_id) REFERENCES design_templates(id),
  CONSTRAINT design_requests_designer_fk FOREIGN KEY (assigned_designer_id) REFERENCES designers(id),
  CONSTRAINT design_requests_mode_ck CHECK (mode IN ('template','ai_assisted','custom')),
  CONSTRAINT design_requests_budget_ck CHECK (budget_max_thb IS NULL OR budget_max_thb BETWEEN 0 AND 1000000),
  CONSTRAINT design_requests_status_ck CHECK (status IN ('DRAFT','SUBMITTED','UNDER_REVIEW','NEEDS_INFORMATION','FEASIBILITY_REVIEW','QUOTE_PREPARING','QUOTE_SENT','QUOTE_ACCEPTED','QUOTE_REJECTED','CANCELLED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A request may only reference a garment owned by its customer (defence in depth; services check too).
CREATE TRIGGER design_requests_garment_owner_ins BEFORE INSERT ON design_requests FOR EACH ROW
BEGIN
  IF NOT EXISTS (SELECT 1 FROM garments g WHERE g.id = NEW.garment_id AND g.owner_id = NEW.customer_id) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'garment does not belong to customer';
  END IF;
END;

CREATE TABLE design_request_status_history (
  id          CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  request_id  CHAR(36) CHARACTER SET ascii NOT NULL,
  from_status VARCHAR(20) NULL,
  to_status   VARCHAR(20) NOT NULL,
  actor_id    CHAR(36) CHARACTER SET ascii NULL,
  note        VARCHAR(2000) NULL,
  created_at  DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  KEY drsh_request_idx (request_id, created_at),
  CONSTRAINT drsh_request_fk FOREIGN KEY (request_id) REFERENCES design_requests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TRIGGER drsh_no_update BEFORE UPDATE ON design_request_status_history FOR EACH ROW
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'design_request_status_history is append-only';

CREATE TABLE design_versions (
  id                CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  request_id        CHAR(36) CHARACTER SET ascii NOT NULL,
  version_no        INT NOT NULL,
  author_id         CHAR(36) CHARACTER SET ascii NOT NULL,
  description       TEXT NOT NULL,
  difficulty        VARCHAR(6) NOT NULL,
  materials         JSON NOT NULL,
  feasibility_notes TEXT NOT NULL,
  is_ai_concept     TINYINT(1) NOT NULL DEFAULT 0,
  status            VARCHAR(12) NOT NULL DEFAULT 'proposed',
  created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY design_versions_uq (request_id, version_no),
  CONSTRAINT design_versions_request_fk FOREIGN KEY (request_id) REFERENCES design_requests(id) ON DELETE CASCADE,
  CONSTRAINT design_versions_author_fk FOREIGN KEY (author_id) REFERENCES users(id),
  CONSTRAINT design_versions_status_ck CHECK (status IN ('proposed','approved','rejected','superseded')),
  CONSTRAINT design_versions_diff_ck CHECK (difficulty IN ('low','medium','high'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE design_assets (
  id           CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  request_id   CHAR(36) CHARACTER SET ascii NULL,
  version_id   CHAR(36) CHARACTER SET ascii NULL,
  uploaded_by  CHAR(36) CHARACTER SET ascii NOT NULL,
  kind         VARCHAR(12) NOT NULL,
  storage_path VARCHAR(200) NOT NULL,
  mime_type    VARCHAR(20) NOT NULL,
  size_bytes   INT NOT NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY design_assets_path_uq (storage_path),
  CONSTRAINT design_assets_request_fk FOREIGN KEY (request_id) REFERENCES design_requests(id) ON DELETE CASCADE,
  CONSTRAINT design_assets_version_fk FOREIGN KEY (version_id) REFERENCES design_versions(id) ON DELETE CASCADE,
  CONSTRAINT design_assets_uploader_fk FOREIGN KEY (uploaded_by) REFERENCES users(id),
  CONSTRAINT design_assets_kind_ck CHECK (kind IN ('reference','sketch','ai_concept','progress')),
  CONSTRAINT design_assets_owner_ck CHECK ((request_id IS NOT NULL) + (version_id IS NOT NULL) >= 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE conversations (
  id                CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  design_request_id CHAR(36) CHARACTER SET ascii NULL,
  order_id          CHAR(36) CHARACTER SET ascii NULL,
  created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY conversations_request_uq (design_request_id),
  UNIQUE KEY conversations_order_uq (order_id),
  CONSTRAINT conversations_request_fk FOREIGN KEY (design_request_id) REFERENCES design_requests(id) ON DELETE CASCADE,
  CONSTRAINT conversations_one_ck CHECK ((design_request_id IS NOT NULL) + (order_id IS NOT NULL) = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE messages (
  id              CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  conversation_id CHAR(36) CHARACTER SET ascii NOT NULL,
  sender_id       CHAR(36) CHARACTER SET ascii NOT NULL,
  body            TEXT NOT NULL,
  is_internal     TINYINT(1) NOT NULL DEFAULT 0,
  created_at      DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  KEY messages_conv_idx (conversation_id, created_at),
  CONSTRAINT messages_conv_fk FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
  CONSTRAINT messages_sender_fk FOREIGN KEY (sender_id) REFERENCES users(id),
  CONSTRAINT messages_body_ck CHECK (CHAR_LENGTH(body) BETWEEN 1 AND 4000)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Allowed request moves. 'system' edges are reachable only through dedicated service methods.
-- app/Domain/Workflow.php mirrors this; tests/WorkflowParityTest.php fails if they drift.
CREATE TABLE request_transitions (
  from_status VARCHAR(20) NOT NULL,
  to_status   VARCHAR(20) NOT NULL,
  actor       VARCHAR(10) NOT NULL,
  PRIMARY KEY (from_status, to_status, actor)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- BEGIN request_transitions
INSERT INTO request_transitions (from_status, to_status, actor) VALUES
  ('DRAFT','SUBMITTED','customer'),
  ('DRAFT','CANCELLED','customer'),
  ('SUBMITTED','CANCELLED','customer'),
  ('SUBMITTED','UNDER_REVIEW','admin'),
  ('SUBMITTED','CANCELLED','admin'),
  ('UNDER_REVIEW','NEEDS_INFORMATION','admin'),
  ('UNDER_REVIEW','FEASIBILITY_REVIEW','admin'),
  ('UNDER_REVIEW','QUOTE_PREPARING','system'),
  ('UNDER_REVIEW','CANCELLED','admin'),
  ('NEEDS_INFORMATION','UNDER_REVIEW','customer'),
  ('NEEDS_INFORMATION','UNDER_REVIEW','admin'),
  ('NEEDS_INFORMATION','CANCELLED','customer'),
  ('NEEDS_INFORMATION','CANCELLED','admin'),
  ('FEASIBILITY_REVIEW','QUOTE_PREPARING','system'),
  ('FEASIBILITY_REVIEW','NEEDS_INFORMATION','admin'),
  ('FEASIBILITY_REVIEW','CANCELLED','admin'),
  ('QUOTE_PREPARING','NEEDS_INFORMATION','designer'),
  ('QUOTE_PREPARING','NEEDS_INFORMATION','admin'),
  ('QUOTE_PREPARING','QUOTE_SENT','system'),
  ('QUOTE_PREPARING','CANCELLED','admin'),
  ('QUOTE_SENT','QUOTE_ACCEPTED','system'),
  ('QUOTE_SENT','QUOTE_REJECTED','system'),
  ('QUOTE_SENT','CANCELLED','customer'),
  ('QUOTE_REJECTED','QUOTE_PREPARING','designer'),
  ('QUOTE_REJECTED','QUOTE_PREPARING','admin'),
  ('QUOTE_REJECTED','CANCELLED','customer'),
  ('QUOTE_REJECTED','CANCELLED','admin');
-- END request_transitions
CREATE TRIGGER drsh_no_delete BEFORE DELETE ON design_request_status_history FOR EACH ROW
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'design_request_status_history is append-only';
