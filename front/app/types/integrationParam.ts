export interface IntegrationParamInterface {
  type: string;
  targetDisplay?: string;
  [key: string]: any;
}

export interface MantisIntegrationParam extends IntegrationParamInterface {
  type: "mantis";
  project_id?: string | number;
  project_name?: string;
}

export interface GiteaIntegrationParam extends IntegrationParamInterface {
  type: "gitea";
  repository?: string;
  branch?: string;
}

export interface SonarQubeIntegrationParam extends IntegrationParamInterface {
  type: "sonarqube";
  project_key?: string;
}

export interface JenkinsIntegrationParam extends IntegrationParamInterface {
  type: "jenkins";
  job_name?: string;
  job?: string;
}

export interface NexusIntegrationParam extends IntegrationParamInterface {
  type: "nexus";
  repository?: string;
  group?: string;
  format?: string;
}

export type AnyIntegrationParam =
  | MantisIntegrationParam
  | GiteaIntegrationParam
  | SonarQubeIntegrationParam
  | JenkinsIntegrationParam
  | NexusIntegrationParam
  | IntegrationParamInterface;
