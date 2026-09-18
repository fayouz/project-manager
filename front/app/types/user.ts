import type { Item } from "./item";

export interface User extends Item {
  email?: string;
  roles?: string[];
  password?: string;
  username?: string;
  isLdap?: boolean;
}
