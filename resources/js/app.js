/**
 * نقطة الدخول لجافاسكربت الموقع.
 *
 * Alpine.js يأتي مضمّنًا مع Livewire — لا يُستورد هنا حتى لا تُحمَّل نسختان.
 */

import './sync';

/**
 * إغلاق صندوق الصور بمفتاح Escape يعمل على مستوى الصفحة كلها،
 * ويبقى فعّالًا بعد كل تنقّل بـ wire:navigate.
 */
document.addEventListener('livewire:navigated', () => {
    window.scrollTo({ top: 0, behavior: 'instant' });
});
