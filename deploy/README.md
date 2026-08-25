# ملفات النشر

إعدادات نشر الموقع على خادم Ubuntu (Apache2 + PHP 8.3-FPM + SQLite + Let's Encrypt).

> **الخادم يستضيف مواقع أخرى.** كل ملف هنا مكتوب ليضيف موقعاً بجوار القائم دون
> تعديل إعداداته — لا `apt upgrade`، ولا `a2enconf` عام، ولا تعطيل `000-default`.

| الملف | الغرض |
|---|---|
| `protect.conf` | حماية عامة تمنع تحميل ملفات المشروع (`.env`، قاعدة البيانات) عبر المواقع الأخرى على الخادم |
| `apache.conf` | VirtualHost كامل — ضغط، تخزين مؤقت للأصول، ترويسات أمان، منع الوصول لملف قاعدة البيانات |
| `.env.production.example` | قالب إعدادات الإنتاج. القيم المطلوب تغييرها معلّمة بـ `‼️ غيّرني` |
| `deploy.sh` | سكربت التحديثات بعد أول نشر — وضع صيانة، سحب، بناء، ترحيل، إعادة كاش |
| `queue-worker.service` | خدمة systemd لعامل الطوابير (`QUEUE_CONNECTION=database`) |
| `scheduler.cron` | سطر cron لمجدول Laravel |

## أول نشر

راجع دليل التنفيذ الكامل خطوة بخطوة (المراحل العشر من تجهيز الخادم إلى شهادة SSL).

## تحديث لاحق

```bash
cd /var/www/html/mutazsatti.com && ./deploy/deploy.sh
```

## نسخة احتياطية

قاعدة البيانات ملف واحد والصور في مجلد واحد:

```bash
tar czf backup-$(date +%F).tar.gz database/database.sqlite storage/app/public
```
