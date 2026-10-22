# Executive Portal - kuweka kwenye cPanel

Package ya 16 Oktoba 2026. Hii ni package ya kuhamishia portal; bado haijawekwa kwenye server.

## 1. Mpangilio wa faili

Extract ZIP kwenye HOME ya cPanel, mfano `/home/USERNAME/`, si ndani ya `public_html`.

/home/USERNAME/
  portal_app/                 Laravel, vendor, config, storage na PDF za ndani
  public_html/                index.php, .htaccess, CSS/JS na PDF.js tu
  database-backup/portal.sql  Database ya kuimport
  INSTALL-PRIVATE.txt        Login mpya za admin za database ya package
  CPANEL-INSTALL-SW.md       Maelekezo haya
  RELEASE-MANIFEST.json      Orodha ya faili na SHA-256

`portal_app` na `public_html` lazima ziwe kwenye level moja na majina hayo. Package tayari imebadilisha entry point na public path kwa mpangilio huu, kwa web na Artisan. Usiweke `portal_app`, SQL au INSTALL-PRIVATE.txt ndani ya public_html. Ikiwa kuna website tayari kwenye public_html, backup kwanza; usibadilishe faili zake bila kupanga uhamisho.

## 2. Mahitaji ya hosting

- Chagua PHP **8.4 au zaidi** kwenye cPanel MultiPHP Manager / PHP Selector. Locked dependencies za package zinahitaji 8.4; PHP 8.3 haitoshi kwa package hii.
- Apache/LiteSpeed inayokubali `.htaccess` na rewrite. Nginx-only inahitaji host aweke routing inayolingana.
- MySQL-compatible database. SQL imeexportiwa bila CREATE DATABASE, GTID au tablespaces, na collation ya kawaida `utf8mb4_unicode_ci`.
- PHP extensions: pdo_mysql, mbstring, openssl, fileinfo, ctype, curl, dom/xml, tokenizer na zip. Calibre migration inahitaji pdo_sqlite. Hakikisha PHP ya Terminal pia ni 8.4 (`php -v`). Host anaweza kuhitaji command ya PHP 8.4 badala ya `php`.
- SSL/HTTPS ya domain iwe tayari (AutoSSL). Ongeza upload_max_filesize/post_max_size kwa ukubwa wa PDF zako kupitia MultiPHP INI Editor ikiwa inahitajika.

`vendor` ya production na frontend build vimo. Huhitaji npm au composer install kwenye server kwa package hii.

## 3. Database

1. cPanel > MySQL Databases: tengeneza database mpya na user wake, mpe user ALL PRIVILEGES kwenye database hiyo pekee.
2. cPanel > phpMyAdmin: chagua database mpya kisha Import `database-backup/portal.sql`.
3. SQL ina content ya sasa: vitabu, categories, slides, insights, accounts na records nyingine. Cache, sessions, reset tokens na pending jobs za kompyuta hazijahamishwa.
4. Password za admin zimebadilishwa **kwenye SQL ya package pekee**. Angalia `INSTALL-PRIVATE.txt`. Password za database/account za kompyuta hazijabadilishwa. Remember-me tokens zimeondolewa kwenye exported copy.
5. Usitumie `migrate:fresh`, `db:wipe` au `db:seed`. Import ina migrations table; hakuna sample content ya kuongezwa.

PDF na uploads zimo kwenye `portal_app/storage/app/`. Vitabu vinahudumiwa na authorization ya reader. Usisogeze private PDF kwenye public_html.

## 4. Environment

Kwenye File Manager washa Show Hidden Files. Copy `portal_app/.env.example` kuwa `portal_app/.env`.
Badilisha angalau:

APP_URL=https://DOMAIN-YAKO
DB_DATABASE=USERNAME_DATABASE
DB_USERNAME=USERNAME_DBUSER
DB_PASSWORD="PASSWORD-YA-DATABASE"

Weka domain halisi, si localhost wala 127.0.0.1. Acha APP_ENV=production na APP_DEBUG=false. APP_KEY inaanza tupu; command ya hatua inayofuata itaiunda. Usiweke APP_KEY ya kompyuta hii.

Session cookies zinahitaji HTTPS. Kwa email za reset/verification, badilisha MAIL_MAILER=log kwenda SMTP na ujaze SMTP ya hosting. Log mailer haitatuma email kwa inbox. QUEUE_CONNECTION=sync imewekwa ili package isihitaji queue worker kwa setup ya kwanza.

## 5. Commands za mwisho (cPanel Terminal au SSH)

Badilisha USERNAME kwa account yako na hakikisha PHP ya command ni 8.4.

```sh
cd /home/USERNAME/portal_app
php artisan key:generate --force --no-interaction
php artisan package:discover --no-interaction
php artisan storage:link --no-interaction
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction
```

Baada ya import, `php artisan migrate:status --no-interaction` inapaswa kuonyesha migrations zilizowekwa. Kwa installation hii hakuna sababu ya kufanya seed/reset. Usirudie key:generate baada ya website kuanza kutumika.

`storage:link` itaweka `public_html/storage` kuelekea `portal_app/storage/app/public`. Link hiyo ni ya cover, hero na Insight images pekee. Private files hazijumuishwi kwenye public link. Windows junction ya kompyuta haijawekwa kwenye ZIP; Linux link lazima iundwe server.

Ikiwa hosting imezima symlink, mwombe host aruhusu public storage link au mpangilio wa directory alias kwa public files. Usi-copy private storage kwenye public_html. Ikiwa Terminal/SSH haipo, mwombe host aendeshe commands hizi; package haitoi public web installer ya kuendesha commands.

Web server lazima iweze kuandika `portal_app/storage` na `portal_app/bootstrap/cache`. Tumia owner/permissions zinazopendekezwa na hosting, kwa kawaida directories 755 na files 644 chini ya user huyo; usitumie 777. Weka `.env` na `INSTALL-PRIVATE.txt` private (600 ikiwezekana).

Ikiwa umebadilisha .env baada ya config cache, rudia `php artisan config:cache --no-interaction`.

## 6. Hakiki kabla ya kuitangaza

- Fungua domain kwa HTTPS: homepage, cover, hero slider, Insights na search.
- Login kwenye /login kwa credentials za INSTALL-PRIVATE.txt; badilisha password kupitia Administrators na hifadhi credentials salama.
- Panel ya demo/autofill imeondolewa kwenye package ya server; haijaondolewa kwenye local prototype.
- Angalia publication detail na reader; jaribu PDF, progress, bookmarks na My Library.
- Angalia /admin/hero-slides na cover/image upload.
- Hakikisha URL za images zina domain yako na /storage, si port 8001.
- Hakikisha private .env, SQL na storage/app/private havifikiwi kwa URL.
- PHP backup feature inahitaji hosting iruhusu exec/proc_open na mysqldump ipatikane; kama host imezima, tumia cPanel database backup.
- Server haijajaribiwa kutoka hapa; PHP extensions, SSL, SMTP, symlink na permissions ni za kuthibitisha kwenye hosting halisi.

## 7. Kilichojumuishwa na kilichoachwa

Kimejumuishwa: Laravel code, production-only vendor iliyotokana na composer.lock, built assets, public media na private publication files, SQL snapshot, environment template na credentials mpya za exported admin accounts.
Kimeachwa: local .env/APP_KEY, node_modules, development vendor packages, logs, browser artifacts, sessions, caches, tests, prototype demo, local spreadsheet, seeders na Windows storage junction. Source portal na data yake havijabadilishwa kwa packaging; mabadiliko ya public path, login demo removal na credential rotation ni ya package pekee.

Vyanzo vya mpangilio: [Laravel deployment](https://laravel.com/framework/docs/deployment), [cPanel document roots](https://docs.cpanel.net/knowledge-base/cpanel-product/how-domains-serve-content/).
