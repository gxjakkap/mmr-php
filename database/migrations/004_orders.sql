-- MEMORii · 004 quotations, orders, payments
CREATE TABLE order_transitions (
  from_status VARCHAR(20) NOT NULL,
  to_status   VARCHAR(20) NOT NULL,
  actor       VARCHAR(10) NOT NULL,
  PRIMARY KEY (from_status, to_status, actor)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- BEGIN order_transitions
INSERT INTO order_transitions (from_status, to_status, actor) VALUES
  ('AWAITING_PAYMENT','PAYMENT_CONFIRMED','system'),
  ('AWAITING_PAYMENT','CANCELLED','customer'),
  ('AWAITING_PAYMENT','CANCELLED','admin'),
  ('PAYMENT_CONFIRMED','AWAITING_GARMENT','admin'),
  ('AWAITING_GARMENT','GARMENT_RECEIVED','admin'),
  ('GARMENT_RECEIVED','PHYSICAL_INSPECTION','admin'),
  ('GARMENT_RECEIVED','PHYSICAL_INSPECTION','tailor'),
  ('PHYSICAL_INSPECTION','DESIGN_FINALIZATION','admin'),
  ('PHYSICAL_INSPECTION','DESIGN_FINALIZATION','tailor'),
  ('PHYSICAL_INSPECTION','REFUND_REVIEW','admin'),
  ('DESIGN_FINALIZATION','IN_PRODUCTION','admin'),
  ('IN_PRODUCTION','QUALITY_CONTROL','tailor'),
  ('IN_PRODUCTION','QUALITY_CONTROL','admin'),
  ('QUALITY_CONTROL','READY_TO_SHIP','qc'),
  ('QUALITY_CONTROL','REWORK_REQUIRED','qc'),
  ('REWORK_REQUIRED','IN_PRODUCTION','tailor'),
  ('REWORK_REQUIRED','IN_PRODUCTION','admin'),
  ('READY_TO_SHIP','SHIPPED','admin'),
  ('SHIPPED','DELIVERED','admin'),
  ('DELIVERED','COMPLETED','customer'),
  ('DELIVERED','COMPLETED','admin'),
  ('DELIVERED','REFUND_REVIEW','customer'),
  ('DELIVERED','REFUND_REVIEW','admin'),
  ('PAYMENT_CONFIRMED','REFUND_REVIEW','admin'),
  ('AWAITING_GARMENT','REFUND_REVIEW','admin'),
  ('REFUND_REVIEW','CANCELLED','admin'),
  ('REFUND_REVIEW','COMPLETED','admin');
-- END order_transitions

-- Quotations are never edited after creation except their status; a revision is a new row.
CREATE TABLE quotations (
  id                CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  request_id        CHAR(36) CHARACTER SET ascii NOT NULL,
  design_version_id CHAR(36) CHARACTER SET ascii NULL,
  revision_no       INT NOT NULL,
  supersedes_id     CHAR(36) CHARACTER SET ascii NULL,
  prepared_by       CHAR(36) CHARACTER SET ascii NOT NULL,
  status            VARCHAR(12) NOT NULL DEFAULT 'sent',
  currency          CHAR(3) NOT NULL DEFAULT 'THB',
  subtotal_satang   BIGINT NOT NULL,
  discount_satang   BIGINT NOT NULL DEFAULT 0,
  tax_rate_bp       INT NOT NULL DEFAULT 700,
  tax_satang        BIGINT NOT NULL,
  total_satang      BIGINT NOT NULL,
  is_provisional    TINYINT(1) NOT NULL DEFAULT 1,
  notes             TEXT NULL,
  expires_at        DATETIME NOT NULL,
  responded_at      DATETIME NULL,
  response_reason   VARCHAR(2000) NULL,
  created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY quotations_rev_uq (request_id, revision_no),
  CONSTRAINT quotations_request_fk FOREIGN KEY (request_id) REFERENCES design_requests(id) ON DELETE CASCADE,
  CONSTRAINT quotations_version_fk FOREIGN KEY (design_version_id) REFERENCES design_versions(id),
  CONSTRAINT quotations_prev_fk FOREIGN KEY (supersedes_id) REFERENCES quotations(id),
  CONSTRAINT quotations_preparer_fk FOREIGN KEY (prepared_by) REFERENCES users(id),
  CONSTRAINT quotations_status_ck CHECK (status IN ('sent','accepted','rejected','superseded','expired')),
  CONSTRAINT quotations_money_ck CHECK (subtotal_satang >= 0 AND discount_satang >= 0 AND tax_satang >= 0
                                        AND discount_satang <= subtotal_satang
                                        AND total_satang = subtotal_satang - discount_satang + tax_satang),
  CONSTRAINT quotations_tax_ck CHECK (tax_rate_bp BETWEEN 0 AND 3000),
  CONSTRAINT quotations_currency_ck CHECK (currency = 'THB')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Numbers on a quotation can never change, only status/response fields.
CREATE TRIGGER quotations_lock_numbers BEFORE UPDATE ON quotations FOR EACH ROW
BEGIN
  IF NEW.subtotal_satang <> OLD.subtotal_satang OR NEW.discount_satang <> OLD.discount_satang
     OR NEW.tax_satang <> OLD.tax_satang OR NEW.total_satang <> OLD.total_satang
     OR NEW.tax_rate_bp <> OLD.tax_rate_bp OR NEW.request_id <> OLD.request_id THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'quotation amounts are immutable; issue a revision';
  END IF;
  IF OLD.status IN ('accepted','rejected','superseded','expired') AND NEW.status <> OLD.status THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'quotation is final';
  END IF;
END;

CREATE TABLE quotation_items (
  id                CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  quotation_id      CHAR(36) CHARACTER SET ascii NOT NULL,
  kind              VARCHAR(14) NOT NULL,
  description       VARCHAR(300) NOT NULL,
  quantity          DECIMAL(10,2) NOT NULL,
  unit_price_satang BIGINT NOT NULL,
  line_total_satang BIGINT NOT NULL,
  sort_order        SMALLINT NOT NULL DEFAULT 0,
  KEY quotation_items_idx (quotation_id, sort_order),
  CONSTRAINT quotation_items_q_fk FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE CASCADE,
  CONSTRAINT quotation_items_kind_ck CHECK (kind IN ('design_fee','labor','material','shipping','platform_fee','discount','other')),
  CONSTRAINT quotation_items_num_ck CHECK (quantity > 0 AND unit_price_satang >= 0 AND line_total_satang >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TRIGGER quotation_items_no_update BEFORE UPDATE ON quotation_items FOR EACH ROW
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'quotation_items are immutable';

CREATE TABLE orders (
  id                  CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  order_no            VARCHAR(20) NOT NULL,
  customer_id         CHAR(36) CHARACTER SET ascii NOT NULL,
  request_id          CHAR(36) CHARACTER SET ascii NOT NULL,
  quotation_id        CHAR(36) CHARACTER SET ascii NOT NULL,
  status              VARCHAR(20) NOT NULL DEFAULT 'AWAITING_PAYMENT',
  total_satang        BIGINT NOT NULL,
  assigned_partner_id CHAR(36) CHARACTER SET ascii NULL,
  created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY orders_no_uq (order_no),
  UNIQUE KEY orders_request_uq (request_id),
  UNIQUE KEY orders_quotation_uq (quotation_id),
  KEY orders_customer_idx (customer_id, created_at),
  KEY orders_status_idx (status),
  CONSTRAINT orders_customer_fk FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT orders_request_fk FOREIGN KEY (request_id) REFERENCES design_requests(id) ON DELETE RESTRICT,
  CONSTRAINT orders_quotation_fk FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE RESTRICT,
  CONSTRAINT orders_partner_fk FOREIGN KEY (assigned_partner_id) REFERENCES production_partners(id),
  CONSTRAINT orders_total_ck CHECK (total_satang >= 0),
  CONSTRAINT orders_status_ck CHECK (status IN ('AWAITING_PAYMENT','PAYMENT_CONFIRMED','AWAITING_GARMENT','GARMENT_RECEIVED','PHYSICAL_INSPECTION','DESIGN_FINALIZATION','IN_PRODUCTION','QUALITY_CONTROL','REWORK_REQUIRED','READY_TO_SHIP','SHIPPED','DELIVERED','COMPLETED','CANCELLED','REFUND_REVIEW'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE conversations ADD CONSTRAINT conversations_order_fk FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE;

CREATE TABLE order_items (
  id                CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  order_id          CHAR(36) CHARACTER SET ascii NOT NULL,
  kind              VARCHAR(14) NOT NULL,
  description       VARCHAR(300) NOT NULL,
  quantity          DECIMAL(10,2) NOT NULL,
  unit_price_satang BIGINT NOT NULL,
  line_total_satang BIGINT NOT NULL,
  KEY order_items_idx (order_id),
  CONSTRAINT order_items_order_fk FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_status_history (
  id          CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  order_id    CHAR(36) CHARACTER SET ascii NOT NULL,
  from_status VARCHAR(20) NULL,
  to_status   VARCHAR(20) NOT NULL,
  actor_id    CHAR(36) CHARACTER SET ascii NULL,
  note        VARCHAR(2000) NULL,
  created_at  DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  KEY osh_order_idx (order_id, created_at),
  CONSTRAINT osh_order_fk FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TRIGGER osh_no_update BEFORE UPDATE ON order_status_history FOR EACH ROW
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'order_status_history is append-only';
CREATE TRIGGER osh_no_delete BEFORE DELETE ON order_status_history FOR EACH ROW
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'order_status_history is append-only';

CREATE TABLE payments (
  id            CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  order_id      CHAR(36) CHARACTER SET ascii NOT NULL,
  provider      VARCHAR(20) NOT NULL,
  provider_ref  VARCHAR(200) NULL,
  amount_satang BIGINT NOT NULL,
  direction     VARCHAR(6) NOT NULL DEFAULT 'charge',
  recorded_by   CHAR(36) CHARACTER SET ascii NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY payments_ref_uq (provider, provider_ref),
  KEY payments_order_idx (order_id),
  CONSTRAINT payments_order_fk FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE RESTRICT,
  CONSTRAINT payments_provider_ck CHECK (provider IN ('manual_transfer','demo','gateway')),
  CONSTRAINT payments_amount_ck CHECK (amount_satang > 0),
  CONSTRAINT payments_direction_ck CHECK (direction IN ('charge','refund'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TRIGGER payments_no_update BEFORE UPDATE ON payments FOR EACH ROW
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'payments is append-only';
CREATE TRIGGER payments_no_delete BEFORE DELETE ON payments FOR EACH ROW
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'payments is append-only';

CREATE TABLE payment_events (
  id         CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  payment_id CHAR(36) CHARACTER SET ascii NULL,
  order_id   CHAR(36) CHARACTER SET ascii NULL,
  event_type VARCHAR(60) NOT NULL,
  payload    JSON NOT NULL,
  verified   TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT payment_events_payment_fk FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE RESTRICT,
  CONSTRAINT payment_events_order_fk FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TRIGGER payment_events_no_update BEFORE UPDATE ON payment_events FOR EACH ROW
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'payment_events is append-only';
CREATE TRIGGER payment_events_no_delete BEFORE DELETE ON payment_events FOR EACH ROW
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'payment_events is append-only';
