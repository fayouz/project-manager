import type { Item } from "./item";

export interface DeploymentServer extends Item {
  name?: string;
  webserverUrl?: string;
  host?: string;
  port?: number;
  description?: string;
  stagings?: any;
}
