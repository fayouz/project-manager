import { defineStore } from "pinia";
import { getEntrypoint } from "~/utils/config";

export interface CurrentUser {
  id: number;
  email: string;
  username: string;
  roles: string[];
  type: "local" | "ldap";
  isLdap: boolean;
  image?: string | null;
  avatar?: string | null;
  firstName?: string | null;
  lastName?: string | null;
  displayName?: string | null;
  title?: string | null;
  department?: string | null;
  managerDn?: string | null;
  manager?: {
    id: number;
    email: string;
    username?: string;
    displayName?: string;
    avatar?: string;
  } | null;
}

export const useAuthStore = defineStore("auth", () => {
  const token = useCookie<string | null>("jwt_token", {
    maxAge: 60 * 60 * 24, // 24 heures
    sameSite: "lax",
    secure: false,
  });

  const user = ref<CurrentUser | null>(null);
  const isLoading = ref(false);
  const error = ref<string | null>(null);

  const isAuthenticated = computed(() => !!token.value);

  async function login(credentials: { email?: string; username?: string; password: string }) {
    isLoading.value = true;
    error.value = null;
    try {
      const response = await $fetch<{ token: string }>(`${getEntrypoint()}/login_check`, {
        method: "POST",
        body: credentials,
      });

      token.value = response.token;
      await fetchCurrentUser();
      return true;
    } catch (err: any) {
      error.value = err?.data?.message || err?.message || "Identifiants invalides.";
      return false;
    } finally {
      isLoading.value = false;
    }
  }

  async function register(userData: { email: string; password: string; username?: string }) {
    isLoading.value = true;
    error.value = null;
    try {
      await $fetch(`${getEntrypoint()}/register`, {
        method: "POST",
        body: userData,
      });
      return true;
    } catch (err: any) {
      error.value = err?.data?.error || err?.message || "Erreur lors de l'inscription.";
      return false;
    } finally {
      isLoading.value = false;
    }
  }

  async function fetchCurrentUser() {
    if (!token.value) {
      user.value = null;
      return null;
    }

    try {
      const currentUser = await $fetch<CurrentUser>(`${getEntrypoint()}/me`, {
        headers: {
          Authorization: `Bearer ${token.value}`,
        },
      });
      user.value = currentUser;
      return currentUser;
    } catch {
      token.value = null;
      user.value = null;
      return null;
    }
  }

  function logout() {
    token.value = null;
    user.value = null;
    navigateTo("/login");
  }

  return {
    token,
    user,
    isLoading,
    error,
    isAuthenticated,
    login,
    register,
    fetchCurrentUser,
    logout,
  };
});
