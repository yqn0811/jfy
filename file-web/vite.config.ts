import path from 'node:path'
import { fileURLToPath } from 'node:url'

import vue from '@vitejs/plugin-vue'
import AutoImport from 'unplugin-auto-import/vite'
import { defineConfig } from 'vite'

const rootDir = fileURLToPath(new URL('.', import.meta.url))

export default defineConfig({
  base: './',
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
        entryFileNames: 'assets/[name].[hash].js',
        chunkFileNames: 'assets/[name].[hash].js',
        assetFileNames: 'assets/[name].[hash][extname]',
      },
    },
  },
})
