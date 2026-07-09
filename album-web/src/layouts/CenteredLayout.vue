<template>
  <main class="min-h-screen w-full flex flex-col items-center justify-center bg-background p-4 relative overflow-hidden">
    <div class="absolute top-[-10%] right-[-10%] w-[40%] h-[40%] bg-primary/5 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-[-10%] left-[-10%] w-[40%] h-[40%] bg-accent/5 rounded-full blur-3xl pointer-events-none"></div>

    <div class="w-full max-w-md z-10">
      <slot />
    </div>

    <footer class="absolute bottom-8 text-caption text-center w-full px-4">
      <p>&copy; 2026 家纺云相册</p>
    </footer>
  </main>
</template>
