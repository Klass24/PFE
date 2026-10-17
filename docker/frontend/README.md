# Docker - Frontend

Configuration Docker pour l'application React + Vite du FleetControl.

## Architecture

- **Dockerfile**: Multi-stage build avec Node.js (build) et Nginx (production)
- **nginx.conf**: Configuration reverse proxy avec SPA routing et proxy API

## Étapes du build

1. **Stage 1 - Build Node.js**:
   - Installe les dépendances npm
   - Compile le code React/TypeScript
   - Génère les fichiers statiques dans `dist/`

2. **Stage 2 - Production Nginx**:
   - Serve les fichiers statiques
   - Proxy les appels API vers le backend (`/api/` → service nginx backend)
   - Gère le routing SPA (toutes les routes non-statiques vers index.html)
   - Active la compression gzip et le cache des assets

## Ports

- **Port 3000** (host) → Port 80 (container)

## Configuration Nginx

- **Root**: `/usr/share/nginx/html` (fichiers build du frontend)
- **API Proxy**: Redirige `/api/*` vers le service `nginx` (backend) du docker-compose
- **SPA Routing**: `try_files $uri $uri/ /index.html`
- **Cache**: 
  - Assets statiques (js, css, img): 1 an
  - HTML: No-cache
- **Compression**: Gzip activée pour réduire la taille des fichiers
- **Sécurité**: Headers de sécurité (X-Content-Type-Options, X-Frame-Options, etc.)

## Build et démarrage

```bash
# Build l'image Docker du frontend
docker build -f docker/frontend/Dockerfile -t fleetcontrol-frontend .

# Démarrer via docker-compose
docker-compose up -d frontend

# Voir les logs
docker-compose logs -f frontend
```

## Variables d'environnement

Le frontend utilise la configuration Vite compile-time:
- `VITE_API_URL`: URL de base pour l'API (défaut: `/api/v1`)

## Dépendances

Le frontend dépend du service `app` (PHP) pour l'API via le service `nginx` du backend.

## Performance

- **Multi-stage build**: Réduit la taille finale de l'image (sans Node.js ni dépendances dev)
- **Compression gzip**: Réduit la taille des assets (JS/CSS)
- **Cache HTTP**: Optimise la bande passante avec cache long-term pour les assets
- **Health check**: Vérifie la disponibilité du service
