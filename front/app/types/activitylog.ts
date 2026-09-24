import type { Item } from "./item";

export interface ActivityLog extends Item {
  entityType?: string;
  entityId?: string;
  entityLabel?: string;
  action?: string;
  actor?: any;
  createdAt?: string;
}
