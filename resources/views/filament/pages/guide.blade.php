<x-filament-panels::page>
    {{--
        App\Filament\Pages\Guide: how to use the panel, for staff who post. Written in Thai, as the panel is used.
        Each part is a section that opens; what is said follows who is reading ($forDepartment, $approval, $admin).
    --}}
    @php($step = 'margin: 0.5rem 0 0; padding-left: 1.25rem; display: grid; gap: 0.35rem; list-style: decimal;')
    @php($note = 'margin-top: 0.75rem; padding: 0.75rem 1rem; border-left: 3px solid rgb(234 179 8); background: rgba(234,179,8,.08); border-radius: 0.375rem;')

    <div style="display: grid; gap: 1rem; max-width: 52rem; line-height: 1.8">
        <x-filament::section>
            @if ($forDepartment)
                <p>คุณกำลังใช้<strong>บัญชีของหน่วยงาน {{ $department }}</strong> ลงเรื่องได้ในนามหน่วยงานนี้ และแก้หน้าโปรไฟล์ของหน่วยงานตัวเองได้</p>
            @elseif ($admin)
                <p>คุณเป็น<strong>ผู้ดูแลระบบ</strong> ทำได้ทุกอย่างในหลังบ้านนี้ รวมถึงตั้งค่าเว็บไซต์ จัดการบัญชีเจ้าหน้าที่ และอนุมัติเรื่องจากหน่วยงาน</p>
            @else
                <p>คุณเป็น<strong>ผู้เขียนเนื้อหาในนามโรงพยาบาล</strong> ลงประกาศได้ทุกประเภท จัดการ ITA เอกสารดาวน์โหลด ตารางคลินิก และอนุมัติเรื่องจากหน่วยงาน</p>
            @endif
        </x-filament::section>

        <x-filament::section heading="1. ลงประกาศ โปสเตอร์ หรือข่าวกิจกรรม" collapsible>
            <p>ทุกอย่างลงที่เมนูเดียวกัน: <strong>ประชาสัมพันธ์</strong> แล้วกดปุ่ม "สร้าง"</p>
            <ol style="{{ $step }}">
                <li>ใส่<strong>หัวข้อ</strong> ให้คนอ่านรู้ทันทีว่าเรื่องอะไร เช่น "งดให้บริการทันตกรรม วันศุกร์ที่ 16 ต.ค. 2569"</li>
                <li>เลือก<strong>ประเภท</strong>: ข่าวประชาสัมพันธ์ ความรู้สุขภาพ ข่าวกิจกรรม รับสมัครงาน หรือจัดซื้อจัดจ้าง</li>
                <li>ใส่เฉพาะสิ่งที่เรื่องนั้นมี ไม่ต้องกรอกทุกช่อง:
                    <ul style="margin-top: 0.25rem; padding-left: 1.25rem; list-style: disc;">
                        <li><strong>โปสเตอร์ / ภาพแผ่นเดียว</strong>: อัปโหลดที่ช่อง "ภาพหน้าปก / ภาพประกาศ" ใส่หัวข้อแล้วเผยแพร่ได้เลย</li>
                        <li><strong>ข้อความ</strong>: พิมพ์ที่ช่อง "รายละเอียด"</li>
                        <li><strong>ภาพกิจกรรมหลายภาพ</strong>: อัปโหลดที่ช่อง "อัลบั้มภาพ" ได้สูงสุด {{ \App\Models\Announcement::MAX_PHOTOS }} ภาพ</li>
                        <li><strong>เอกสาร</strong>: แนบ PDF หรือวางลิงก์ Google Drive (ไฟล์ใหญ่ควรใช้ Google Drive)</li>
                    </ul>
                </li>
                <li>เลือก<strong>สถานะ</strong>แล้วกดบันทึก</li>
            </ol>
            <p style="{{ $note }}">รูปที่อัปโหลดระบบจะย่อให้เอง ไม่ต้องย่อก่อน</p>
        </x-filament::section>

        <x-filament::section heading="2. สถานะของเรื่อง" collapsible collapsed>
            <ul style="display: grid; gap: 0.35rem; padding-left: 1.25rem; list-style: disc;">
                <li><strong>ฉบับร่าง</strong>: เก็บไว้เขียนต่อ ยังไม่มีใครเห็นนอกจากเจ้าหน้าที่</li>
                @if ($approval)
                    <li><strong>ส่งให้ตรวจ</strong>: ส่งให้งานประชาสัมพันธ์หรือผู้ดูแลตรวจ เมื่อได้รับอนุมัติจึงจะขึ้นเว็บไซต์</li>
                    <li><strong>ส่งกลับ</strong>: ผู้ตรวจขอให้แก้ไข เปิดเรื่องนั้นจะเห็นหมายเหตุของผู้ตรวจ แก้แล้วบันทึก เรื่องจะถูกส่งให้ตรวจอีกครั้ง</li>
                @endif
                <li><strong>เผยแพร่แล้ว</strong>: ขึ้นเว็บไซต์ตั้งแต่วันที่ในช่อง "แสดงตั้งแต่" ถ้าใส่วันในอนาคต เรื่องจะขึ้นเองเมื่อถึงวัน</li>
            </ul>
            @if ($approval)
                <p style="{{ $note }}">ทุกครั้งที่แก้ไขเรื่องที่เผยแพร่แล้ว เรื่องจะกลับไปรอตรวจใหม่ และหายจากเว็บไซต์ชั่วคราวจนกว่าจะได้รับอนุมัติ ถ้าแก้คำผิดเล็กน้อยให้แจ้งงานประชาสัมพันธ์แก้ให้จะเร็วกว่า</p>
            @endif
            <p>ช่อง "แสดงถึง" ใช้กับเรื่องที่มีวันหมดอายุ เช่น รับสมัครงาน หรือประกาศงดบริการ เมื่อพ้นวัน เรื่องจะออกจากหน้าแรกเองแต่ยังค้นเจอในประกาศย้อนหลัง</p>
        </x-filament::section>

        @if ($forDepartment)
            <x-filament::section heading="3. หน้าโปรไฟล์ของหน่วยงาน" collapsible collapsed>
                <p>แก้ได้ที่เมนู <strong>หน้าของหน่วยงานเรา</strong> แล้วกดแก้ไขที่ชื่อหน่วยงาน</p>
                <ul style="margin-top: 0.5rem; display: grid; gap: 0.35rem; padding-left: 1.25rem; list-style: disc;">
                    <li><strong>ภาพปก</strong>: ภาพแนวนอน กว้างประมาณ 3 เท่าของความสูง</li>
                    <li><strong>รูปโปรไฟล์</strong>: ภาพสี่เหลี่ยมจัตุรัส เช่น ตราหน่วยงานหรือรูปทีม</li>
                    <li><strong>ข้อความใต้ชื่อ</strong>: หนึ่งบรรทัดบอกว่าหน่วยงานทำอะไร</li>
                    <li><strong>บริการ</strong>: พิมพ์บรรทัดละหนึ่งบริการ</li>
                    <li><strong>บุคลากร</strong>: ชื่อ ตำแหน่ง และรูป (ขออนุญาตเจ้าตัวก่อน)</li>
                    <li><strong>ที่ตั้ง เบอร์โทร อีเมล Facebook LINE</strong></li>
                </ul>
                <p style="margin-top: 0.5rem">ในฟอร์มประกาศมีสวิตช์ <strong>"ปักหมุดไว้บนสุดของหน้าหน่วยงาน"</strong> ใช้กับเรื่องที่อยากให้คนเห็นก่อน ปักได้ครั้งละหนึ่งเรื่อง</p>
                <p>ชื่อหน่วยงาน การซ่อนหน่วยงาน และตารางคลินิก แก้โดยผู้ดูแลระบบหรืองานประชาสัมพันธ์</p>
            </x-filament::section>
        @else
            <x-filament::section heading="3. ตรวจเรื่องจากหน่วยงาน" collapsible collapsed>
                <p>เมื่อหน่วยงานส่งเรื่องมา หน้าแรกของหลังบ้านจะขึ้นแจ้งเตือนสีแดงว่า "มี … เรื่องจากหน่วยงานรอตรวจ"</p>
                <ol style="{{ $step }}">
                    <li>เปิดเมนู <strong>ประชาสัมพันธ์</strong> เรื่องที่รอตรวจมีป้ายสถานะ "รอตรวจ" (กรองตามสถานะได้)</li>
                    <li>กด "ดูบนเว็บไซต์" เพื่ออ่านเรื่องอย่างที่ประชาชนจะเห็น</li>
                    <li>กด <strong>อนุมัติ</strong> เพื่อให้ขึ้นเว็บไซต์ หรือ <strong>ส่งกลับแก้ไข</strong> พร้อมเขียนเหตุผลให้หน่วยงาน</li>
                </ol>
                <p style="{{ $note }}">สิ่งที่ควรดูก่อนอนุมัติ: ไม่มีรูปหรือชื่อที่ระบุตัวผู้ป่วย วันที่และเบอร์โทรถูกต้อง และเลือกประเภทตรงกับเนื้อหา</p>
                @if ($admin)
                    <p>ถ้าต้องการให้เรื่องของหน่วยงานขึ้นเว็บไซต์ทันทีโดยไม่ต้องตรวจ ปิดได้ที่ ตั้งค่าเว็บไซต์ &gt; ความปลอดภัย</p>
                @endif
            </x-filament::section>
        @endif

        <x-filament::section heading="4. ข้อห้ามและข้อควรระวัง" collapsible collapsed>
            <ul style="display: grid; gap: 0.35rem; padding-left: 1.25rem; list-style: disc;">
                <li><strong>ห้ามลงรูปที่เห็นหน้าผู้ป่วยหรือญาติ</strong> หรือรูปที่มีเอกสารผู้ป่วย จอคอมพิวเตอร์ ป้ายชื่อเตียง ติดอยู่ในภาพ เว้นแต่เจ้าตัวยินยอมเป็นลายลักษณ์อักษร</li>
                <li>ห้ามลงชื่อ เลขบัตรประชาชน เบอร์โทร หรือข้อมูลการรักษาของผู้ป่วย</li>
                <li>ชื่อและรูปของเจ้าหน้าที่ ให้ขออนุญาตเจ้าตัวก่อน</li>
                <li>ตรวจวันที่ เวลา และเบอร์โทรทุกครั้งก่อนส่ง เรื่องที่ผิดจะถูกส่งต่อทาง LINE ไปไกลกว่าที่แก้ตามทัน</li>
                <li>อย่าใช้รูปจากอินเทอร์เน็ตที่ไม่ใช่ของโรงพยาบาล</li>
            </ul>
        </x-filament::section>

        <x-filament::section heading="5. เข้าระบบและความปลอดภัย" collapsible collapsed>
            <ul style="display: grid; gap: 0.35rem; padding-left: 1.25rem; list-style: disc;">
                <li>เปลี่ยนรหัสผ่านหลังเข้าระบบครั้งแรก ที่ชื่อของคุณมุมขวาบน</li>
                <li>ถ้าระบบให้ตั้ง<strong>ยืนยันตัวตนสองขั้น</strong> ให้ติดตั้งแอป Google Authenticator หรือ Microsoft Authenticator ในโทรศัพท์ แล้วสแกน QR ที่ขึ้นบนจอ และเก็บรหัสกู้คืนไว้ในที่ปลอดภัย</li>
                <li>บัญชีเป็นของหน่วยงาน ถ้ามีคนย้ายหรือลาออกให้แจ้งผู้ดูแลเปลี่ยนรหัสผ่าน</li>
                <li>ทุกการสร้าง แก้ไข และลบ ระบบบันทึกไว้ว่าใครทำเมื่อไร</li>
            </ul>
        </x-filament::section>

        <x-filament::section heading="6. เมื่อมีปัญหา" collapsible collapsed>
            <ul style="display: grid; gap: 0.35rem; padding-left: 1.25rem; list-style: disc;">
                <li><strong>อัปโหลดรูปไม่ขึ้น</strong>: ไฟล์ต้องเป็น JPG, PNG หรือ WebP และไม่เกิน 8 MB</li>
                <li><strong>ลงแล้วไม่เห็นบนเว็บไซต์</strong>: ดูสถานะของเรื่อง และวันที่ในช่อง "แสดงตั้งแต่"</li>
                <li><strong>ลบเรื่องผิด</strong>: แจ้งผู้ดูแลระบบภายใน 14 วัน ระบบมีสำเนาสำรองทุกคืน</li>
                <li>เรื่องอื่น ๆ ติดต่อผู้ดูแลระบบของโรงพยาบาล</li>
            </ul>
        </x-filament::section>
    </div>
</x-filament-panels::page>
