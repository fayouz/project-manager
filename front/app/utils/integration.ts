import type { ProjectIntegration } from "~/types/projectIntegration";
import type { Integration } from "~/types/integration";
import type { Server } from "~/types/server";

export function getIntegration(pi: ProjectIntegration): Integration | null {
  if (typeof pi.integration === "object" && pi.integration !== null) {
    return pi.integration as Integration;
  }
  return null;
}

export function getIntegrationType(pi: ProjectIntegration): string {
  const integ = getIntegration(pi);
  return (integ?.type || "").toLowerCase();
}

export function getIntegrationName(pi: ProjectIntegration): string {
  const integ = getIntegration(pi);
  return integ?.name || getIntegrationType(pi).toUpperCase();
}

export function getServer(item?: ProjectIntegration | Integration | null): Server | null {
  if (!item) return null;
  let integ: Integration | null = null;
  if ("integration" in item) {
    integ = getIntegration(item as ProjectIntegration);
  } else {
    integ = item as Integration;
  }

  if (integ?.server && typeof integ.server === "object") {
    return integ.server as Server;
  }
  return null;
}

export function getServerName(item?: Server | ProjectIntegration | Integration | null): string {
  if (!item) return "Serveur distant";
  let server: Server | null = null;
  if ("host" in item || "port" in item) {
    server = item as Server;
  } else {
    server = getServer(item as ProjectIntegration | Integration);
  }
  return server?.name || "Serveur distant";
}

export function getServerHost(item?: Server | ProjectIntegration | Integration | null): string {
  if (!item) return "Hôte non renseigné";
  let server: Server | null = null;
  if ("host" in item || "port" in item) {
    server = item as Server;
  } else {
    server = getServer(item as ProjectIntegration | Integration);
  }
  if (!server?.host) return "Hôte non renseigné";
  if (server.port && server.port !== 80 && server.port !== 443) {
    return `${server.host}:${server.port}`;
  }
  return server.host;
}

export function getServerStatus(item?: Server | ProjectIntegration | Integration | null): string {
  if (!item) return "unknown";
  if ("integration" in item) {
    const integ = getIntegration(item as ProjectIntegration);
    return integ?.status || "unknown";
  }
  if ("type" in item && "server" in item) {
    return (item as Integration).status || "unknown";
  }
  return "unknown";
}

export function getStatusBadgeColor(status?: string): "success" | "warning" | "error" | "neutral" {
  switch (status?.toLowerCase()) {
    case "healthy":
    case "ok":
    case "active":
      return "success";
    case "warning":
      return "warning";
    case "error":
    case "failed":
      return "error";
    default:
      return "neutral";
  }
}

export function getServerBaseUrl(server?: Server | any | null): string {
  if (!server || !server.host) return "";
  const protocol = server.options?.protocol || "http";
  const port =
    server.port &&
    !(
      (protocol === "http" && server.port === 80) ||
      (protocol === "https" && server.port === 443)
    )
      ? `:${server.port}`
      : "";
  return `${protocol}://${server.host}${port}`;
}

export function getExternalUrl(pi: ProjectIntegration): string | null {
  const integ = getIntegration(pi);
  const server = getServer(pi);
  if (!server) return null;

  const baseUrl = getServerBaseUrl(server);
  if (!baseUrl) return null;

  const params = pi.parameters || {};
  const type = integ?.type?.toLowerCase();

  if (type === "gitea" && params.repository) {
    return `${baseUrl}/${params.repository}`;
  }
  if (type === "sonarqube" && params.project_key) {
    return `${baseUrl}/dashboard?id=${encodeURIComponent(params.project_key)}`;
  }
  if (type === "jenkins") {
    if (params.folder || params.folder_path) {
      const f = (params.folder || params.folder_path).replace(/^\/+/, "");
      return `${baseUrl}/${f}`;
    }
    if (params.job || params.job_name) {
      return `${baseUrl}/job/${encodeURIComponent(params.job || params.job_name)}`;
    }
  }
  if (type === "mantis" && params.project_id) {
    return `${baseUrl}/view_all_bug_page.php?project_id=${encodeURIComponent(params.project_id)}`;
  }
  if (type === "nexus") {
    if (params.repository) {
      return `${baseUrl}/#browse/browse:${encodeURIComponent(params.repository)}`;
    }
    return baseUrl;
  }

  return baseUrl;
}

export function getTypeIcon(type?: string): string {
  switch (type?.toLowerCase()) {
    case "gitea":
      return "i-heroicons-code-bracket";
    case "jenkins":
      return "i-heroicons-arrow-path-rounded-square";
    case "mantis":
      return "i-heroicons-bug-ant";
    case "sonarqube":
      return "i-heroicons-shield-check";
    case "nexus":
      return "i-heroicons-cube";
    default:
      return "i-heroicons-puzzle-piece";
  }
}

export function getTypeBadgeColor(type?: string): "warning" | "info" | "success" | "primary" | "neutral" {
  switch (type?.toLowerCase()) {
    case "gitea":
      return "warning";
    case "jenkins":
      return "info";
    case "mantis":
      return "success";
    case "sonarqube":
      return "primary";
    case "nexus":
      return "info";
    default:
      return "neutral";
  }
}

export function getTypeBgClass(type?: string): string {
  switch (type?.toLowerCase()) {
    case "gitea":
      return "bg-amber-50 dark:bg-amber-950/30 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-900/50";
    case "jenkins":
      return "bg-sky-50 dark:bg-sky-950/30 text-sky-600 dark:text-sky-400 border border-sky-200 dark:border-sky-900/50";
    case "mantis":
      return "bg-emerald-50 dark:bg-emerald-950/30 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-900/50";
    case "sonarqube":
      return "bg-blue-50 dark:bg-blue-950/30 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-900/50";
    case "nexus":
      return "bg-teal-50 dark:bg-teal-950/30 text-teal-600 dark:text-teal-400 border border-teal-200 dark:border-teal-900/50";
    default:
      return "bg-neutral-50 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-300 border border-neutral-200 dark:border-neutral-700";
  }
}

export function getStatusDotClass(status?: string): string {
  switch (status?.toLowerCase()) {
    case "healthy":
    case "ok":
    case "active":
      return "bg-emerald-500";
    case "warning":
      return "bg-amber-500";
    case "error":
    case "failed":
      return "bg-red-500";
    default:
      return "bg-neutral-400";
  }
}

export function getStatusLabel(status?: string): string {
  switch (status?.toLowerCase()) {
    case "healthy":
    case "ok":
    case "active":
      return "Opérationnel";
    case "warning":
      return "Attention";
    case "error":
    case "failed":
      return "Erreur";
    default:
      return "Non testé";
  }
}

export function isStandardParam(key: string, type?: string): boolean {
  switch (type?.toLowerCase()) {
    case "gitea":
      return key === "repository" || key === "branch";
    case "sonarqube":
      return key === "project_key";
    case "jenkins":
      return key === "folder" || key === "folder_path" || key === "job" || key === "job_name" || key === "jobs" || key === "jobs_count";
    case "mantis":
      return key === "project_id" || key === "project_name";
    case "nexus":
      return key === "repository" || key === "group" || key === "format";
    default:
      return false;
  }
}

export function formatParamKey(key: string, type?: string): string {
  if (type === "gitea" && key === "repository") return "Dépôt Gitea (chemin)";
  if (type === "gitea" && key === "branch") return "Branche par défaut";
  if (type === "sonarqube" && key === "project_key") return "Clé de projet SonarQube";
  if (type === "jenkins" && (key === "folder" || key === "folder_path")) return "Dossier des jobs Jenkins";
  if (type === "jenkins" && (key === "jobs" || key === "jobs_count")) return "Jobs référencés";
  if (type === "jenkins" && (key === "job" || key === "job_name")) return "Nom du Job Jenkins";
  if (type === "mantis" && key === "project_id") return "Identifiant Mantis";
  if (type === "mantis" && key === "project_name") return "Nom du projet Mantis";
  if (type === "nexus" && key === "repository") return "Dépôt Nexus (repository)";
  if (type === "nexus" && key === "group") return "Groupe de composants";
  if (type === "nexus" && key === "format") return "Format du dépôt";
  return key;
}

export function getTargetDisplay(pi?: ProjectIntegration | null): string {
  if (!pi) return "Non configuré";
  if (pi.targetDisplay) return pi.targetDisplay;

  const type = getIntegrationType(pi);
  const params = pi.parameters || {};

  if (type === "mantis") {
    const id = params.project_id;
    const name = params.project_name;
    if (name && id) return `${name} (#${id})`;
    if (name) return `${name}`;
    if (id) return `#${id}`;
    return "Non configuré";
  }

  if (type === "gitea") {
    if (params.repository && params.branch) return `${params.repository} (${params.branch})`;
    if (params.repository) return params.repository;
    return "Non configuré";
  }

  if (type === "sonarqube") {
    return params.project_key || "Non configuré";
  }

  if (type === "jenkins") {
    const f = params.folder || params.folder_path || params.job_folder;
    if (f) {
      const count = params.jobs_count || (Array.isArray(params.jobs) ? params.jobs.length : 0);
      return count > 0 ? `${f} (${count} job${count > 1 ? "s" : ""})` : f;
    }
    return params.job_name || params.job || "Non configuré";
  }

  if (type === "nexus") {
    if (params.repository && params.group) return `${params.repository} (${params.group})`;
    if (params.repository) return params.repository;
    return "Non configuré";
  }

  const values = Object.values(params);
  if (values.length > 0 && typeof values[0] === "string") {
    return values[0];
  }

  return "Non configuré";
}

export function getServerNameAndHost(item?: Server | ProjectIntegration | Integration | null): string {
  if (!item) return "Inconnu";
  let server: Server | null = null;
  if ("host" in item || "port" in item) {
    server = item as Server;
  } else {
    server = getServer(item as ProjectIntegration | Integration);
  }
  if (!server) return "Inconnu";
  if (server.name && server.host && server.name !== server.host) {
    return `${server.name} (${server.host})`;
  }
  return server.name || server.host || "Inconnu";
}
