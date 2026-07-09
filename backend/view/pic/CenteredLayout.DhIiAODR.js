import { c as createComponent, b as createAstro, r as renderComponent, a as renderTemplate, m as maybeRenderHead, d as renderSlot } from "./astro/server.CNtEcxKA.js";
import { $ as $$BaseLayout } from "./BaseLayout.DVPaqkBc.js";
/* empty css                                */
const $$Astro = createAstro();
const $$CenteredLayout = createComponent(($$result, $$props, $$slots) => {
  const Astro = $$result.createAstro($$Astro, $$props, $$slots);
  Astro.self = $$CenteredLayout;
  const { title, description } = Astro.props;
  return renderTemplate`${renderComponent($$result, "BaseLayout", $$BaseLayout, { title, description }, { default: ($$result2) => renderTemplate`
  ${maybeRenderHead()}<main class="min-h-screen w-full flex flex-col items-center justify-center bg-background p-4 relative overflow-hidden">
    <!-- Background Decor (Optional but adds aesthetic for standalone pages) -->
    <div class="absolute top-[-10%] right-[-10%] w-[40%] h-[40%] bg-primary/5 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-[-10%] left-[-10%] w-[40%] h-[40%] bg-accent/5 rounded-full blur-3xl pointer-events-none"></div>

    <!-- Content Container -->
    <div class="w-full max-w-md z-10">
      ${renderSlot($$result2, $$slots.default)}
    </div>

    <!-- Footer Copyright (Optional common element for standalone pages) -->
    <footer class="absolute bottom-8 text-caption text-center w-full px-4">
      <p>&copy; 2026 家纺云相册</p>
    </footer>
  </main>
` })}`;
}, "/Users/mac/Documents/trae_projects/sub2api/ai_jf/.codex_tmp/jfy-backend-homefix/album-web/src/layouts/CenteredLayout.astro", void 0);
export {
  $$CenteredLayout as $
};
