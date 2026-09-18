import type { Item } from "./item";

export interface User extends Item {
  email?: string;
  roles?: string[];
  password?: string;
  username?: string;
  isLdap?: boolean;
  type?: "local" | "ldap";
  image?: string;
  avatar?: string;
  firstName?: string;
  lastName?: string;
  displayName?: string;
  title?: string;
  department?: string;
  managerDn?: string;
  manager?: User | string | null;
  distinguishedName?: string;
  syncedAt?: string;
  ldapUid?: string;
}
