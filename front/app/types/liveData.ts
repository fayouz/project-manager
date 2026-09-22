export interface GiteaCommit {
  sha: string;
  shortSha: string;
  message: string;
  author: string;
  authorUsername?: string;
  authorEmail?: string;
  date: string;
  url?: string;
}

export interface GiteaBranch {
  name: string;
  commitSha: string;
  isDefault: boolean;
}

export interface GiteaPullRequest {
  number: number;
  title: string;
  state: string;
  author: string;
  createdAt: string;
  merged: boolean;
  url?: string;
}

export interface GiteaTag {
  name: string;
  commitSha: string;
}

export interface GiteaFile {
  name: string;
  path: string;
  type: "file" | "dir";
  size: number;
}

export interface GiteaLiveData {
  repository: string;
  name: string;
  fullName: string;
  defaultBranch: string;
  isPrivate: boolean;
  url: string;
  cloneUrl: string;
  sshUrl: string;
  stats: {
    stars: number;
    forks: number;
    openIssues: number;
    openPulls: number;
  };
  commits: GiteaCommit[];
  branches: GiteaBranch[];
  pullRequests: GiteaPullRequest[];
  tags: GiteaTag[];
  files: GiteaFile[];
  readme?: string | null;
}

export interface SonarCondition {
  metric: string;
  operator: string;
  errorThreshold: string;
  actualValue: string;
  status: string;
}

export interface SonarIssue {
  key: string;
  severity: "INFO" | "MINOR" | "MAJOR" | "CRITICAL" | "BLOCKER" | string;
  type: "BUG" | "VULNERABILITY" | "CODE_SMELL" | "SECURITY_HOTSPOT" | string;
  message: string;
  component: string;
  line: number;
  effort?: string;
  rule?: string;
  creationDate?: string;
}

export interface SonarQubeLiveData {
  projectKey: string;
  name: string;
  url: string;
  qualityGate: {
    status: "OK" | "ERROR" | "WARN" | "UNKNOWN" | string;
    conditions: SonarCondition[];
  };
  metrics: {
    bugs: number;
    reliabilityRating: string;
    vulnerabilities: number;
    securityRating: string;
    securityHotspots: number;
    codeSmells: number;
    maintainabilityRating: string;
    debtMinutes: number;
    debtDisplay: string;
    coverage: number;
    duplications: number;
    linesOfCode: number;
  };
  issues: SonarIssue[];
}

export interface MantisIssue {
  id: number;
  summary: string;
  description: string;
  category: string;
  severity: string;
  priority: string;
  status: string;
  statusCode: number;
  resolution: string;
  reporter: string;
  handler: string;
  dateSubmitted: string;
  lastUpdated: string;
}

export interface MantisMilestone {
  name: string;
  progress: number;
  totalIssues: number;
  resolvedIssues: number;
  status: string;
  dueDate?: string;
}

export interface MantisLiveData {
  projectId: number;
  projectName: string;
  url: string;
  stats: {
    total: number;
    open: number;
    inProgress: number;
    new: number;
    resolved: number;
    resolutionRate: number;
    bySeverity: Record<string, number>;
  };
  issues: MantisIssue[];
  roadmap: MantisMilestone[];
}

export interface JenkinsBuild {
  number: number;
  url: string;
  result: "SUCCESS" | "FAILURE" | "UNSTABLE" | "ABORTED" | "BUILDING" | string;
  timestamp: number;
  duration: number;
  durationFormatted?: string;
  building: boolean;
  jobName?: string;
  jobUrl?: string;
}

export interface JenkinsJob {
  name: string;
  displayName: string;
  url: string;
  color: string;
  class: string;
  description?: string;
  status: "SUCCESS" | "FAILURE" | "UNSTABLE" | "BUILDING" | "DISABLED" | "NOT_BUILT" | "UNKNOWN" | string;
  statusLabel: string;
  statusBadgeColor: "success" | "error" | "warning" | "primary" | "neutral";
  isBuilding: boolean;
  lastBuild?: JenkinsBuild | null;
  lastSuccessfulBuild?: { number: number; url: string; timestamp: number } | null;
  lastFailedBuild?: { number: number; url: string; timestamp: number } | null;
}

export interface JenkinsLiveData {
  folder: string;
  folderName: string;
  url: string;
  jobs: JenkinsJob[];
  stats: {
    total: number;
    success: number;
    failure: number;
    unstable: number;
    building: number;
    disabled: number;
  };
  lastBuild?: JenkinsBuild | null;
}

export interface NexusRepository {
  name: string;
  format: string;
  type: string;
  url: string;
  online: boolean;
}

export interface NexusAsset {
  id: string;
  downloadUrl: string;
  path: string;
  format: string;
  fileSize?: number | null;
  contentType: string;
  lastModified: string;
  checksum?: Record<string, string>;
}

export interface NexusComponent {
  id: string;
  repository: string;
  format: string;
  group: string;
  name: string;
  version: string;
  assets: NexusAsset[];
}

export interface NexusLiveData {
  repository?: string | null;
  group?: string | null;
  format?: string | null;
  type?: string | null;
  online?: boolean;
  url: string;
  baseUrl?: string;
  repositories: NexusRepository[];
  componentsCount: number;
  components: NexusComponent[];
  assetsCount: number;
  assets: NexusAsset[];
}

export interface IntegrationLiveDataResponse<T = any> {
  success: boolean;
  type?: string;
  message?: string;
  data?: T;
}
