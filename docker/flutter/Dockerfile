# Stage 1 — Build Flutter web
FROM ghcr.io/cirruslabs/flutter:3.29.2 AS builder

WORKDIR /app

# Configure Flutter and pre-download Web SDK
# The Web SDK is needed for flutter build web
RUN flutter config --no-analytics --no-cli-animations 2>/dev/null || true && \
    flutter precache --web 2>/dev/null || \
    flutter doctor 2>/dev/null || true

# Copier pubspec d'abord pour bénéficier du cache Docker
COPY mobile/pubspec.yaml ./
RUN touch pubspec.lock && flutter pub get

# Copier tout le source mobile
COPY mobile/ .

# Créer des icônes placeholder (PNG minimal valide) si absentes
RUN mkdir -p web/icons && \
    python3 -c "import base64,os; px=base64.b64decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='); [open(('web/icons/'+n) if 'Icon' in n else ('web/'+n),'wb').write(px) for n in ['Icon-192.png','Icon-512.png','Icon-maskable-192.png','Icon-maskable-512.png','favicon.png'] if not os.path.exists(('web/icons/'+n) if 'Icon' in n else ('web/'+n))]"

# Build web avec l'URL API injectée
ARG API_URL=http://localhost:8080/api/v1
RUN flutter build web --release --dart-define=API_URL=${API_URL}

# Stage 2 — Servir avec nginx
FROM nginx:1.25-alpine

COPY --from=builder /app/build/web /usr/share/nginx/html

# Config nginx pour SPA
RUN printf 'server {\n\
    listen 80;\n\
    root /usr/share/nginx/html;\n\
    index index.html;\n\
    gzip on;\n\
    gzip_types text/plain application/javascript text/css application/json;\n\
    location / {\n\
        try_files $uri $uri/ /index.html;\n\
    }\n\
}\n' > /etc/nginx/conf.d/default.conf

EXPOSE 80
