import path from 'node:path'
import { fileURLToPath } from 'node:url'

import vue from '@vitejs/plugin-vue'
import AutoImport from 'unplugin-auto-import/vite'
import { defineConfig } from 'vite'

function normalizeBasePath(value?: string) {
  const raw = (value || '/').trim()
  if (!raw || raw === '/') return '/'
  return `/${raw.replace(/^\/+|\/+$/g, '')}/`
}

const rootDir = fileURLToPath(new URL('.', import.meta.url))

export default defineConfig({
  base: normalizeBasePath(process.env.PUBLIC_JFYUNTU_BASE_PATH),
  envPrefix: ['VITE_', 'PUBLIC_'],
  plugins: [
    vue(),
    AutoImport({
      imports: [
        'vue',
        {
          '@/lib/utils': ['cn'],
        },
      ],
      dts: 'src/auto-imports.d.ts',
      vueTemplate: true,
    }),
  ],
  resolve: {
    alias: {
      '@': path.resolve(rootDir, 'src'),
    },
  },
  build: {
    target: 'es2022',
    minify: 'esbuild',
    chunkSizeWarningLimit: 10000,
    rollupOptions: {
      maxParallelFileOps: 24,
      output: {
        entryFileNames: '[name].[hash].js',
        chunkFileNames: '[name].[hash].js',
        assetFileNames: '[name].[hash][extname]',
      },
    },
  },
})
