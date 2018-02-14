# Collapse/Expand filters

A collapsible/expandable block of filters was created in the `custom theme` of the product listing pages for **tablet devices**.

The old logic remains for **desktop** where filters are always shown, and for **mobile** devices where filters open on a full screen popup.

All changes are provided in this bundle.

The following is the list of the modified files:
<dl>
    <dt>CustomThemeBundle/.../FilterBundle/js/datagrid/frontend-collection-filters-manager.js</dt>
    <dd>
        <ol>
            <li>Added possibility enabled/disabled MultiselectWidget.</li>
            <li>Disabled FiltersStateView - this component saves the open/close filters state to Local Storage.</li>
            <li> Updated RenderMode - now this is the template for FiltersManager rendered depending on
                the parameter renderMode ('dropdown-mode' | 'collapse-mode' | 'toggle-mode').
            </li>
        </ol>
    </dd>
    <dt>CustomThemeBundle/.../FilterBundle/js/datagrid/plugins/frontend-filters-plugin.js</dt>
    <dd>
        <ol>
            <li>Include viewportManager.</li>
            <li>Now FullScreenFiltersAction is enabled on mobile-landscape (max-width: 662px;) or mobile.</li>
            <li>If datagrid has the data-server-render attribute and the screen size bigger than mobile-landscape (max-width: 662px) - disable this plugin.</li>
        </ol>
    </dd>
    <dt>CustomThemeBundle/.../FilterBundle/scss/components/filters-box-collapse.scss</dt>
    <dd>
         <ol>
            <li>Styles for filters-box-collapse block.</li>
         </ol>
    </dd>
    <dt>CustomThemeBundle/.../FilterBundle/scss/variables/filters-box-collapse-config.scss</dt>
    <dd>
         <ol>
            <li>Variables for filters-box-collapse block.</li>
         </ol>
    </dd>
    <dt>CustomThemeBundle/.../FilterBundle/scss/components/filters.scss</dt>
    <dd>
         <ol>
            <li>Add new styles for filters in collapse mode.</li>
         </ol>
    </dd>
    <dt>CustomThemeBundle/.../FilterBundle/scss/variables/filters-config.scss</dt>
    <dd>
         <ol>
            <li>Variables are for enabling or disabling styles for collapse mode.</li>
         </ol>
    </dd>
    <dt>CustomThemeBundle/.../FilterBundle/scss/components/_filters-collapse-mode.scss</dt>
    <dd>
         <ol>
            <li>Styles for filters in collapse mode.</li>
         </ol>
    </dd>
    <dt>CustomThemeBundle/.../FilterBundle/scss/variables/filters-collapse-mode-config.scss</dt>
    <dd>
         <ol>
            <li>Variables for filter styles in collapse mode.</li>
         </ol>
    </dd>
    <dt>CustomThemeBundle/.../FilterBundle/templates/filters/filters-container.html</dt>
    <dd>
         <ol>
            <li>New template for FilterManager that is rendered depending on the renderMode parameter.</li>
         </ol>
    </dd>
    <dt>CustomThemeBundle/Resources/views/layouts/custom/config/requirejs.yml</dt>
    <dd>
         <ol>
            <li>Included new *.js files and overrode orofilter/js/plugins/filters-toggle-plugin from default theme.</li>
         </ol>
    </dd>
    <dt>CustomThemeBundle/../layouts/custom/oro_product_frontend_product_index/product_index.yml</dt>
    <dd>
         <ol>
            <li>In product_require_js_config block set new attribute data-layout="separate"
                for possibility initialize `collapse-widget`.
            </li>
         </ol>
    </dd>
    <dt>CustomThemeBundle/../layouts/custom/oro_product_frontend_product_index/require_js_config.html.twig</dt>
    <dd>
         <ol>
            <li>Used frontend-collection-filters-manager from custom theme.</li>
            <li>Disable Multiselect Widget.</li>
         </ol>
    </dd>
</dl>
