import type { Item } from "./item";
import type { Server } from "./server";

export interface ConnectionTestResult {
  success: boolean;
  status: 'healthy' | 'error' | 'unknown' | string;
  statusMessage: string;
  lastCheckedAt?: string;
  details?: Record<string, any>;
}

export interface Integration extends Item {
  name?: string;
  type?: string;
  enabled?: boolean;
  server?: string | Server;
  status?: string;
  statusMessage?: string;
  lastCheckedAt?: string;
  createdAt?: string;
  updatedAt?: string;
}
