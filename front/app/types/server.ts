import type { Item } from "./item";

export interface ServerType extends Item {
  name?: string;
}

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
  type?: ServerType | string;
  authenticationType?: ServerAuthenticationType | string;
  proxy?: any;
  integrations?: any[];
}
