export function getEntrypoint(): string {
  if (typeof window !== "undefined" && window.location?.origin) {
    return `${window.location.origin}/api`;
  }

  try {
    const reqUrl = useRequestURL();
    if (reqUrl?.origin && !reqUrl.origin.includes("localhost:3000") && !reqUrl.origin.includes("front:3000")) {
      let origin = reqUrl.origin;
      if (origin.startsWith("https://")) {
        origin = origin.replace("https://", "http://");
      }
      return `${origin}/api`;
    }
  } catch {
    // En dehors du contexte Nuxt SSR
  }

  let url = "";

  try {
    if (typeof useRuntimeConfig === "function") {
      const config = useRuntimeConfig();
      if (config?.public?.apiBase) {
        url = config.public.apiBase;
      }
    }
  } catch {
    // En dehors du cycle de vie Nuxt
  }

  if (!url && typeof window !== "undefined" && (window as any).__NUXT__?.config?.public?.apiBase) {
    url = (window as any).__NUXT__.config.public.apiBase;
  }

  if (!url && typeof process !== "undefined" && process.env?.NUXT_PUBLIC_API_BASE) {
    url = process.env.NUXT_PUBLIC_API_BASE;
  }

  if (!url) {
    url = "http://local-project-manager.localhost/api";
  }

  // Côté serveur interne Docker, si Caddy tourne en auto_https off, basculer sur http
  if (typeof window === "undefined" && url.startsWith("https://local-project-manager.localhost")) {
    url = url.replace("https://", "http://");
  }

  return url;
}

export const ENTRYPOINT = "http://local-project-manager.localhost/api";
