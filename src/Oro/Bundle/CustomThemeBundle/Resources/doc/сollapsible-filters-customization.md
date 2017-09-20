# Collapse/Expand filters

In `custom theme` on product listing pages for **tablet devices** created collapsible/expandable block of filters according.
Also saved logic for **desktop** - filters always shown and **mobile** devices - filter opens on full screen popup.

All changes provide in this bundle.
List of modified files:
<dl>
    <dt>`CustomThemeBundle/.../FilterBundle/js/datagrid/frontend-collection-filters-manager.js`</dt>
    <dd>
         * Added possibility enabled/disabled `MultiselectWidget`.
         * Disabled FiltersStateView - this component save state open/close filters to Locale Storage.
         * Updated RenderMode - now `template for FiltersManager` rendered depending on
           the parameter renderMode (['dropdown-mode' | 'collapse-mode' | 'toggle-mode']).
    </dd>
    <dt>`CustomThemeBundle/.../FilterBundle/js/datagrid/plugins/frontend-filters-plugin.js`</dt>
    <dd>
         * Include `viewportManager`.
         * Now `FullScreenFiltersAction` enable on `mobile-landscape` (`max-width: 662px;`) and less page size.
         * If datagrid has attribute `data-server-render` and screen size more `mobile-landscape` (max-width: 662px) - disable this plugin.
    </dd>
    <dt>`CustomThemeBundle/.../FilterBundle/scss/components/filters-box-collapse.scss`</dt>
    <dd>
         Styles for `filters-box-collapse` block.
    </dd>
    <dt>`CustomThemeBundle/.../FilterBundle/scss/variables/filters-box-collapse-config.scss`</dt>
    <dd>
        Variables for `filters-box-collapse` block.
    </dd>
    <dt>`CustomThemeBundle/.../FilterBundle/scss/components/filters.scss`</dt>
    <dd>
        Add new styles for filters in collapse mode.
    </dd>
    <dt>`CustomThemeBundle/.../FilterBundle/scss/variables/filters-config.scss`</dt>
    <dd>
        Variables for use or not styles for collapse mode.
    </dd>
    <dt>`CustomThemeBundle/.../FilterBundle/scss/components/_filters-collapse-mode.scss`</dt>
    <dd>
        Styles for filnters in collapse mode.
    </dd>
    <dt>`CustomThemeBundle/.../FilterBundle/scss/variables/filters-collapse-mode-config.scss`</dt>
    <dd>
        Variables for filters styles in collapse mode.
    </dd>
    <dt>`CustomThemeBundle/.../FilterBundle/templates/filters/filters-container.html`</dt>
    <dd>
        New template for `FilterManager` that rendered depending on the parameter renderMode.
    </dd>
    <dt>`CustomThemeBundle/Resources/views/layouts/custom/config/requirejs.yml`</dt>
    <dd>
        Included new `*.js` files and override `orofilter/js/plugins/filters-toggle-plugin` from **default** theme.
    </dd>
    <dt>`/CustomThemeBundle/../layouts/custom/oro_product_frontend_product_index/product_index.yml`</dt>
    <dd>
        For `product_require_js_config` block added new attribute `data-layout="separate"` for possibility initialize `collapse-widget`.
    </dd>
    <dt>`/CustomThemeBundle/../layouts/custom/oro_product_frontend_product_index/require_js_config.html.twig`</dt>
    <dd>
        * Used `frontend-collection-filters-manager` from **custom** theme.
        * Disable Multiselect Widget.
    </dd>
</dl>
