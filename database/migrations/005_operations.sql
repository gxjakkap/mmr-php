-- MEMORii · 005 production, QC, shipping, marketplace, swap, support, carbon, notifications.
-- Schema now; UIs follow in M2/M3. Access rules live in app/Core/Policy.php.
CREATE TABLE production_jobs (
  id                CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  order_id          CHAR(36) CHARACTER SET ascii NOT NULL,
  partner_id        CHAR(36) CHARACTER SET ascii NOT NULL,
  required_skill    VARCHAR(30) NOT NULL,
  instructions      TEXT NOT NULL,
  feasibility       VARCHAR(14) NOT NULL DEFAULT 'pending',
  missing_materials JSON NOT NULL,
  week_start        DATE NOT NULL,
  created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY production_jobs_order_uq (order_id),
  CONSTRAINT production_jobs_order_fk FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE RESTRICT,
  CONSTRAINT production_jobs_partner_fk FOREIGN KEY (partner_id) REFERENCES production_partners(id),
  CONSTRAINT production_jobs_feas_ck CHECK (feasibility IN ('pending','confirmed','not_feasible'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE production_updates (
  id         CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  job_id     CHAR(36) CHARACTER SET ascii NOT NULL,
  author_id  CHAR(36) CHARACTER SET ascii NOT NULL,
  kind       VARCHAR(20) NOT NULL,
  body       TEXT NOT NULL,
  photo_path VARCHAR(200) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT production_updates_job_fk FOREIGN KEY (job_id) REFERENCES production_jobs(id) ON DELETE CASCADE,
  CONSTRAINT production_updates_kind_ck CHECK (kind IN ('progress','issue','feasibility','submitted_for_qc'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE qc_inspections (
  id           CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  order_id     CHAR(36) CHARACTER SET ascii NOT NULL,
  inspector_id CHAR(36) CHARACTER SET ascii NOT NULL,
  attempt_no   SMALLINT NOT NULL DEFAULT 1,
  result       VARCHAR(8) NULL,
  notes        TEXT NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  completed_at DATETIME NULL,
  UNIQUE KEY qc_attempt_uq (order_id, attempt_no),
  CONSTRAINT qc_order_fk FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE RESTRICT,
  CONSTRAINT qc_result_ck CHECK (result IS NULL OR result IN ('pass','fail','rework'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE qc_checklist_items (
  id            CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  inspection_id CHAR(36) CHARACTER SET ascii NOT NULL,
  check_key     VARCHAR(24) NOT NULL,
  result        VARCHAR(4) NOT NULL,
  note          VARCHAR(1000) NULL,
  photo_path    VARCHAR(200) NULL,
  UNIQUE KEY qc_item_uq (inspection_id, check_key),
  CONSTRAINT qc_item_insp_fk FOREIGN KEY (inspection_id) REFERENCES qc_inspections(id) ON DELETE CASCADE,
  CONSTRAINT qc_item_key_ck CHECK (check_key IN ('measurements','stitching','construction','design_accuracy','material_integrity','finishing','functional_usability','packaging_readiness')),
  CONSTRAINT qc_item_result_ck CHECK (result IN ('pass','fail','na'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE marketplace_listings (
  id                   CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  seller_id            CHAR(36) CHARACTER SET ascii NOT NULL,
  garment_id           CHAR(36) CHARACTER SET ascii NOT NULL,
  title                VARCHAR(120) NOT NULL,
  condition_disclosure TEXT NOT NULL,
  price_satang         BIGINT NOT NULL,
  status               VARCHAR(16) NOT NULL DEFAULT 'pending_review',
  moderation_note      TEXT NULL,
  created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY ml_status_idx (status, created_at),
  CONSTRAINT ml_seller_fk FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT ml_garment_fk FOREIGN KEY (garment_id) REFERENCES garments(id) ON DELETE RESTRICT,
  CONSTRAINT ml_price_ck CHECK (price_satang > 0),
  CONSTRAINT ml_status_ck CHECK (status IN ('pending_review','active','rejected','reserved','sold','withdrawn'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE marketplace_orders (
  id           CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  listing_id   CHAR(36) CHARACTER SET ascii NOT NULL,
  buyer_id     CHAR(36) CHARACTER SET ascii NOT NULL,
  status       VARCHAR(12) NOT NULL DEFAULT 'requested',
  price_satang BIGINT NOT NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT mo_listing_fk FOREIGN KEY (listing_id) REFERENCES marketplace_listings(id) ON DELETE RESTRICT,
  CONSTRAINT mo_buyer_fk FOREIGN KEY (buyer_id) REFERENCES users(id),
  CONSTRAINT mo_status_ck CHECK (status IN ('requested','accepted','declined','paid','shipped','delivered','completed','cancelled','disputed'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE swap_listings (
  id                   CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  owner_id             CHAR(36) CHARACTER SET ascii NOT NULL,
  garment_id           CHAR(36) CHARACTER SET ascii NOT NULL,
  condition_disclosure TEXT NOT NULL,
  looking_for          VARCHAR(500) NULL,
  status               VARCHAR(16) NOT NULL DEFAULT 'pending_review',
  created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT sl_owner_fk FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT sl_garment_fk FOREIGN KEY (garment_id) REFERENCES garments(id) ON DELETE RESTRICT,
  CONSTRAINT sl_status_ck CHECK (status IN ('pending_review','active','matched','withdrawn','rejected'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE swap_requests (
  id                 CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  listing_id         CHAR(36) CHARACTER SET ascii NOT NULL,
  requester_id       CHAR(36) CHARACTER SET ascii NOT NULL,
  offered_garment_id CHAR(36) CHARACTER SET ascii NOT NULL,
  status             VARCHAR(20) NOT NULL DEFAULT 'proposed',
  fee_satang         BIGINT NOT NULL DEFAULT 0,  -- swaps are not assumed free
  created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT sr_listing_fk FOREIGN KEY (listing_id) REFERENCES swap_listings(id) ON DELETE CASCADE,
  CONSTRAINT sr_requester_fk FOREIGN KEY (requester_id) REFERENCES users(id),
  CONSTRAINT sr_garment_fk FOREIGN KEY (offered_garment_id) REFERENCES garments(id),
  CONSTRAINT sr_status_ck CHECK (status IN ('proposed','accepted_by_owner','mutually_accepted','declined','shipping','completed','disputed','cancelled')),
  CONSTRAINT sr_fee_ck CHECK (fee_satang >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE shipments (
  id                   CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  order_id             CHAR(36) CHARACTER SET ascii NULL,
  marketplace_order_id CHAR(36) CHARACTER SET ascii NULL,
  swap_request_id      CHAR(36) CHARACTER SET ascii NULL,
  direction            VARCHAR(20) NOT NULL,
  carrier              VARCHAR(60) NULL,
  tracking_no          VARCHAR(80) NULL,
  status               VARCHAR(12) NOT NULL DEFAULT 'created',
  created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT shipments_order_fk FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE RESTRICT,
  CONSTRAINT shipments_mo_fk FOREIGN KEY (marketplace_order_id) REFERENCES marketplace_orders(id) ON DELETE RESTRICT,
  CONSTRAINT shipments_sr_fk FOREIGN KEY (swap_request_id) REFERENCES swap_requests(id) ON DELETE RESTRICT,
  CONSTRAINT shipments_one_ck CHECK ((order_id IS NOT NULL) + (marketplace_order_id IS NOT NULL) + (swap_request_id IS NOT NULL) = 1),
  CONSTRAINT shipments_dir_ck CHECK (direction IN ('inbound_garment','outbound_delivery','swap_leg','marketplace')),
  CONSTRAINT shipments_status_ck CHECK (status IN ('created','in_transit','delivered','exception','cancelled'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE shipment_events (
  id          CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  shipment_id CHAR(36) CHARACTER SET ascii NOT NULL,
  status      VARCHAR(20) NOT NULL,
  description VARCHAR(500) NULL,
  occurred_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  source      VARCHAR(16) NOT NULL DEFAULT 'manual',
  CONSTRAINT shipment_events_fk FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE CASCADE,
  CONSTRAINT shipment_events_src_ck CHECK (source IN ('manual','carrier_webhook','demo'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE support_tickets (
  id          CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  customer_id CHAR(36) CHARACTER SET ascii NOT NULL,
  order_id    CHAR(36) CHARACTER SET ascii NULL,
  subject     VARCHAR(200) NOT NULL,
  body        TEXT NOT NULL,
  status      VARCHAR(10) NOT NULL DEFAULT 'open',
  assignee_id CHAR(36) CHARACTER SET ascii NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT st_customer_fk FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT st_order_fk FOREIGN KEY (order_id) REFERENCES orders(id),
  CONSTRAINT st_status_ck CHECK (status IN ('open','pending','resolved','closed'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Carbon: observed activity data, versioned factors, and calculations kept separate.
CREATE TABLE emission_factors (
  id              CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  activity_type   VARCHAR(40) NOT NULL,
  unit            VARCHAR(20) NOT NULL,
  kgco2e_per_unit DECIMAL(14,6) NOT NULL,
  source          VARCHAR(300) NOT NULL,
  geography       VARCHAR(20) NOT NULL,
  valid_year      SMALLINT NOT NULL,
  method          VARCHAR(300) NOT NULL,
  version         VARCHAR(20) NOT NULL,
  is_demo         TINYINT(1) NOT NULL DEFAULT 0,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY ef_uq (activity_type, geography, valid_year, version),
  CONSTRAINT ef_value_ck CHECK (kgco2e_per_unit >= 0 AND valid_year BETWEEN 1990 AND 2100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE activity_records (
  id            CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  order_id      CHAR(36) CHARACTER SET ascii NULL,
  garment_id    CHAR(36) CHARACTER SET ascii NULL,
  activity_type VARCHAR(40) NOT NULL,
  quantity      DECIMAL(14,4) NOT NULL,
  unit          VARCHAR(20) NOT NULL,
  data_quality  VARCHAR(10) NOT NULL,
  recorded_by   CHAR(36) CHARACTER SET ascii NULL,
  note          TEXT NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT ar_order_fk FOREIGN KEY (order_id) REFERENCES orders(id),
  CONSTRAINT ar_garment_fk FOREIGN KEY (garment_id) REFERENCES garments(id),
  CONSTRAINT ar_quality_ck CHECK (data_quality IN ('measured','estimated','assumed') AND quantity >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE carbon_calculations (
  id          CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  order_id    CHAR(36) CHARACTER SET ascii NULL,
  calc_type   VARCHAR(24) NOT NULL,
  kgco2e      DECIMAL(14,4) NOT NULL,
  inputs      JSON NOT NULL,
  method_note TEXT NOT NULL,
  is_verified TINYINT(1) NOT NULL DEFAULT 0,
  created_by  CHAR(36) CHARACTER SET ascii NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT cc_order_fk FOREIGN KEY (order_id) REFERENCES orders(id),
  CONSTRAINT cc_type_ck CHECK (calc_type IN ('estimated_emissions','baseline_assumption','potential_avoided')),
  CONSTRAINT cc_unverified_ck CHECK (is_verified = 0) -- no verification workflow exists yet
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
  id         CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  user_id    CHAR(36) CHARACTER SET ascii NOT NULL,
  kind       VARCHAR(40) NOT NULL,
  payload    JSON NOT NULL,
  read_at    DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY notifications_user_idx (user_id, created_at),
  CONSTRAINT notifications_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
