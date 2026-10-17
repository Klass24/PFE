# Frontend - Configuration API

## Configuration de l'URL de l'API

Le frontend peut être configuré pour appeler la bonne URL de l'API selon l'environnement.

### En développement (npm run dev)

**Port par défaut**: http://localhost:5173

- Le proxy Vite redirige `/api/*` vers http://127.0.0.1:8080**OU**
- La variable `VITE_API_URL` définit l'URL de l'API
- **Fallback**: Si aucune configuration, utilise automatiquement http://localhost:8080/api/v1

```bash
cd frontend
npm install
npm run dev
```

Les appels API devraient se faire directement vers **http://localhost:8080/api/v1** (le backend).

### En production (Docker)

**Port exposé**: http://localhost:3000

- Le Nginx du frontend intercept les appels `/api/v1`
- Les redirige vers le service backend `nginx:80` dans le docker network
- **La baseURL relative `/api/v1` est utilisée**

```bash
docker-compose up -d
```

Les appels API depuis le navigateur vont à http://localhost:3000/api/v1 et sont automatiquement redirigées par le proxy Nginx du frontend.

## Configuration via .env

### .env.development
```
VITE_API_URL=/api/v1
```
Ou laisser vide pour utiliser le fallback automatique (recommandé).

### .env.production
```
VITE_API_URL=/api/v1
```
En production Docker, utiliser l'URL relative pour être proxifiée par Nginx.

## Architecture du proxy

### En développement local
```
Frontend (localhost:5173)
    ↓ (proxy Vite)
Backend (localhost:8080/api/v1)
```

### En production Docker
```
Navigateur (localhost:3000)
    ↓ HTTP request
Frontend Nginx (localhost:3000) 
    ↓ proxy /api/
Backend Nginx (service nginx:80)
    ↓
PHP-FPM (backend)
```

## Vérifier la configuration

Ouvrir les DevTools du navigateur (Ctrl+F12):
- **Onglet Network**: Voir les appels GET/POST vers l'API
- **Correct**: Les appels vont vers http://localhost:8080/api/v1 (développement) ou http://localhost:3000/api/v1 (production)

Si vous voyez des appels vers http://localhost:3000/api/v1 en développement, c'est une erreur. Vérifiez que:
- Le proxy Vite est actif
- Le backend tourne sur http://localhost:8080
- Les dépendances npm sont installées
