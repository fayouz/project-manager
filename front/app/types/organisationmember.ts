import type { Item } from "./item";

export interface OrganisationMember extends Item {
  organisation?: any;
  user?: any;
  role?: string;
  joinedAt?: string;
}
