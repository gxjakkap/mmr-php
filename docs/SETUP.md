# Setup & deployment / การติดตั้งและเปิดใช้งานจริง

## Requirements
PHP 8.1+ with `mysqli`, `mbstring`, `fileinfo`, `openssl`, `json` (`gd` and `curl` only for tests);
MySQL 8.0.16+ or MariaDB 10.6+; Apache (mod_rewrite) or Nginx; HTTPS certificate (Let's Encrypt is fine).
Most Thai shared hosts with cPanel/DirectAdmin and PHP 8.1+ meet this. A small VPS (1 vCPU / 1 GB) is plenty to start.

## Go-live checklist (ภาษาไทย)
1. **โฮสติ้ง + โดเมน** — เช่าโฮสต์ที่มี PHP 8.1+ และ MySQL/MariaDB จดโดเมน แล้วเปิด HTTPS (ส่วนใหญ่มีปุ่ม Let's Encrypt ฟรี)
2. **อัปโหลดไฟล์** — แตกไฟล์ zip ขึ้นโฮสต์ ตั้ง Document root ให้ชี้ที่โฟลเดอร์ `public/` (ถ้าตั้งไม่ได้ ไฟล์ `.htaccess` ที่ root จะส่งต่อให้และบล็อกโฟลเดอร์ภายใน)
3. **ฐานข้อมูล** — สร้าง database + user ในแผงควบคุม (utf8mb4) แล้วคัดลอก `.env.example` เป็น `.env` และกรอก `DB_*`
4. **ตั้งค่า `.env` สำหรับใช้งานจริง** — `APP_URL=https://โดเมนของคุณ`, `APP_DEBUG=false`, `SESSION_SECURE=true`,
   `MAIL_DRIVER=smtp` + `SMTP_*` (เช่น Google Workspace: smtp.gmail.com พอร์ต 587 ใช้ App Password), `MAIL_FROM` เป็นอีเมลโดเมนของคุณ,
   `OPERATOR_NAME` (ชื่อบุคคล/บริษัทผู้ให้บริการ), `CONTACT_EMAIL`, `CONTACT_LINE`, `CONTACT_PHONE`
5. **สร้างตาราง** — รัน `php bin/migrate.php` (ผ่าน SSH หรือ Terminal ในแผงควบคุม)
6. **สิทธิ์โฟลเดอร์** — ให้เว็บเซิร์ฟเวอร์เขียน `storage/uploads` และ `storage/logs` ได้ (`chmod -R 750 storage`)
7. **แอดมินคนแรก** — สมัครสมาชิกบนเว็บ ยืนยันอีเมล แล้วรัน `php bin/create-admin.php you@example.com`
8. **ตั้งค่าในเว็บ** — แอดมิน → ตั้งค่า: กรอกบัญชีรับโอน/PromptPay · แอดมิน → ผู้ใช้: ให้บทบาทดีไซเนอร์/ช่าง · แก้โปรไฟล์ดีไซเนอร์ที่จะโชว์หน้าแรก
9. **ทดสอบจริงก่อนประกาศ** — สมัครด้วยอีเมลจริง (ต้องได้รับอีเมลยืนยัน), เพิ่มเสื้อผ้า+รูปจากมือถือ, ส่งคำขอ, ทำใบเสนอราคา, อนุมัติ, แนบสลิป, บันทึกยอด, ลงขาย/อนุมัติประกาศ
10. **ข้อกฎหมาย** — หน้า `/privacy` และ `/terms` เป็นร่างที่เขียนตามสิ่งที่ระบบทำจริง **ควรให้นักกฎหมายตรวจก่อนเปิดสาธารณะ** (PDPA, การรับชำระเงิน, การซื้อขายระหว่างสมาชิก)
11. **ราคา** — ช่วงราคาเทมเพลตใน `database/migrations/006` และ `007` เป็นตัวเลขตัวอย่าง แก้ในตาราง `design_templates` ให้ตรงต้นทุนจริง
12. **สำรองข้อมูล** — ตั้ง backup รายวันทั้ง database **และ** `storage/uploads` (รูปลูกค้าอยู่ที่นี่ ไม่ได้อยู่ใน database)

## Install (developer)
```bash
cp .env.example .env          # set DB_*, APP_URL, MAIL_*
mysql -u root -p -e "CREATE DATABASE memorii CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  CREATE USER 'memorii'@'localhost' IDENTIFIED BY '…'; GRANT ALL ON memorii.* TO 'memorii'@'localhost';"
php bin/migrate.php
chmod -R 750 storage && chown -R www-data storage   # web server must write storage/uploads and storage/logs
```
Local development: `php -S localhost:8000 -t public public/router.php`

## Web server
**Document root must be `/public`.** Apache: `public/.htaccess` routes to `index.php`.
If a shared host cannot change the document root, the root `.htaccess` forwards to `/public` and blocks `app/`, `storage/`, `.env` etc.

Nginx:
```nginx
root /var/www/memorii/public;
location / { try_files $uri /index.php?$query_string; }
location ~ \.php$ { include fastcgi_params; fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; fastcgi_pass unix:/run/php/php8.3-fpm.sock; }
location /assets/ { expires 30d; }
client_max_body_size 70m;   # 8 photos × 8 MB
```
PHP ini: `upload_max_filesize = 8M`, `post_max_size = 70M`, `max_file_uploads = 8`.

## Email
Registration requires email verification, so production **must** send real mail. `MAIL_DRIVER=smtp` with
`SMTP_HOST/PORT/USER/PASS` (587 STARTTLS or 465 TLS). Set SPF/DKIM for your domain so verification mails are not marked as spam.

## Production checklist
- HTTPS only (`SESSION_SECURE=true`), `APP_DEBUG=false`
- `MAIL_DRIVER=smtp` tested with a real inbox
- `OPERATOR_NAME`, `CONTACT_*` filled; privacy/terms reviewed by a lawyer
- Bank/PromptPay details entered in Admin → Settings
- Daily backup of the database **and** `storage/uploads`; rotate `storage/logs/*`
