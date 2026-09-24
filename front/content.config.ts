import { defineCollection, defineContentConfig, z } from "@nuxt/content";

export default defineContentConfig({
  collections: {
    guide: defineCollection({
      type: "page",
      source: "guide/**/*.md",
      schema: z.object({
        navigation: z
          .object({
            title: z.string().optional(),
            icon: z.string().optional(),
          })
          .optional(),
      }),
    }),
    changelog: defineCollection({
      type: "page",
      source: "changelog/**/*.md",
      schema: z.object({
        date: z.string(),
        badge: z.string().optional(),
        image: z.string().optional(),
        authors: z
          .array(
            z.object({
              name: z.string(),
              avatar: z
                .object({
                  src: z.string().optional(),
                })
                .optional(),
              to: z.string().optional(),
            }),
          )
          .optional(),
      }),
    }),
  },
});
