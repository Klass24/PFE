import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { useAuthStore } from '../store/authStore';
import type { AuthUser } from '../types';

const user: AuthUser = {
  uuid: 'user-test',
  name: 'Test Admin',
  email: 'admin@example.com',
  role: 'admin',
  company_id: 1,
};

describe('Authentication store', () => {
  beforeEach(() => {
    vi.useFakeTimers();
    vi.setSystemTime(new Date('2026-01-01T12:00:00Z'));
    useAuthStore.getState().logout();
    localStorage.clear();
  });

  afterEach(() => {
    useAuthStore.getState().logout();
    localStorage.clear();
    vi.useRealTimers();
  });

  it('starts unauthenticated with an expired token', () => {
    expect(useAuthStore.getState()).toMatchObject({
      token: null,
      user: null,
      isAuthenticated: false,
      tokenExpiresAt: null,
    });
    expect(useAuthStore.getState().isTokenExpired()).toBe(true);
  });

  it('authenticates the user with a default lifetime of one day', () => {
    useAuthStore.getState().setAuth('test-token', user);

    expect(useAuthStore.getState()).toMatchObject({
      token: 'test-token',
      user,
      isAuthenticated: true,
      tokenExpiresAt: Date.now() + 86400 * 1000,
    });
    expect(useAuthStore.getState().isTokenExpired()).toBe(false);
  });

  it('honors the lifetime supplied by the API', () => {
    useAuthStore.getState().setAuth('test-token', user, 60);

    expect(useAuthStore.getState().tokenExpiresAt).toBe(Date.now() + 60000);
    vi.advanceTimersByTime(59000);
    expect(useAuthStore.getState().isTokenExpired()).toBe(false);
  });

  it('detects expiration after the token lifetime', () => {
    useAuthStore.getState().setAuth('test-token', user, 60);
    vi.advanceTimersByTime(60001);

    expect(useAuthStore.getState().isTokenExpired()).toBe(true);
  });

  it('clears all session data on logout', () => {
    useAuthStore.getState().setAuth('test-token', user);
    useAuthStore.getState().logout();

    expect(useAuthStore.getState()).toMatchObject({
      token: null,
      user: null,
      isAuthenticated: false,
      tokenExpiresAt: null,
    });
    expect(useAuthStore.getState().isTokenExpired()).toBe(true);
  });

  it('persists authentication and removes credentials from storage on logout', () => {
    useAuthStore.getState().setAuth('test-token', user);

    expect(JSON.parse(localStorage.getItem('fleetcontrol-auth')!).state).toMatchObject({
      token: 'test-token',
      user,
      isAuthenticated: true,
    });

    useAuthStore.getState().logout();

    expect(JSON.parse(localStorage.getItem('fleetcontrol-auth')!).state).toMatchObject({
      token: null,
      user: null,
      isAuthenticated: false,
    });
  });
});