import { useAuthStore } from "~/stores/auth";

export default defineNuxtRouteMiddleware(async (to) => {
  const publicRoutes = ["/setup", "/login", "/register", "/docs", "/guide", "/changelog"];
  const isPublicRoute = publicRoutes.some(
    (route) =>
      to.path === route ||
      to.path.startsWith("/docs") ||
      to.path.startsWith("/guide") ||
      to.path.startsWith("/changelog"),
  );

  const token = useCookie<string | null>("jwt_token").value;
  const authStore = useAuthStore();

  // Si l'utilisateur a un token mais que ses données profil ne sont pas chargées, les récupérer
  if (token && !authStore.user) {
    await authStore.fetchCurrentUser();
  }

  // Si l'utilisateur n'est pas connecté et tente d'accéder à une page protégée (dashboard, gestion, etc.)
  if (!token && !isPublicRoute) {
    return navigateTo({
      path: "/login",
      query: to.path !== "/" ? { redirect: to.fullPath } : undefined,
    });
  }

  // Si l'utilisateur est déjà connecté et va sur /login ou /register
  if (token && (to.path === "/login" || to.path === "/register")) {
    return navigateTo("/dashboard");
  }

  // Si l'utilisateur connecté arrive sur la racine '/', le diriger vers /dashboard
  if (token && to.path === "/") {
    return navigateTo("/dashboard");
  }
});
