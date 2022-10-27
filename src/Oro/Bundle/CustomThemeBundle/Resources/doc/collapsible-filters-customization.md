# Collapse/Expand Filters

A collapsible/expandable block of filters is created in a `custom theme` of the product listing pages for **tablet devices**.

The old logic remains for **desktops** where filters are always displayed, and for **mobile** devices where filters are displayed in a full screen popup.

All changes are provided in this bundle.

The following is the list of the modified files:
<dl>
    <dt>CustomThemeBundle/.../FilterBundle/js/datagrid/frontend-collection-filters-manager.js</dt>
    <dd>
        <ol>
            <li>Added a possibility to enable/disable MultiselectWidget.</li>
            <li>Disabled FiltersStateView - this component saves the open/close filters state to a local storage.</li>
            <li> Updated RenderMode - a template for FiltersManager that is rendered depending on
                a renderMode parameter ('dropdown-mode' | 'collapse-mode' | 'toggle-mode').
            </li>
        </ol>
    </dd>
    <dt>CustomThemeBundle/.../FilterBundle/js/datagrid/plugins/frontend-filters-plugin.js</dt>
    <dd>
        <ol>
            <li>Include viewportManager.</li>
            <li>Now FullScreenFiltersAction is enabled on mobile-landscape (max-width: 662px;) or mobile.</li>
            <li>If a datagrid has the data-server-render attribute, and the screen size is bigger than mobile-landscape (max-width: 662px), you need to disable this plugin.</li>
        </ol>
    </dd>
    <dt>CustomThemeBundle/.../FilterBundle/scss/components/filters-box-collapse.scss</dt>
    <dd>
         <ol>
            <li>Styles for a filters-box-collapse block.</li>
         </ol>
    </dd>
    <dt>CustomThemeBundle/.../FilterBundle/scss/variables/filters-box-collapse-config.scss</dt>
    <dd>
         <ol>
            <li>Variables for a filters-box-collapse block.</li>
         </ol>
    </dd>
    <dt>CustomThemeBundle/.../FilterBundle/scss/components/filters.scss</dt>
    <dd>
         <ol>
            <li>New styles are added for filters in a collapse mode.</li>
         </ol>
    </dd>
    <dt>CustomThemeBundle/.../FilterBundle/scss/variables/filters-config.scss</dt>
    <dd>
         <ol>
            <li>Variables are provided to enable or disable styles in a collapse mode.</li>
         </ol>
    </dd>
    <dt>CustomThemeBundle/.../FilterBundle/scss/components/_filters-collapse-mode.scss</dt>
    <dd>
         <ol>
            <li>Styles for filters in a collapse mode.</li>
         </ol>
    </dd>
    <dt>CustomThemeBundle/.../FilterBundle/scss/variables/filters-collapse-mode-config.scss</dt>
    <dd>
         <ol>
            <li>Variables for filter styles in a collapse mode.</li>
         </ol>
    </dd>
    <dt>CustomThemeBundle/.../FilterBundle/templates/filters/filters-container.html</dt>
    <dd>
         <ol>
            <li>A new template for FilterManager that is rendered depending on a renderMode parameter.</li>
         </ol>
    </dd>
    <dt>CustomThemeBundle/Resources/views/layouts/custom/config/jsmodules.yml</dt>
    <dd>
         <ol>
            <li>New *.js files are included and orofilter/js/plugins/filters-toggle-plugin is overriden from a default theme.</li>
         </ol>
    </dd>
    <dt>CustomThemeBundle/../layouts/custom/oro_product_frontend_product_index/product_index.yml</dt>
    <dd>
         <ol>
            <li>A new data-layout="separate" attribute is set in the product_js_modules_config block to support `collapse` initializing.
            </li>
         </ol>
    </dd>
    <dt>CustomThemeBundle/../layouts/custom/oro_product_frontend_product_index/js_modules_config.html.twig</dt>
    <dd>
         <ol>
            <li>A frontend-collection-filters-manager is used from a custom theme.</li>
            <li>A multiselect widget is disabled.</li>
         </ol>
    </dd>
</dl>
