<?php

/*
|--------------------------------------------------------------------------
| رسائل التحقّق من المدخلات
|--------------------------------------------------------------------------
|
| ما لا يُترجم هنا يعود إلى الإنجليزية عبر APP_FALLBACK_LOCALE، فلا تظهر
| مفاتيح خام للمستخدم في أي حال.
|
| أسماء الحقول في مصفوفة attributes بالأسفل هي ما يحلّ محل :attribute،
| وهي مضبوطة على الحقول المستخدمة فعلًا في لوحة التحكم ونماذج الموقع.
|
*/

return [

    'accepted' => 'يجب قبول :attribute.',
    'accepted_if' => 'يجب قبول :attribute عندما يكون :other هو :value.',
    'active_url' => ':attribute ليس رابطًا صحيحًا.',
    'after' => 'يجب أن يكون :attribute تاريخًا بعد :date.',
    'after_or_equal' => 'يجب أن يكون :attribute تاريخًا بعد :date أو مساويًا له.',
    'alpha' => 'يجب ألّا يحتوي :attribute إلا على حروف.',
    'alpha_dash' => 'يجب ألّا يحتوي :attribute إلا على حروف وأرقام وشرطات.',
    'alpha_num' => 'يجب ألّا يحتوي :attribute إلا على حروف وأرقام.',
    'array' => 'يجب أن يكون :attribute مصفوفة.',
    'ascii' => 'يجب ألّا يحتوي :attribute إلا على حروف ورموز لاتينية.',
    'before' => 'يجب أن يكون :attribute تاريخًا قبل :date.',
    'before_or_equal' => 'يجب أن يكون :attribute تاريخًا قبل :date أو مساويًا له.',

    'between' => [
        'array' => 'يجب أن يحتوي :attribute على عناصر بين :min و :max.',
        'file' => 'يجب أن يكون حجم :attribute بين :min و :max كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute بين :min و :max.',
        'string' => 'يجب أن يكون طول :attribute بين :min و :max حرفًا.',
    ],

    'boolean' => 'يجب أن تكون قيمة :attribute صحيحة أو خاطئة.',
    'can' => ':attribute يحتوي قيمة غير مصرّح بها.',
    'confirmed' => 'تأكيد :attribute غير مطابق.',
    'contains' => 'ينقص :attribute قيمة مطلوبة.',
    'current_password' => 'كلمة المرور غير صحيحة.',
    'date' => ':attribute ليس تاريخًا صحيحًا.',
    'date_equals' => 'يجب أن يكون :attribute تاريخًا مساويًا لـ :date.',
    'date_format' => 'لا يطابق :attribute الصيغة :format.',
    'decimal' => 'يجب أن يحتوي :attribute على :decimal منزلة عشرية.',
    'declined' => 'يجب رفض :attribute.',
    'declined_if' => 'يجب رفض :attribute عندما يكون :other هو :value.',
    'different' => 'يجب أن يختلف :attribute عن :other.',
    'digits' => 'يجب أن يتكوّن :attribute من :digits رقمًا.',
    'digits_between' => 'يجب أن يتكوّن :attribute من عدد أرقام بين :min و :max.',
    'dimensions' => 'أبعاد صورة :attribute غير صالحة.',
    'distinct' => 'قيمة :attribute مكرّرة.',
    'doesnt_end_with' => 'يجب ألّا ينتهي :attribute بأحد التالي: :values.',
    'doesnt_start_with' => 'يجب ألّا يبدأ :attribute بأحد التالي: :values.',
    'email' => 'يجب أن يكون :attribute بريدًا إلكترونيًا صحيحًا.',
    'ends_with' => 'يجب أن ينتهي :attribute بأحد التالي: :values.',
    'enum' => 'القيمة المختارة لـ :attribute غير صالحة.',
    'exists' => 'القيمة المختارة لـ :attribute غير موجودة.',
    'extensions' => 'يجب أن يكون امتداد ملف :attribute أحد التالي: :values.',
    'file' => 'يجب أن يكون :attribute ملفًا.',
    'filled' => 'يجب ألّا يكون :attribute فارغًا.',

    'gt' => [
        'array' => 'يجب أن يحتوي :attribute على أكثر من :value عنصرًا.',
        'file' => 'يجب أن يكون حجم :attribute أكبر من :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute أكبر من :value.',
        'string' => 'يجب أن يكون طول :attribute أكبر من :value حرفًا.',
    ],

    'gte' => [
        'array' => 'يجب أن يحتوي :attribute على :value عنصرًا أو أكثر.',
        'file' => 'يجب أن يكون حجم :attribute :value كيلوبايت أو أكبر.',
        'numeric' => 'يجب أن تكون قيمة :attribute :value أو أكبر.',
        'string' => 'يجب أن يكون طول :attribute :value حرفًا أو أكثر.',
    ],

    'hex_color' => 'يجب أن يكون :attribute لونًا بصيغة hex صحيحة.',
    'image' => 'يجب أن يكون :attribute صورة.',
    'in' => 'القيمة المختارة لـ :attribute غير صالحة.',
    'in_array' => 'القيمة :attribute غير موجودة ضمن :other.',
    'integer' => 'يجب أن يكون :attribute رقمًا صحيحًا.',
    'ip' => 'يجب أن يكون :attribute عنوان IP صحيحًا.',
    'ipv4' => 'يجب أن يكون :attribute عنوان IPv4 صحيحًا.',
    'ipv6' => 'يجب أن يكون :attribute عنوان IPv6 صحيحًا.',
    'json' => 'يجب أن يكون :attribute نصًا بصيغة JSON صحيحة.',
    'list' => 'يجب أن يكون :attribute قائمة.',
    'lowercase' => 'يجب أن يكون :attribute بأحرف صغيرة.',

    'lt' => [
        'array' => 'يجب أن يحتوي :attribute على أقل من :value عنصرًا.',
        'file' => 'يجب أن يكون حجم :attribute أصغر من :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute أصغر من :value.',
        'string' => 'يجب أن يكون طول :attribute أقل من :value حرفًا.',
    ],

    'lte' => [
        'array' => 'يجب ألّا يحتوي :attribute على أكثر من :value عنصرًا.',
        'file' => 'يجب أن يكون حجم :attribute :value كيلوبايت أو أصغر.',
        'numeric' => 'يجب أن تكون قيمة :attribute :value أو أصغر.',
        'string' => 'يجب أن يكون طول :attribute :value حرفًا أو أقل.',
    ],

    'mac_address' => 'يجب أن يكون :attribute عنوان MAC صحيحًا.',

    'max' => [
        'array' => 'يجب ألّا يحتوي :attribute على أكثر من :max عنصرًا.',
        'file' => 'يجب ألّا يتجاوز حجم :attribute :max كيلوبايت.',
        'numeric' => 'يجب ألّا تتجاوز قيمة :attribute :max.',
        'string' => 'يجب ألّا يتجاوز طول :attribute :max حرفًا.',
    ],

    'max_digits' => 'يجب ألّا يحتوي :attribute على أكثر من :max رقمًا.',
    'mimes' => 'يجب أن يكون :attribute ملفًا من نوع: :values.',
    'mimetypes' => 'يجب أن يكون :attribute ملفًا من نوع: :values.',

    'min' => [
        'array' => 'يجب أن يحتوي :attribute على :min عنصرًا على الأقل.',
        'file' => 'يجب ألّا يقل حجم :attribute عن :min كيلوبايت.',
        'numeric' => 'يجب ألّا تقل قيمة :attribute عن :min.',
        'string' => 'يجب ألّا يقل طول :attribute عن :min حرفًا.',
    ],

    'min_digits' => 'يجب أن يحتوي :attribute على :min رقمًا على الأقل.',
    'missing' => 'يجب ألّا يكون :attribute موجودًا.',
    'missing_if' => 'يجب ألّا يكون :attribute موجودًا عندما يكون :other هو :value.',
    'missing_unless' => 'يجب ألّا يكون :attribute موجودًا إلا إذا كان :other هو :value.',
    'missing_with' => 'يجب ألّا يكون :attribute موجودًا مع :values.',
    'missing_with_all' => 'يجب ألّا يكون :attribute موجودًا مع :values.',
    'multiple_of' => 'يجب أن تكون قيمة :attribute من مضاعفات :value.',
    'not_in' => 'القيمة المختارة لـ :attribute غير صالحة.',
    'not_regex' => 'صيغة :attribute غير صالحة.',
    'numeric' => 'يجب أن يكون :attribute رقمًا.',

    'password' => [
        'letters' => 'يجب أن تحتوي :attribute على حرف واحد على الأقل.',
        'mixed' => 'يجب أن تحتوي :attribute على حرف كبير وحرف صغير على الأقل.',
        'numbers' => 'يجب أن تحتوي :attribute على رقم واحد على الأقل.',
        'symbols' => 'يجب أن تحتوي :attribute على رمز واحد على الأقل.',
        'uncompromised' => 'ظهرت :attribute في تسريب بيانات. اختر كلمة مرور أخرى.',
    ],

    'present' => 'يجب أن يكون :attribute موجودًا.',
    'present_if' => 'يجب أن يكون :attribute موجودًا عندما يكون :other هو :value.',
    'present_unless' => 'يجب أن يكون :attribute موجودًا إلا إذا كان :other هو :value.',
    'present_with' => 'يجب أن يكون :attribute موجودًا مع :values.',
    'present_with_all' => 'يجب أن يكون :attribute موجودًا مع :values.',
    'prohibited' => ':attribute غير مسموح به.',
    'prohibited_if' => ':attribute غير مسموح به عندما يكون :other هو :value.',
    'prohibited_unless' => ':attribute غير مسموح به إلا إذا كان :other ضمن :values.',
    'prohibits' => ':attribute يمنع وجود :other.',
    'regex' => 'صيغة :attribute غير صالحة.',
    'required' => 'حقل :attribute مطلوب.',
    'required_array_keys' => 'يجب أن يحتوي :attribute على المفاتيح: :values.',
    'required_if' => 'حقل :attribute مطلوب عندما يكون :other هو :value.',
    'required_if_accepted' => 'حقل :attribute مطلوب عند قبول :other.',
    'required_if_declined' => 'حقل :attribute مطلوب عند رفض :other.',
    'required_unless' => 'حقل :attribute مطلوب إلا إذا كان :other ضمن :values.',
    'required_with' => 'حقل :attribute مطلوب مع :values.',
    'required_with_all' => 'حقل :attribute مطلوب مع :values.',
    'required_without' => 'حقل :attribute مطلوب في غياب :values.',
    'required_without_all' => 'حقل :attribute مطلوب في غياب :values.',
    'same' => 'يجب أن يتطابق :attribute مع :other.',

    'size' => [
        'array' => 'يجب أن يحتوي :attribute على :size عنصرًا.',
        'file' => 'يجب أن يكون حجم :attribute :size كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute :size.',
        'string' => 'يجب أن يكون طول :attribute :size حرفًا.',
    ],

    'starts_with' => 'يجب أن يبدأ :attribute بأحد التالي: :values.',
    'string' => 'يجب أن يكون :attribute نصًا.',
    'timezone' => 'يجب أن يكون :attribute منطقة زمنية صحيحة.',
    'unique' => ':attribute مستخدم من قبل.',
    'uploaded' => 'فشل رفع :attribute.',
    'uppercase' => 'يجب أن يكون :attribute بأحرف كبيرة.',
    'url' => 'يجب أن يكون :attribute رابطًا صحيحًا.',
    'ulid' => 'يجب أن يكون :attribute معرّف ULID صحيحًا.',
    'uuid' => 'يجب أن يكون :attribute معرّف UUID صحيحًا.',

    /*
    | رسائل مخصّصة لحقل وقاعدة بعينهما.
    */
    'custom' => [
        'attribute-name' => [
            'rule-name' => 'رسالة مخصّصة',
        ],
    ],

    /*
    | أسماء الحقول كما تظهر داخل الرسائل.
    */
    'attributes' => [
        'name' => 'الاسم',
        'name_en' => 'الاسم بالإنجليزية',
        'email' => 'البريد الإلكتروني',
        'password' => 'كلمة المرور',
        'password_confirmation' => 'تأكيد كلمة المرور',
        'current_password' => 'كلمة المرور الحالية',
        'phone' => 'رقم الجوال',
        'message' => 'الرسالة',
        'subject' => 'الموضوع',
        'title' => 'العنوان',
        'subtitle' => 'العنوان الفرعي',
        'slug' => 'الرابط',
        'excerpt' => 'المقتطف',
        'body' => 'المحتوى',
        'tagline' => 'الجملة التعريفية',
        'description' => 'الوصف',
        'icon' => 'الأيقونة',
        'color' => 'اللون',
        'sort_order' => 'الترتيب',
        'is_active' => 'الظهور',
        'section_id' => 'القسم',
        'category_id' => 'القسم الفرعي',
        'status' => 'الحالة',
        'published_at' => 'تاريخ النشر',
        'event_date' => 'تاريخ المناسبة',
        'location' => 'الموقع',
        'client' => 'الجهة',
        'price' => 'السعر',
        'duration' => 'المدة',
        'seats' => 'عدد المقاعد',
        'seo_title' => 'عنوان السيو',
        'seo_description' => 'وصف السيو',
        'question' => 'السؤال',
        'answer' => 'الإجابة',
        'role' => 'الصفة',
        'rating' => 'التقييم',
        'images' => 'الصور',
        'image' => 'الصورة',
        'alt' => 'النص البديل',
    ],

];
