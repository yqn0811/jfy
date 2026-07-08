import { c as createComponent, b as createAstro, r as renderComponent, a as renderTemplate, m as maybeRenderHead, d as renderSlot } from "./astro/server.DafmnnCm.js";
import { $ as $$BaseLayout } from "./BaseLayout.51tEWhO9.js";
const $$Astro = createAstro();
const $$ManagementLayout = createComponent(($$result, $$props, $$slots) => {
  const Astro = $$result.createAstro($$Astro, $$props, $$slots);
  Astro.self = $$ManagementLayout;
  const { title, description } = Astro.props, currentPath = Astro.url.pathname;
  return renderTemplate`${renderComponent($$result, "BaseLayout", $$BaseLayout, { title, description }, { default: ($$result2) => renderTemplate`
  ${maybeRenderHead()}<div class="flex flex-col h-screen overflow-hidden">
    <!-- Header is client:load to handle user menu and navigation -->
    ${renderComponent($$result2, "ManagementHeader", null, { "client:only": "vue", "client:component-hydration": "only", "client:component-path": "@/components/common/ManagementHeader.vue", "client:component-export": "default" })}

    <div class="flex-1 min-h-0">
      ${renderComponent($$result2, "AuthGate", null, { "client:only": "vue", "client:component-hydration": "only", "client:component-path": "@/components/common/AuthGate.vue", "client:component-export": "default" }, { default: ($$result3) => renderTemplate`
        ${renderComponent($$result3, "AppSidebarLayout", null, { "client:only": "vue", currentPath, "client:component-hydration": "only", "client:component-path": "@/components/common/AppSidebarLayout.vue", "client:component-export": "default" }, { default: ($$result4) => renderTemplate`
          ${renderSlot($$result4, $$slots.default)}
        ` })}
      ` })}
    </div>
  </div>
` })}`;
}, "/Users/mac/Documents/trae_projects/sub2api/ai_jf/.codex_tmp/jfy-sync/album-web/src/layouts/ManagementLayout.astro", void 0);
export {
  $$ManagementLayout as $
};
