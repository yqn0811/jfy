<script setup lang="ts">
import { navigateTo } from '@/navigation'
</script>

<template>
  <div class="min-h-screen bg-gradient-to-b from-background to-muted/20 flex flex-col">
    <header class="border-b border-border bg-card/80 backdrop-blur-sm">
      <div class="mx-auto flex max-w-2xl items-center justify-center px-4 py-4 sm:px-6">
        <button class="flex items-center gap-2" @click="navigateTo('/workbench')">
          <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary text-primary-foreground">
            <span class="text-sm font-bold">织</span>
          </div>
          <span class="text-lg font-bold text-foreground">织序传输</span>
        </button>
      </div>
    </header>

    <main class="flex flex-1 items-start justify-center px-4 py-6 sm:py-8 md:py-12">
      <div class="w-full max-w-2xl">
        <slot />
      </div>
    </main>

    <footer class="border-t border-border bg-card/40 px-4 py-5 text-center text-caption">
      <p>© 2026 织序传输助手。隐私政策 · 服务条款</p>
    </footer>
  </div>
</template>
