import type { Item } from "./item";

export interface ServerAuthenticationType extends Item {
  name?: string;
}

export interface Server extends Item {
  name?: string;
  host?: string;
  port?: number;
  username?: string;
  password?: string;
  options?: Record<string, any>;
  type?: any;
  authenticationType?: ServerAuthenticationType | string;
}
