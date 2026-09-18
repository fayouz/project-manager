import type { Item } from "./item";

export interface Team extends Item {
  name?: string;
  description?: string;
  project?: any;
  members?: any;
  createdAt?: string;
}
