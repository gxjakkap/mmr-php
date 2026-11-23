-- MEMORii · 002 wardrobe & assessment
CREATE TABLE garments (
  id                 CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  owner_id           CHAR(36) CHARACTER SET ascii NOT NULL,
  name               VARCHAR(120) NOT NULL,
  category           VARCHAR(20) NOT NULL,
  fabric_primary     VARCHAR(20) NOT NULL,
  fabric_composition JSON NOT NULL,
  color              VARCHAR(40) NULL,
  measurements       JSON NOT NULL,
  `condition`        VARCHAR(10) NOT NULL,
  defects            JSON NOT NULL,
  condition_notes    TEXT NULL,
  sentimental_notes  TEXT NULL,
  approx_weight_g    INT NULL,
  is_demo            TINYINT(1) NOT NULL DEFAULT 0,
  created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY garments_owner_idx (owner_id, created_at),
  CONSTRAINT garments_owner_fk FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT garments_category_ck CHECK (category IN ('jeans','denim_jacket','denim_skirt','denim_shirt','dress','shirt','tshirt','jacket','skirt','trousers','bag','other')),
  CONSTRAINT garments_fabric_ck CHECK (fabric_primary IN ('denim','cotton','linen','wool','silk','polyester','blend','leather','other')),
  CONSTRAINT garments_condition_ck CHECK (`condition` IN ('excellent','good','fair','worn','damaged')),
  CONSTRAINT garments_name_ck CHECK (CHAR_LENGTH(name) >= 1),
  CONSTRAINT garments_weight_ck CHECK (approx_weight_g IS NULL OR approx_weight_g BETWEEN 1 AND 20000)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Files live in storage/uploads (outside the web root) and are streamed by MediaController after an access check.
CREATE TABLE garment_images (
  id           CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  garment_id   CHAR(36) CHARACTER SET ascii NOT NULL,
  owner_id     CHAR(36) CHARACTER SET ascii NOT NULL,
  storage_path VARCHAR(200) NOT NULL,
  mime_type    VARCHAR(20) NOT NULL,
  size_bytes   INT NOT NULL,
  sort_order   SMALLINT NOT NULL DEFAULT 0,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY garment_images_path_uq (storage_path),
  KEY garment_images_garment_idx (garment_id, sort_order),
  CONSTRAINT garment_images_garment_fk FOREIGN KEY (garment_id) REFERENCES garments(id) ON DELETE CASCADE,
  CONSTRAINT garment_images_owner_fk FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT garment_images_mime_ck CHECK (mime_type IN ('image/jpeg','image/png','image/webp')),
  CONSTRAINT garment_images_size_ck CHECK (size_bytes > 0 AND size_bytes <= 8388608)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE garment_assessments (
  id             CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  garment_id     CHAR(36) CHARACTER SET ascii NOT NULL,
  owner_id       CHAR(36) CHARACTER SET ascii NOT NULL,
  engine_version VARCHAR(20) NOT NULL,
  inputs         JSON NOT NULL,
  summary        JSON NOT NULL,
  is_preliminary TINYINT(1) NOT NULL DEFAULT 1,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY garment_assessments_garment_idx (garment_id, created_at),
  CONSTRAINT garment_assessments_garment_fk FOREIGN KEY (garment_id) REFERENCES garments(id) ON DELETE CASCADE,
  CONSTRAINT garment_assessments_owner_fk FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT garment_assessments_prelim_ck CHECK (is_preliminary = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE assessment_recommendations (
  id            CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  assessment_id CHAR(36) CHARACTER SET ascii NOT NULL,
  pathway       VARCHAR(10) NOT NULL,
  suitability   VARCHAR(12) NOT NULL,
  complexity    VARCHAR(6) NULL,
  cost_min_thb  INT NULL,
  cost_max_thb  INT NULL,
  rationale     JSON NOT NULL,
  advantages    JSON NOT NULL,
  disadvantages JSON NOT NULL,
  materials     JSON NOT NULL,
  uncertainty   VARCHAR(6) NOT NULL,
  next_steps    JSON NOT NULL,
  `rank`        SMALLINT NOT NULL,
  KEY assessment_recs_idx (assessment_id, `rank`),
  CONSTRAINT assessment_recs_fk FOREIGN KEY (assessment_id) REFERENCES garment_assessments(id) ON DELETE CASCADE,
  CONSTRAINT assessment_recs_pathway_ck CHECK (pathway IN ('reuse','repair','redesign','upcycle','rewear','swap','recycle')),
  CONSTRAINT assessment_recs_suit_ck CHECK (suitability IN ('recommended','possible','not_advised')),
  CONSTRAINT assessment_recs_unc_ck CHECK (uncertainty IN ('low','medium','high')),
  CONSTRAINT assessment_recs_cost_ck CHECK (cost_max_thb IS NULL OR cost_max_thb >= cost_min_thb)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
