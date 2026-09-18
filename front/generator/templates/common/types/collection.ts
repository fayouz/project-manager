import type { View } from "./view";

export interface PagedCollection<T> {
  "@context"?: string;
  "@id"?: string;
  "@type"?: string;
  "hydra:member"?: T[];
  member?: T[];
  "hydra:totalItems"?: number;
  totalItems?: number;
  "hydra:view"?: View;
  view?: View;
  "hydra:search"?: object;
}
