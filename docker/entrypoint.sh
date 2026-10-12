#!/bin/sh
# FacilMed — roda toda vez que o servidor liga (README §2.7).
set -e

cd /var/www/html

# 1. Apache escuta na porta que o Render mandar.
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Endereço do site: se ninguém definiu APP_URL, usa o que o Render criou
# (ex.: https://facilmed.onrender.com). Vai nos links dos e-mails.
export APP_URL="${APP_URL:-$RENDER_EXTERNAL_URL}"

# 2. Certificado do banco (Aiven exige conexão segura). O texto do
#    certificado vem da variável MYSQL_CA_CERT e vira um arquivo.
if [ -n "$MYSQL_CA_CERT" ]; then
    printf '%s\n' "$MYSQL_CA_CERT" > /tmp/mysql-ca.pem
    export MYSQL_ATTR_SSL_CA=/tmp/mysql-ca.pem
fi

# 3. Cache de configuração, rotas e telas (site mais rápido).
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4a. (removido em 12/10/2026, revisão de segurança) Aqui havia um
#     "migrate:fresh --seed" automático, usado UMA vez em 07/10 para trocar o
#     banco da versão com agendamento. Ele já tinha cumprido o papel e, se a
#     tabela "consultas" voltasse a existir, apagaria o banco do site a cada
#     publicação. Para zerar o site de propósito: README §2.7 (aba Shell).

# 4. Cria/atualiza as tabelas. Só aplica migrations novas; não apaga nada.
php artisan migrate --force

# 5. Banco vazio (primeiro deploy)? Coloca os dados de demonstração.
USUARIOS=$(php artisan tinker --execute='echo \App\Models\User::count();' 2>/dev/null | tail -n 1)
if [ "$USUARIOS" = "0" ]; then
    echo "FacilMed: banco vazio, rodando os seeders de demonstração."
    php artisan db:seed --force
else
    echo "FacilMed: banco já tem dados ($USUARIOS usuários), seeder não roda."
fi

# 5b. (12/10/2026) A senha "facilmed2026" está no README (repositório público).
#     Aqui ela deixa de valer: as contas de demonstração passam a usar a
#     DEMO_PASSWORD do Render (sem a variável, uma senha aleatória). Quem já
#     estava logado nessas contas cai fora. SEM "|| true" de propósito: se
#     isto falhar, o servidor não sobe com o admin aberto para qualquer um.
php artisan facilmed:proteger-demonstracao

# 6. (01/10/2026) Administrador com e-mail de verdade, se ADMIN_EMAIL e
#    ADMIN_PASSWORD estiverem no Render. Já existe? Não mexe. Nunca derruba o boot.
php artisan facilmed:garantir-admin || true

chown -R www-data:www-data storage bootstrap/cache

exec "$@"
