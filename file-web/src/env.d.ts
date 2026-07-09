/// <reference types="vite/client" />

interface ImportMetaEnv {
  readonly PUBLIC_API_BASE?: string
  readonly PUBLIC_ENABLE_MOCK?: string
  readonly PUBLIC_JFYUNTU_BASE_PATH?: string
  readonly PUBLIC_JFYUNTU_MOCK?: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}
