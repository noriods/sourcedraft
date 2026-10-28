# sourcedraft web screen

Laravel app that drives the `sd` engine one level up. It only calls the CLI and reads run folders.

```bash
composer install && npm install && npm run build
cp .env.example .env && php artisan key:generate
touch database/database.sqlite && php artisan migrate --seed
php artisan serve & php artisan queue:work
```

Login: `demo@sourcedraft.test` / `sourcedraft` (change in `database/seeders/DatabaseSeeder.php`).
Slide images need LibreOffice (`soffice` on PATH, or `SOFFICE=/path/to/soffice` in `.env`) and `pdftoppm`.
