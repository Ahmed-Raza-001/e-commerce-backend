#!/bin/bash
set -e

# Ensure JWT directory exists
mkdir -p config/jwt

# Generate JWT keypair if missing
if [ ! -f config/jwt/private.pem ] || [ ! -f config/jwt/public.pem ]; then
    echo "[Entrypoint] Generating JWT keypair..."
    JWT_PASS="${JWT_PASSPHRASE:-169b2dfada0e0dd491be61c79f45187720f30d7c214ef47bb0096da45509a16d}"
    openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096 -pass "pass:${JWT_PASS}"
    openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout -passin "pass:${JWT_PASS}"
fi

chown -R www-data:www-data config/jwt var

# Run database migrations to ensure PostgreSQL tables exist
echo "[Entrypoint] Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || echo "[Entrypoint] Migration check done."

exec "$@"
