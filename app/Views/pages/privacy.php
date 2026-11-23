<?php use App\Core\{Env, View}; View::$title = L('นโยบายความเป็นส่วนตัว', 'Privacy policy');
$op = Env::get('OPERATOR_NAME', '') ?: 'MEMORii'; $mail = Env::get('CONTACT_EMAIL', ''); $th = L('1', '') === '1'; ?>
<article class="w-md prose card">
<?php if ($th): ?>
<h1>นโยบายความเป็นส่วนตัว</h1>
<p class="small muted">ใช้กับเว็บไซต์ MEMORii ซึ่งดำเนินการโดย <?= e($op) ?> (“เรา”) ตามพระราชบัญญัติคุ้มครองข้อมูลส่วนบุคคล พ.ศ. 2562</p>
<h2>ข้อมูลที่เราเก็บ</h2>
<ul><li>ข้อมูลบัญชี: อีเมล ชื่อที่แสดง เบอร์โทร (ถ้าให้ไว้) และรหัสผ่านที่เข้ารหัสแบบทางเดียว</li>
<li>ข้อมูลเสื้อผ้า: รูปถ่าย ประเภท เนื้อผ้า สภาพ ขนาด และเรื่องราวที่คุณเลือกกรอก</li>
<li>คำขอออกแบบ ข้อความ ใบเสนอราคา คำสั่งซื้อ และสลิปการโอนที่คุณแนบ</li>
<li>ประกาศขาย/แลก คำขอซื้อ ข้อเสนอแลก รายการที่บันทึกไว้ และชุดที่จัดไว้</li>
<li>ข้อมูลทางเทคนิค: IP address ในบันทึกความปลอดภัยและการจำกัดการเข้าสู่ระบบ</li></ul>
<h2>เราใช้ข้อมูลเพื่อ</h2>
<ul><li>ให้บริการตามที่คุณขอ (ฐานสัญญา): ประเมินเสื้อผ้า ออกแบบ เสนอราคา ผลิต จัดส่ง และให้บริการตลาดมือสอง/แลกเปลี่ยน</li>
<li>ยืนยันการชำระเงินและเก็บหลักฐานทางบัญชี (หน้าที่ตามกฎหมาย)</li>
<li>ป้องกันการทุจริตและรักษาความปลอดภัยของระบบ (ประโยชน์โดยชอบด้วยกฎหมาย)</li></ul>
<p>เราไม่ขายข้อมูลส่วนบุคคล และไม่ใช้ข้อมูลเพื่อโฆษณาจากบุคคลที่สาม</p>
<h2>ใครเห็นข้อมูลของคุณ</h2>
<ul><li>รูปและข้อมูลเสื้อผ้าเห็นได้เฉพาะคุณ ทีมงาน และดีไซเนอร์/ช่างที่ได้รับมอบหมายงานของคุณ</li>
<li>เมื่อคุณลงประกาศขายหรือแลกและได้รับอนุมัติ สมาชิกที่เข้าสู่ระบบจะเห็นรูปของชิ้นนั้น ชื่อที่แสดง และรายละเอียดที่คุณเปิดเผย</li>
<li>ผู้ให้บริการโฮสติ้งและอีเมลที่เราใช้ ประมวลผลข้อมูลแทนเราภายใต้สัญญา</li></ul>
<h2>คุกกี้</h2>
<p>เราใช้เฉพาะคุกกี้ที่จำเป็น: คุกกี้เซสชันสำหรับการเข้าสู่ระบบและป้องกันการปลอมคำขอ และคุกกี้จำภาษา ไม่มีคุกกี้โฆษณาหรือติดตาม แบบอักษรโหลดจาก Google Fonts ซึ่งจะเห็น IP address ของคุณ</p>
<h2>ระยะเวลาเก็บ</h2>
<p>เก็บตลอดที่บัญชียังใช้งาน หลักฐานการชำระเงินและบันทึกคำสั่งซื้อเก็บตามระยะที่กฎหมายบัญชีและภาษีกำหนด เมื่อคุณลบเสื้อผ้า รูปของชิ้นนั้นจะถูกลบจากเซิร์ฟเวอร์</p>
<h2>สิทธิของคุณ</h2>
<p>คุณขอเข้าถึง ขอสำเนา แก้ไข ลบ คัดค้าน หรือขอให้ระงับการใช้ข้อมูล และร้องเรียนต่อสำนักงานคณะกรรมการคุ้มครองข้อมูลส่วนบุคคลได้ ติดต่อเรา<?= $mail ? ' ที่ ' . e($mail) : 'ผ่านหน้าติดต่อเรา' ?></p>
<?php else: ?>
<h1>Privacy policy</h1>
<p class="small muted">For the MEMORii website operated by <?= e($op) ?> (“we”), under Thailand’s Personal Data Protection Act B.E. 2562 (2019).</p>
<h2>What we collect</h2>
<ul><li>Account: email, display name, phone (optional) and a one-way hashed password</li>
<li>Garments: photos, category, fabric, condition, measurements and any story you add</li>
<li>Design requests, messages, quotations, orders and transfer slips you attach</li>
<li>Rewear/Swap listings, purchase requests, swap offers, saved items and outfits</li>
<li>Technical: IP address in security logs and login rate limiting</li></ul>
<h2>Why we use it</h2>
<ul><li>To provide what you ask for (contract): assessment, design, quotation, production, delivery, Rewear and Swap</li>
<li>To confirm payments and keep accounting records (legal obligation)</li>
<li>To prevent fraud and keep the service secure (legitimate interest)</li></ul>
<p>We do not sell personal data or use it for third-party advertising.</p>
<h2>Who sees your data</h2>
<ul><li>Garment photos and details: you, our staff, and the designer/maker assigned to your job</li>
<li>Approved Rewear/Swap listings: signed-in members see that item’s photos, your display name and what you disclosed</li>
<li>Our hosting and email providers process data on our behalf under contract</li></ul>
<h2>Cookies</h2>
<p>Only essential cookies: a session cookie for sign-in and request forgery protection, and a language cookie. No advertising or tracking cookies. Fonts load from Google Fonts, which sees your IP address.</p>
<h2>Retention</h2>
<p>While your account is active. Payment evidence and order records are kept as long as accounting and tax law requires. Deleting a garment deletes its photos from our server.</p>
<h2>Your rights</h2>
<p>You may request access, a copy, correction, deletion, objection or restriction, and complain to Thailand’s PDPC. Contact us<?= $mail ? ' at ' . e($mail) : ' via the contact page' ?>.</p>
<?php endif; ?>
</article>
