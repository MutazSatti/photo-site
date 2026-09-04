<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

/**
 * الجهات التي تُعرض شعاراتها في الصفحة الرئيسية.
 *
 * الأسماء والروابط هنا، والشعارات في SiteMediaSeeder التي تربطها بالجهة باسمها.
 * كل رابط طُلب فعليًا قبل إثباته: الجهة بلا موقع عامل تُربط بموقعها على خرائط
 * Google بدلًا منه، فلا يفتح شعارٌ رابطًا ميتًا.
 *
 * updateOrCreate بالاسم: تشغيلها على موقع قائم لا يكرّر جهة ولا يمسح تعديلًا
 * على ترتيبها أو ظهورها.
 */
class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $clients = [
            [
                'name' => 'أمانة جدة',
                'name_en' => 'Jeddah Municipality',
                'url' => 'https://www.jeddah.gov.sa',
                'sort_order' => 0,
            ],
            [
                'name' => 'جامعة الملك عبدالعزيز',
                'name_en' => 'King Abdulaziz University',
                'url' => 'https://www.kau.edu.sa/ar',
                'sort_order' => 1,
            ],
            [
                'name' => 'مجمع الملك سلمان العالمي للغة العربية',
                'name_en' => 'King Salman Global Academy for Arabic Language',
                'url' => 'https://ksaa.gov.sa',
                'sort_order' => 2,
            ],
            [
                'name' => 'وزارة الرياضة',
                'name_en' => 'Ministry of Sport',
                'url' => 'https://www.mos.gov.sa',
                'sort_order' => 3,
            ],
            [
                'name' => 'وزارة الخارجية — سلطنة عُمان',
                'name_en' => 'Ministry of Foreign Affairs, Sultanate of Oman',
                'url' => 'https://www.fm.gov.om',
                'sort_order' => 4,
            ],
            [
                'name' => 'القنصلية العامة لجمهورية العراق في جدة',
                'name_en' => 'Iraq Consulate in Jeddah',
                'url' => 'https://mofa.gov.iq/jeddah/',
                'sort_order' => 5,
            ],
            [
                'name' => 'الاتحاد السعودي للدراجات',
                'name_en' => 'Saudi Cycling Federation',
                'url' => 'https://www.sacf.sa',
                'sort_order' => 6,
            ],
            [
                'name' => 'الاتحاد السعودي للكاراتيه',
                'name_en' => 'Saudi Karate Federation',
                'url' => 'https://skf.sa',
                'sort_order' => 7,
            ],
            [
                'name' => 'الاتحاد السعودي للملاكمة والركل',
                'name_en' => 'Saudi Kickboxing Federation',
                'url' => 'https://skfed.sa',
                'sort_order' => 8,
            ],
            [
                'name' => 'الاتحاد السعودي للإنقاذ والسلامة المائية',
                'name_en' => 'Saudi Life Saving Federation',
                'url' => 'https://slsf.sa',
                'sort_order' => 9,
            ],
            [
                'name' => 'الاتحاد السعودي للبلياردو والسنوكر',
                'name_en' => 'Saudi Billiard and Snooker Federation',
                'url' => 'https://www.google.com/maps/search/?api=1&query=%D8%A7%D9%84%D8%A7%D8%AA%D8%AD%D8%A7%D8%AF%20%D8%A7%D9%84%D8%B3%D8%B9%D9%88%D8%AF%D9%8A%20%D9%84%D9%84%D8%A8%D9%84%D9%8A%D8%A7%D8%B1%D8%AF%D9%88%20%D9%88%D8%A7%D9%84%D8%B3%D9%86%D9%88%D9%83%D8%B1%20%D8%A7%D9%84%D8%B1%D9%8A%D8%A7%D8%B6',
                'sort_order' => 10,
            ],
            [
                'name' => 'وقف لغة القرآن بجامعة الملك عبدالعزيز',
                'name_en' => 'Waqf of the Language of the Quran',
                'url' => 'https://kau.edu.sa/ar/page/the-quranic-language-endowment-at-king-abdulaziz-university',
                'sort_order' => 11,
            ],
            [
                'name' => 'مجمع التاج القرآني بجامع الزبيدي',
                'name_en' => 'Al Taj Al Qurani Complex',
                'url' => 'https://www.google.com/maps/search/?api=1&query=%D9%85%D8%AC%D9%85%D8%B9%20%D8%A7%D9%84%D8%AA%D8%A7%D8%AC%20%D8%A7%D9%84%D9%82%D8%B1%D8%A2%D9%86%D9%8A%20%D8%AC%D8%A7%D9%85%D8%B9%20%D8%A7%D9%84%D8%B2%D8%A8%D9%8A%D8%AF%D9%8A%20%D8%AC%D8%AF%D8%A9',
                'sort_order' => 12,
            ],
            [
                'name' => 'جمعية منائر لخدمة المساجد بمنطقة مكة المكرمة',
                'name_en' => 'Manaer Association',
                'url' => 'https://manair.sa',
                'sort_order' => 13,
            ],
            [
                'name' => 'جمعية رعاية الأجيال',
                'name_en' => 'Riayat Al Ajyal Association',
                'url' => 'https://ralajyal.org',
                'sort_order' => 14,
            ],
            [
                'name' => 'نماء المكية للسقاية والرفادة',
                'name_en' => 'Namaa Al Makkiyah',
                'url' => 'https://www.nmamakkyah.sa',
                'sort_order' => 15,
            ],
            [
                'name' => 'مدارس الأمجاد',
                'name_en' => 'Al Amjad Schools',
                'url' => 'https://alamjadsch.com',
                'sort_order' => 16,
            ],
            [
                'name' => 'معهد إعداد القادة',
                'name_en' => 'Leaders Development Institute',
                'url' => 'https://www.google.com/maps/search/?api=1&query=%D9%85%D8%B9%D9%87%D8%AF%20%D8%A5%D8%B9%D8%AF%D8%A7%D8%AF%20%D8%A7%D9%84%D9%82%D8%A7%D8%AF%D8%A9%20%D8%A7%D9%84%D8%B1%D9%8A%D8%A7%D8%B6',
                'sort_order' => 17,
            ],
            [
                'name' => 'مؤسسة كيان النهضة العقارية',
                'name_en' => 'Kayan Al Nahda Real Estate',
                'url' => 'https://kayanalnhda.sa',
                'sort_order' => 18,
            ],
            [
                'name' => 'المهند العقارية',
                'name_en' => 'Al Muhannad Real Estate',
                'url' => 'https://almuhanad.com',
                'sort_order' => 19,
            ],
            [
                'name' => 'بيلسان العقارية',
                'name_en' => 'Bilsan Real Estate',
                'url' => 'https://www.google.com/maps/search/?api=1&query=%D8%A8%D9%8A%D9%84%D8%B3%D8%A7%D9%86%20%D8%A7%D9%84%D8%B9%D9%82%D8%A7%D8%B1%D9%8A%D8%A9%20%D8%AC%D8%AF%D8%A9',
                'sort_order' => 20,
            ],
            [
                'name' => 'راف العقارية',
                'name_en' => 'RAF Real Estate',
                'url' => 'https://www.raf-advanced.sa',
                'sort_order' => 21,
            ],
            [
                'name' => 'ديارا',
                'name_en' => 'Dyara',
                'url' => 'https://www.dyara.sa',
                'sort_order' => 22,
            ],
            [
                'name' => 'مسكن ميمون',
                'name_en' => 'Maskan Maymoon',
                'url' => 'https://www.google.com/maps/search/?api=1&query=%D9%85%D8%B3%D9%83%D9%86%20%D9%85%D9%8A%D9%85%D9%88%D9%86%20%D8%AC%D8%AF%D8%A9',
                'sort_order' => 23,
            ],
            [
                'name' => 'الرويحي والشهراني — محامون ومستشارون',
                'name_en' => 'Al Ruwaihy & Al Shahrani',
                'url' => 'https://www.google.com/maps/search/?api=1&query=%D8%B4%D8%B1%D9%83%D8%A9%20%D8%A7%D9%84%D8%B1%D9%88%D9%8A%D8%AD%D9%8A%20%D9%88%D8%A7%D9%84%D8%B4%D9%87%D8%B1%D8%A7%D9%86%D9%8A%20%D9%84%D9%84%D9%85%D8%AD%D8%A7%D9%85%D8%A7%D8%A9%20%D8%AC%D8%AF%D8%A9',
                'sort_order' => 24,
            ],
            [
                'name' => 'جابر يسلم للمحاماة والاستشارات القانونية',
                'name_en' => 'Jaber Yaslam Law Firm',
                'url' => 'https://www.jaber-law.com',
                'sort_order' => 25,
            ],
            [
                'name' => 'WATAN',
                'name_en' => 'Watan',
                'url' => 'https://www.google.com/maps/search/?api=1&query=%D9%88%D8%B7%D9%86%20WATAN%20%D8%AC%D8%AF%D8%A9',
                'sort_order' => 26,
            ],
            [
                'name' => 'فنون',
                'name_en' => 'Fonon',
                'url' => 'https://www.google.com/maps/search/?api=1&query=%D9%81%D9%86%D9%88%D9%86%20Fonon%20%D8%AC%D8%AF%D8%A9',
                'sort_order' => 27,
            ],
            [
                'name' => 'Focus Production House',
                'name_en' => 'Focus Production House',
                'url' => 'https://www.google.com/maps/search/?api=1&query=Focus%20Production%20House%20Jeddah',
                'sort_order' => 28,
            ],
            [
                'name' => 'السحيلي',
                'name_en' => 'Al Suhaili',
                'url' => 'https://www.google.com/maps/search/?api=1&query=%D8%A7%D9%84%D8%B3%D8%AD%D9%8A%D9%84%D9%8A%20AL%20SUHAILI%20%D8%AC%D8%AF%D8%A9',
                'sort_order' => 29,
            ],
            [
                'name' => 'سمير',
                'name_en' => 'Samir',
                'url' => 'https://samirgroup.com',
                'sort_order' => 30,
            ],
            [
                'name' => 'متجر تبويب',
                'name_en' => 'Tbwyeb Store',
                'url' => 'https://www.google.com/maps/search/?api=1&query=%D9%85%D8%AA%D8%AC%D8%B1%20%D8%AA%D8%A8%D9%88%D9%8A%D8%A8',
                'sort_order' => 31,
            ],
            [
                'name' => 'ثروة',
                'name_en' => 'Tharwa',
                'url' => 'https://tharwahperfume.com',
                'sort_order' => 32,
            ],
            [
                'name' => 'Le Beurre',
                'name_en' => 'Le Beurre',
                'url' => 'https://www.google.com/maps/search/?api=1&query=Le%20Beurre%20Bakery%20%D9%85%D8%AE%D8%A8%D8%B2%20%D9%84%D9%88%D8%A8%D9%8A%D8%BA%20%D8%AC%D8%AF%D8%A9',
                'sort_order' => 33,
            ],
            [
                'name' => 'مضغوط بيتنا',
                'name_en' => 'Madghout Baitna',
                'url' => 'https://www.google.com/maps/search/?api=1&query=%D9%85%D8%B7%D8%B9%D9%85%20%D9%85%D8%B6%D8%BA%D9%88%D8%B7%20%D8%A8%D9%8A%D8%AA%D9%86%D8%A7%20%D8%AC%D8%AF%D8%A9',
                'sort_order' => 34,
            ],
            [
                'name' => 'Fradis',
                'name_en' => 'Fradis',
                'url' => 'https://www.google.com/maps/search/?api=1&query=Fradis%20%D8%AC%D8%AF%D8%A9',
                'sort_order' => 35,
            ],
            [
                'name' => 'حديقة المرح',
                'name_en' => 'Hadiqa Al Marah',
                'url' => 'https://www.google.com/maps/search/?api=1&query=%D8%AD%D8%AF%D9%8A%D9%82%D8%A9%20%D8%A7%D9%84%D9%85%D8%B1%D8%AD%20%D8%A7%D9%84%D8%AD%D9%85%D8%AF%D8%A7%D9%86%D9%8A%D8%A9%20%D8%AC%D8%AF%D8%A9',
                'sort_order' => 36,
            ],
            [
                'name' => 'شركة الشبل الإبداعية المحدودة',
                'name_en' => 'Alshebl Creative Limited Company',
                'url' => 'https://www.alshebl.com.sa',
                'sort_order' => 37,
            ],
        ];

        foreach ($clients as $client) {
            Client::updateOrCreate(['name' => $client['name']], $client);
        }
    }
}
