/// <reference types="vite/client" />

declare module 'vue' {
  interface ComponentCustomProperties {
    $appTheme: string | null
  }
}

export {}
