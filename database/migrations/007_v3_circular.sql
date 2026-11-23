-- MEMORii · 007 v3: product kinds (clothing / bags / accessories / home), Rewear & Swap details,
-- favourites, outfits, payment settings and transfer slips. Indicative prices — validate with operations.

ALTER TABLE design_templates ADD COLUMN kind VARCHAR(6) NOT NULL DEFAULT 'wear' AFTER slug,
  ADD COLUMN image_key VARCHAR(40) NOT NULL DEFAULT '' AFTER kind;
UPDATE design_templates SET kind = 'bag' WHERE slug = 'tote-bag';
UPDATE design_templates SET image_key = CASE slug
  WHEN 'jeans-to-skirt' THEN 'skirt' WHEN 'denim-dress' THEN 'dress' WHEN 'two-piece' THEN 'model2' WHEN 'cropped-jacket' THEN 'cropped'
  WHEN 'denim-vest' THEN 'vest' WHEN 'patchwork-jacket' THEN 'patchwork' WHEN 'tote-bag' THEN 'tote' WHEN 'alteration' THEN 'jeans'
  WHEN 'repair-embellish' THEN 'mended' ELSE 'tote' END;

INSERT INTO design_templates (id, slug, kind, image_key, name_th, name_en, description_th, description_en, compatible_categories, difficulty, required_skill, extra_materials, price_min_thb, price_max_thb, feasibility_note_th, feasibility_note_en, sort_order) VALUES
 (UUID(),'shirt-to-dress','wear','shirtdress','เชิ้ตเป็นเดรส','Shirt to dress','ต่อความยาวและเพิ่มสายผูกเอว เปลี่ยนเชิ้ตตัวโคร่งเป็นเชิ้ตเดรส','Lengthen and add a waist tie to turn an oversized shirt into a shirt-dress','["shirt","denim_shirt"]','medium','garment_reconstruction','["matching fabric","tie belt"]',1200,2400,'ต้องมีผ้าเพิ่มที่สีเข้ากัน','Needs matching extra fabric',100),
 (UUID(),'crossbody-bag','bag','crossbody','กระเป๋าสะพายข้างจากยีนส์','Denim crossbody bag','เก็บกระเป๋าและตะเข็บเดิมของยีนส์ไว้ด้านหน้า เพิ่มซับใน ซิป และสายสะพายปรับได้','Keeps the original jeans pockets and seams on the front, with lining, zip and an adjustable strap','["jeans","trousers","denim_skirt","denim_jacket"]','medium','denim_repair','["lining","zip","strap","hardware"]',1200,2600,'ตำแหน่งกระเป๋าขึ้นกับยีนส์ตัวจริง','Pocket placement depends on the actual jeans',110),
 (UUID(),'backpack','bag','backpack','เป้แพตช์เวิร์ก','Patchwork backpack','เป้ขนาดเล็กแบบหูรูดพร้อมฝาปิด ต่อจากเดนิมหลายเฉด','Small drawstring backpack with a flap, pieced from several denim washes','["jeans","denim_jacket","trousers","denim_skirt"]','high','advanced_upcycling','["additional denim","lining","webbing straps","cord"]',1800,3800,'มักต้องใช้ยีนส์มากกว่า 1 ตัว','Usually needs more than one garment',120),
 (UUID(),'pouch-set','acc','accessories','กระเป๋าใส่ของและซองบัตร','Zip pouch & card holder','กระเป๋าซิปใบเล็กและซองใส่บัตร ใช้เศษผ้าได้แม้ผ้าเสียหายบางส่วน','A small zip pouch and card holder — works even with partly damaged fabric','["jeans","denim_jacket","denim_skirt","denim_shirt","shirt","trousers","skirt","dress","other"]','low','basic_alteration','["zip","interfacing"]',350,900,'','',130),
 (UUID(),'bucket-hat','acc','accessories','หมวกบักเก็ต','Bucket hat','หมวกบักเก็ตต่อชิ้นจากผ้าเดิม เย็บปีกหลายแถว','Panelled bucket hat with a multi-stitched brim','["jeans","denim_jacket","denim_shirt","shirt","trousers"]','medium','garment_reconstruction','["interfacing","lining"]',600,1200,'ต้องวัดรอบศีรษะก่อนผลิต','Head circumference is needed before production',140),
 (UUID(),'scrunchie-set','acc','accessories','ยางรัดผมผ้า (เซ็ต)','Scrunchie set','ยางรัดผมจากเสื้อเชิ้ต เดรส หรือเศษเดนิม','Scrunchies from shirts, dresses or denim offcuts','["shirt","denim_shirt","tshirt","dress","skirt","other"]','low','basic_alteration','["elastic"]',150,400,'','',150),
 (UUID(),'cushion-cover','home','home','ปลอกหมอนอิงแพตช์เวิร์ก','Patchwork cushion cover','ปลอกหมอนอิงต่อชิ้นพร้อมงานปักซาชิโกะ ใส่ซิปด้านหลัง','Pieced cushion cover with sashiko stitching and a back zip','["jeans","denim_jacket","denim_shirt","shirt","trousers"]','low','denim_repair','["zip","sashiko thread","cushion insert (optional)"]',500,1200,'ไส้หมอนไม่รวมในราคา เว้นแต่ระบุในใบเสนอราคา','Insert not included unless quoted',160),
 (UUID(),'apron','home','home','ผ้ากันเปื้อนยีนส์','Denim apron','ผ้ากันเปื้อนเต็มตัวจากยีนส์ เก็บกระเป๋าหลังเดิมไว้ใช้งาน','Full apron from jeans, keeping the back pockets usable','["jeans","trousers","denim_skirt","denim_shirt"]','medium','denim_repair','["webbing straps","hardware"]',700,1500,'','',170);

-- Rewear: size, moderation trail, buyer message, seller decision time
ALTER TABLE marketplace_listings ADD COLUMN size_label VARCHAR(20) NULL AFTER price_satang,
  ADD COLUMN moderated_by CHAR(36) CHARACTER SET ascii NULL, ADD COLUMN moderated_at DATETIME NULL,
  ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE marketplace_orders ADD COLUMN message VARCHAR(500) NULL, ADD COLUMN decided_at DATETIME NULL,
  ADD KEY mo_listing_idx (listing_id, created_at), ADD KEY mo_buyer_idx (buyer_id);

-- Swap: moderation trail and proposer message
ALTER TABLE swap_listings ADD COLUMN moderated_by CHAR(36) CHARACTER SET ascii NULL, ADD COLUMN moderated_at DATETIME NULL,
  ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, ADD KEY sl_status_idx (status, created_at);
ALTER TABLE swap_requests ADD COLUMN message VARCHAR(500) NULL, ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

CREATE TABLE favorites (
  user_id    CHAR(36) CHARACTER SET ascii NOT NULL,
  target     VARCHAR(80) NOT NULL,           -- 't:<template slug>' or 'm:<listing id>'
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, target),
  CONSTRAINT favorites_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE outfits (
  id         CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  owner_id   CHAR(36) CHARACTER SET ascii NOT NULL,
  name       VARCHAR(60) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY outfits_owner_idx (owner_id, created_at),
  CONSTRAINT outfits_owner_fk FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE outfit_items (
  outfit_id  CHAR(36) CHARACTER SET ascii NOT NULL,
  garment_id CHAR(36) CHARACTER SET ascii NOT NULL,
  sort_order SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (outfit_id, garment_id),
  CONSTRAINT outfit_items_outfit_fk FOREIGN KEY (outfit_id) REFERENCES outfits(id) ON DELETE CASCADE,
  CONSTRAINT outfit_items_garment_fk FOREIGN KEY (garment_id) REFERENCES garments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Key/value settings edited by admins (e.g. bank transfer details shown to customers).
CREATE TABLE app_settings (
  k          VARCHAR(60) NOT NULL PRIMARY KEY,
  v          JSON NOT NULL,
  updated_by CHAR(36) CHARACTER SET ascii NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Customer-uploaded transfer slips. Evidence only: a payment exists only when staff record one.
CREATE TABLE payment_slips (
  id           CHAR(36) CHARACTER SET ascii PRIMARY KEY,
  order_id     CHAR(36) CHARACTER SET ascii NOT NULL,
  uploaded_by  CHAR(36) CHARACTER SET ascii NOT NULL,
  storage_path VARCHAR(200) NOT NULL,
  mime_type    VARCHAR(20) NOT NULL,
  size_bytes   INT NOT NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY payment_slips_order_idx (order_id),
  CONSTRAINT payment_slips_order_fk FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE RESTRICT,
  CONSTRAINT payment_slips_user_fk FOREIGN KEY (uploaded_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notification read marker per user (notifications themselves are derived from history tables).
ALTER TABLE users ADD COLUMN notifications_seen_at DATETIME NULL;
