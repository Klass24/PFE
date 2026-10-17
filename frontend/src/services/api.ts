import axios from 'axios';
import { useAuthStore } from '@/store/authStore';

// Déterminer l'URL de base de l'API
let apiBaseURL = '/api/v1';

// En production/build, garder l'URL relative (sera proxifiée)
// En développement, utiliser la variable d'env ou l'URL complète
if (!import.meta.env.PROD && typeof window !== 'undefined') {
  // En développement (npm run dev), utiliser l'URL complète du backend
  const apiUrl = import.meta.env.VITE_API_URL;
  if (apiUrl) {
    apiBaseURL = apiUrl;
  } else {
    // Fallback: construire l'URL avec le bon host/port
    apiBaseURL = `${window.location.protocol}//${window.location.hostname}:8080/api/v1`;
  }
} else if (import.meta.env.PROD && import.meta.env.VITE_API_URL) {
  // En production, utiliser la variable d'env si fournie
  apiBaseURL = import.meta.env.VITE_API_URL;
}

const api = axios.create({
  baseURL: apiBaseURL,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
});

api.interceptors.request.use((config) => {
  const token = useAuthStore.getState().token;
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

api.interceptors.response.use(
  (response) => response.data,
  (error) => {
    if (error.response?.status === 401) {
      useAuthStore.getState().logout();
      window.location.href = '/login';
    }
    return Promise.reject(
      error.response?.data?.message || 'Une erreur est survenue.'
    );
  }
);

export default api;
