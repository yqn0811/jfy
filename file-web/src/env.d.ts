/// <reference types="vite/client" />

interface ImportMetaEnv {
  readonly PUBLIC_API_BASE?: string
  readonly PUBLIC_JFYUNTU_BASE_PATH?: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}
