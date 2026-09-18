import type { Item } from "./item";

export interface TeamMember extends Item {
  team?: any;
  user?: any;
  role?: string;
  joinedAt?: string;
}
