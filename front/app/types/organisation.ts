import type { Item } from "./item";

export interface Organisation extends Item {
  name?: string;
  projects?: any;
}
