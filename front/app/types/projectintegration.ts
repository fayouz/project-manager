import type { Item } from "./item";
import type { Integration } from "./integration";
import type { Project } from "./project";

export interface ProjectIntegration extends Item {
  project?: Project | string;
  integration?: Integration | string;
  parameters?: Record<string, any>;
  createdAt?: string;
  updatedAt?: string;
}
