import type { Item } from "./item";

export interface Staging extends Item {
  name?: string;
  project?: any;
  deploymentServer?: any;
  environment?: string;
  status?: string;
  branch?: string;
  description?: string;
}
