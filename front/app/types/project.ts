import type { Item } from "./item";
import type { ProjectIntegration } from "./projectintegration";

export interface Project extends Item {
  name?: string;
  organisation?: any;
  projectIntegrations?: ProjectIntegration[] | string[];
}
