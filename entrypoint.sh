#!/bin/sh

# Interpola apenas a variável VITE_API_BASE_URL ignorando as demais variáveis do Nginx
# Este script rodará automaticamente pelo docker-entrypoint oficial do nginx:alpine
echo "Interpolando VITE_API_BASE_URL no arquivo app.conf..."
envsubst '$VITE_API_BASE_URL' < /etc/nginx/conf.d/app.conf > /tmp/app.conf
mv /tmp/app.conf /etc/nginx/conf.d/app.conf
echo "Nginx proxy_pass configurado com sucesso!"
