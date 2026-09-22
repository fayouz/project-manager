import type { Item } from "./item";

export interface Proxy extends Item {
  name?: string;
  url?: string;
  username?: string;
  password?: string;
  noProxy?: string;
  enabled?: boolean;
  createdAt?: string;
  updatedAt?: string;
}
