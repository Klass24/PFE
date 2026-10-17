# Multi-stage build pour le frontend React + Vite
# Stage 1: Build
FROM node:20-alpine as builder

WORKDIR /app

# Copier les fichiers de dépendances
COPY frontend/package*.json ./

# Installer les dépendances
RUN npm install --prefer-offline --no-audit

# Copier le code source
COPY frontend/ .

# Build l'application
RUN npm run build

# Stage 2: Servir avec Nginx
FROM nginx:1.25-alpine

# Copier la configuration Nginx
COPY docker/frontend/nginx.conf /etc/nginx/conf.d/default.conf

# Copier les fichiers build depuis le stage précédent
COPY --from=builder /app/dist /usr/share/nginx/html

# Exposer le port
EXPOSE 80

# Healthcheck
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD wget --quiet --tries=1 --spider http://localhost/health || exit 1

# Démarrer Nginx
CMD ["nginx", "-g", "daemon off;"]
