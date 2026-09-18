import type { Item } from "./item";

export interface ProjectMember extends Item {
  project?: any;
  user?: any;
  role?: string;
  joinedAt?: string;
}
