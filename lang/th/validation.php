<?php

/*
 * What a form says, in Thai, when something typed into it is not right: for the forms visitors fill in
 * (complaints) and those of the panel. Only the rules the site uses are written here; a rule that is not
 * falls back to English.
 */
return [
    'accepted' => 'โปรดทำเครื่องหมายยอมรับ:attribute',
    'array' => ':attributeไม่ถูกต้อง',
    'before_or_equal' => ':attributeต้องไม่เกินวันนี้',
    'date' => ':attributeไม่ใช่วันที่ที่ถูกต้อง',
    'digits' => ':attributeต้องเป็นตัวเลข :digits หลัก',
    'email' => 'รูปแบบ:attributeไม่ถูกต้อง',
    'exists' => ':attributeที่เลือกไม่ถูกต้อง',
    'file' => ':attributeต้องเป็นไฟล์',
    'image' => ':attributeต้องเป็นไฟล์รูปภาพ',
    'in' => ':attributeที่เลือกไม่ถูกต้อง',
    'integer' => ':attributeต้องเป็นจำนวนเต็ม',
    'max' => [
        'array' => ':attributeมีได้ไม่เกิน :max รายการ',
        'file' => ':attributeต้องมีขนาดไม่เกิน :max กิโลไบต์',
        'numeric' => ':attributeต้องไม่เกิน :max',
        'string' => ':attributeต้องยาวไม่เกิน :max ตัวอักษร',
    ],
    'mimes' => ':attributeต้องเป็นไฟล์ประเภท: :values',
    'mimetypes' => ':attributeต้องเป็นไฟล์ประเภท: :values',
    'min' => [
        'array' => ':attributeต้องมีอย่างน้อย :min รายการ',
        'file' => ':attributeต้องมีขนาดอย่างน้อย :min กิโลไบต์',
        'numeric' => ':attributeต้องไม่น้อยกว่า :min',
        'string' => ':attributeต้องยาวอย่างน้อย :min ตัวอักษร',
    ],
    'numeric' => ':attributeต้องเป็นตัวเลข',
    'regex' => 'รูปแบบ:attributeไม่ถูกต้อง',
    'required' => 'โปรดกรอก:attribute',
    'same' => ':attributeไม่ตรงกับ:other',
    'starts_with' => ':attributeต้องขึ้นต้นด้วย: :values',
    'string' => ':attributeต้องเป็นข้อความ',
    'unique' => ':attributeนี้ถูกใช้แล้ว',
    'uploaded' => 'อัปโหลด:attributeไม่สำเร็จ ไฟล์อาจใหญ่เกินไป',
    'url' => 'รูปแบบ:attributeไม่ถูกต้อง',
];
