# HM Pro Çeviri (WordPress Plugin)

Basit çokdilli katman + Google Translate entegrasyonu (planlandı). Bu MVP şunları sağlar:

- Varsayılan dil: `tr`
- Aktif diller: `tr`, `en` (ayar panelinden değiştirilebilir)
- Dil seçimindeki öncelik: `?hm_lang=xx` query parametresi → cookie → varsayılan dil
- Kısa kod: `[hm_lang_switcher]` (inline) veya `[hm_lang_switcher style="dropdown"]`
- Stil: `assets/switcher.css` otomatik enqueue edilir; inline link/dropdown için temel görsel düzen

## Admin ayarları
- **Ayar sayfası:** Ayarlar → HM Pro Çeviri
- **Default language:** Seçilebilir liste
- **Enabled languages:** CheckBox listesi; seçilen diller switcher’da gösterilir

## Geliştirici notları
- Dil katalogu `includes/class-hm-pro-ceviri-i18n.php` içindeki `get_languages_catalog()` ile yönetilir.
- Aktif dil `HM_Pro_Ceviri_I18n::get_current_lang()` ile okunur ve cookie’den gelir.
- Switcher markup’ı `includes/class-hm-pro-ceviri-switcher.php` içinde üretilir.
- Ayarlar sanitize işlemi `includes/admin/class-hm-pro-ceviri-admin.php` içinde yapılır.

