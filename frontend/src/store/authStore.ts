import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import type { AuthUser } from '@/types';

interface AuthState {
  token: string | null;
  tokenExpiresAt: number | null;
  user: AuthUser | null;
  isAuthenticated: boolean;
  setAuth: (token: string, user: AuthUser, expiresInSeconds?: number) => void;
  logout: () => void;
  isTokenExpired: () => boolean;
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      token: null,
      tokenExpiresAt: null,
      user: null,
      isAuthenticated: false,

      setAuth: (token, user, expiresInSeconds = 86400) =>
        set({
          token,
          user,
          isAuthenticated: true,
          tokenExpiresAt: Date.now() + expiresInSeconds * 1000,
        }),

      logout: () =>
        set({
          token: null,
          tokenExpiresAt: null,
          user: null,
          isAuthenticated: false,
        }),

      isTokenExpired: () => {
        const { tokenExpiresAt } = get();
        if (!tokenExpiresAt) return true;
        return Date.now() > tokenExpiresAt;
      },
    }),
    {
      name: 'fleetcontrol-auth',
    }
  )
);
