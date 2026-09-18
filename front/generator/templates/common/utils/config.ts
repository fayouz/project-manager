export function getEntrypoint(): string {
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
    url = "{{entrypoint}}";
  }

  // Côté serveur interne Docker, si Caddy tourne en auto_https off, basculer sur http
  if (typeof window === "undefined" && url.startsWith("https://local-project-manager.localhost")) {
    url = url.replace("https://", "http://");
  }

  return url;
}

export function resolveApiUrl(path: string = ""): string {
  if (!path) {
    return getEntrypoint();
  }

  // Si c'est déjà une URL absolue complète (ex: https://...)
  if (path.startsWith("http://") || path.startsWith("https://")) {
    return path;
  }

  const entrypoint = getEntrypoint().replace(/\/+$/, "");

  // Si le chemin commence par /api ou api, on retire le préfixe pour ne pas doubler avec entrypoint
  let cleanPath = path;
  if (cleanPath.startsWith("/api/")) {
    cleanPath = cleanPath.substring(4); // conserve le slash initial: '/...'
  } else if (cleanPath === "/api") {
    cleanPath = "";
  } else if (cleanPath.startsWith("api/")) {
    cleanPath = cleanPath.substring(3);
  } else if (!cleanPath.startsWith("/")) {
    cleanPath = `/${cleanPath}`;
  }

  return `${entrypoint}${cleanPath}`;
}

export const ENTRYPOINT = "{{entrypoint}}";
