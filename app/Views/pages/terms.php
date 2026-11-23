<?php use App\Core\{Env, View}; View::$title = L('ข้อกำหนดการใช้งาน', 'Terms of use');
$op = Env::get('OPERATOR_NAME', '') ?: 'MEMORii'; $th = L('1', '') === '1'; ?>
<article class="w-md prose card">
<?php if ($th): ?>
<h1>ข้อกำหนดการใช้งาน</h1>
<p class="small muted">ผู้ให้บริการ: <?= e($op) ?></p>
<h2>บริการออกแบบใหม่ (Redesign)</h2>
<ul><li>ผลประเมินและช่วงราคาบนเว็บเป็นข้อมูลเบื้องต้น ไม่ใช่ข้อเสนอผูกพัน ราคาที่ผูกพันคือใบเสนอราคาที่คุณกดอนุมัติ</li>
<li>หลังได้รับเสื้อผ้า ช่างจะตรวจตัวจริง หากทำตามแบบไม่ได้หรือต้องเปลี่ยนราคา เราจะแจ้งและขออนุมัติก่อนเสมอ หรือคุณเลือกขอคืนเงินได้</li>
<li>การตัดหรือแปลงผ้าย้อนกลับไม่ได้ ผลงานจริงอาจแตกต่างจากภาพตัวอย่างตามผ้าและสภาพเสื้อผ้า</li>
<li>การชำระเงินทำโดยโอนเข้าบัญชีที่แสดงในหน้าคำสั่งซื้อเท่านั้น งานจะเริ่มเมื่อทีมงานยืนยันยอดแล้ว</li></ul>
<h2>ตลาดมือสอง (Rewear) และ Swap</h2>
<ul><li>ผู้ลงประกาศต้องเป็นเจ้าของสินค้า ใช้รูปจริง และเปิดเผยตำหนิตามจริง ประกาศต้องผ่านการตรวจก่อนแสดง</li>
<li>การซื้อขายและแลกเปลี่ยนเป็นข้อตกลงระหว่างสมาชิก <?= e($op) ?> ไม่ได้เป็นคู่สัญญา ไม่รับชำระเงินแทน และไม่รับประกันสินค้าระหว่างสมาชิก</li>
<li>เราอาจลบประกาศหรือระงับบัญชีที่ให้ข้อมูลเท็จ หลอกลวง หรือขายสินค้าต้องห้าม/ละเมิดลิขสิทธิ์</li></ul>
<h2>เนื้อหาของคุณ</h2>
<p>คุณยังเป็นเจ้าของรูปและข้อความที่อัปโหลด และอนุญาตให้เราใช้เท่าที่จำเป็นเพื่อให้บริการ เราจะไม่นำรูปของคุณไปใช้โฆษณาหากไม่ได้รับความยินยอม</p>
<h2>ข้อจำกัดความรับผิด</h2>
<p>เราให้บริการตามสภาพที่เป็น และรับผิดตามที่กฎหมายไทยกำหนด ข้อกำหนดนี้อยู่ภายใต้กฎหมายไทย</p>
<?php else: ?>
<h1>Terms of use</h1>
<p class="small muted">Provider: <?= e($op) ?></p>
<h2>Redesign service</h2>
<ul><li>Assessments and price ranges on the site are preliminary and not binding offers. The binding price is the quotation you approve.</li>
<li>After we receive your garment it is inspected. If the design cannot be made as quoted or the price must change, we ask for your approval first, or you may request a refund.</li>
<li>Cutting and reworking fabric cannot be undone. Results can differ from sample images depending on your fabric and its condition.</li>
<li>Payment is by transfer to the account shown on your order page only. Work starts once our team confirms the payment.</li></ul>
<h2>Rewear and Swap</h2>
<ul><li>Sellers must own the item, use real photos and disclose flaws honestly. Listings are reviewed before they go live.</li>
<li>Sales and swaps are agreements between members. <?= e($op) ?> is not a party, does not hold payments and does not guarantee items exchanged between members.</li>
<li>We may remove listings or suspend accounts for false, fraudulent, prohibited or infringing items.</li></ul>
<h2>Your content</h2>
<p>You keep ownership of photos and text you upload and allow us to use them only as needed to provide the service. We won’t use your photos in advertising without your consent.</p>
<h2>Liability</h2>
<p>The service is provided as is, with liability as required by Thai law. These terms are governed by Thai law.</p>
<?php endif; ?>
</article>
