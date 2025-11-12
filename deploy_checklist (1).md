# Responsables App – Local & Production Checklist

## Repo root (/responsables-app):
- database.sql (o en /database/ con .htaccess Deny from all)
- .env.local.example
- .env.production.example
- htaccess.public.subdomain.example
- htaccess.public.subfolder.example
- deploy_checklist.md

## Local
1) Importa database.sql (phpMyAdmin).
2) Ajusta config/env.php (LOCAL) o usa las variables de .env.local.example.
3) composer install
4) php -S 127.0.0.1:8080 -t public/
5) Test: /api/health, /api/whoami, /api/login

## Producción (cPanel)
1) DocRoot subdominio → /public_html/responsables-app/public (recomendado).
2) Crea DB+usuario; importa database.sql.
3) Sube código y vendor (o composer install --no-dev --optimize-autoloader).
4) Ajusta config/env.php (PROD) o env vars de .env.production.example.
5) Reemplaza public/.htaccess según tu escenario.
6) Test: /api/health y login.
