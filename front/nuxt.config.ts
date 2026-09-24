// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  future: {
    compatibilityVersion: 4
  },
  compatibilityDate: '2024-04-03',
  devtools: { enabled: true },
  modules: [
    '@nuxt/ui',
    '@nuxt/content',
    '@pinia/nuxt'
  ],
  css: [
    '~/assets/css/main.css'
  ],
  runtimeConfig: {
    public: {
      apiBase: 'local-project-manager.localhost/api'
    }
  },
  vite: {
    server: {
      allowedHosts: ['front', 'php', 'local-project-manager.localhost', 'test-project-manager.localhost', 'test-project-manager.test']
    }
  },
  icon: {
    serverBundle: {
      collections: ['heroicons', 'lucide']
    }
  }
})
