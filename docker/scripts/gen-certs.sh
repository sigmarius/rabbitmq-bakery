#!/usr/bin/env bash
set -euo pipefail

export MSYS_NO_PATHCONV=1

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CERT_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)/certs"
mkdir -p "${CERT_DIR}"
cd "${CERT_DIR}"

CA_KEY="ca.key"
CA_PEM="ca.pem"
SRV_KEY="server_key.pem"
SRV_CSR="server.csr"
SRV_CERT="server_cert.pem"
SRV_EXT="server.ext"

cat > "${SRV_EXT}" <<'EOF'
subjectAltName=DNS:localhost,DNS:rabbit-bakery.local,IP:127.0.0.1
extendedKeyUsage=serverAuth
EOF

openssl genrsa -out "${CA_KEY}" 4096
openssl req -x509 -new -nodes -key "${CA_KEY}" -sha256 -days 3650 \
  -subj "/CN=Bakery-Training-CA" -out "${CA_PEM}"

openssl genrsa -out "${SRV_KEY}" 4096
openssl req -new -key "${SRV_KEY}" -out "${SRV_CSR}" \
  -subj "/CN=rabbit-bakery.local"

openssl x509 -req -in "${SRV_CSR}" -CA "${CA_PEM}" -CAkey "${CA_KEY}" \
  -CAcreateserial -out "${SRV_CERT}" -days 825 -sha256 -extfile "${SRV_EXT}"

rm -f "${SRV_CSR}" "${SRV_EXT}" "ca.srl"

chmod 600 "${CA_KEY}" "${SRV_KEY}" 2>/dev/null || true
chmod 644 "${CA_PEM}" "${SRV_CERT}" 2>/dev/null || true

echo "Готово: ${CERT_DIR} (ca.pem, server_cert.pem, server_key.pem)"
