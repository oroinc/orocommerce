# ORO Frontend Styles Architecture

* [Theme structure](#user-content-theme-structure)
* [Extend theme](#user-content-extend-theme)
* [Themes settings and useful recommendations](#user-content-themes-settings-and-useful-recommendation)

Overview how to customize, develop and supported styles in **ORO commerce** project.
In the project selected [SASS](http://sass-lang.com/) preprocessor.
All styles are divided into bundles and themes and placed in *BundleName/Resources/public/theme_name/scss/*.
In project has three themes: **blank**, **default**, **custom**.

1. **blank** - the main theme in project there are basic @mixins, @functions, variables, color palette and typography for the site.
Blank theme includes basic functionality without reference to the design;*
2. **default** - expanded theme with their settings and dependencies based on the **blank** theme;
3. **custom** - slightly modified **default** theme;

In order to inherit the theme should be put in theme.yml attribute: parent: theme_name.
For example default theme inherit blank:
*BundleName/Resources/views/layouts/default/theme.yml*

*/theme.ym*
```yml
parent: blank
```

## Theme structure

Core styles placed in UIBundle(*UIBundle/Resources/public/blank/scss/*).
For styles we have three main folders: **components**, **settings**, **variables**.

1. **components** - folder for bundle components;
2. **settings** - folder settings for bundle or theme styles, here we pull @mixins, @functions, common settings etc.
3. **variables** - folder for configs bundle components

Each bundle has its own **styles.scss**.
In **styles.scss** include only components and additional styles, others connect *BundleName/Resources/views/layouts/theme_name/config/assets.yml*
This is due to the assembly of styles.

Example:

```
components/
    page-container.scss
    page-content.scss
    page-footer.scss
    page-header.scss
    page-title.scss
settings/
    global-settings.scss
variables/
    page-container-config.scss
    page-content-config.scss
    page-footer-config.scss
    page-header-config.scss
    page-title-config.scss
styles.scss
```

*/styles.scss*
```scss
@import 'components/page-container';
@import 'components/page-header';
@import 'components/page-content';
@import 'components/page-footer';
@import 'components/page-title';
```

*/assets.yml*
```yml
styles:
    inputs:
        - 'bundles/oroui/blank/scss/settings/global-settings.scss'
        - 'bundles/oroui/blank/scss/variables/base-config.scss'
        - 'bundles/oroui/blank/scss/variables/page-container-config.scss'
        - 'bundles/oroui/blank/scss/variables/page-header-config.scss'
        - 'bundles/oroui/blank/scss/variables/page-content-config.scss'
        - 'bundles/oroui/blank/scss/variables/page-footer-config.scss'
        - 'bundles/oroui/blank/scss/variables/page-title-config.scss'
        - 'bundles/oroui/blank/scss/styles.scss'
    filters: ['cssrewrite', 'cssmin']
    output: 'css/layout/blank/styles.css'
```

Assetic collects all styles in one file for the theme.
Sort by priority, at the top are files with **settings folder**, then the **variables**, and in the end all **styles.scss**, and then compile in css.

Example:

*application/commerce/web/css/layout/base/styles.css.scss*

@import "../bundles/oroui/blank/scss/**settings**/global-settings.scss";
@import "../bundles/oroui/blank/scss/**variables**/base-config.scss";
@import "../bundles/oroui/blank/scss/**variables**/page-container-config.scss";
@import "../bundles/oroui/blank/scss/**variables**/page-header-config.scss";
@import "../bundles/oroui/blank/scss/**variables**/page-content-config.scss";
@import "../bundles/oroui/blank/scss/**variables**/page-footer-config.scss";
@import "../bundles/oroui/blank/scss/**variables**/page-title-config.scss";
@import "../bundles/oroaccount/blank/scss/**styles.scss**";

This is done so that we can change styles for components in the bundle level, elements and in a child theme.
So we will not have to interrupt the child theme styles from parent theme.
We change only settings and appends styles that are missing.

## Extend theme

If you put a flag in theme.yml parent: theme_name, you get access to parent styles.
That is, in the main file added imports with the inherited themes.

Consider the example in default theme.
In default theme we want change global and form elements styles.
in the corresponding bundles(FrontEndBundle, FormBundle) we create a folder (folder name - theme name) after that updates styles.
In FrontEndBundle we want changes same settings and in FormBundle updated styles.


*FrontEndBundle/Resources/public/default/scss/*
```
settings/
    global-settings.scss
variables/
    page-content-config.scss
    page-footer-config.scss
    page-title-config.scss
```
*FrontEndBundle/Resources/views/layouts/default/config/*
```
assets.yml/
    inputs:
        - 'bundles/orofrontend/default/scss/settings/global-settings.scss'

        - 'bundles/orofrontend/default/scss/variables/page-content-config.scss'
        - 'bundles/orofrontend/default/scss/variables/page-footer-config.scss'
        - 'bundles/orofrontend/default/scss/variables/page-title-config.scss'
        - 'bundles/orofrontend/default/scss/styles.scss'
    filters: ['cssrewrite', 'cssmin']
    output: 'css/layout/default/styles.css'
```

*FormBundle/Resources/public/default/scss/*

```
components/
    input.scss
settings/
    global-settings.scss
variables/
    input-config.scss
styles.scss
```

*input-config.scss*
``` scss
// Update and added new variables for this component

$input-padding: 10px 12px; // update the variable's value with blank theme
$input-font-size: 13px; // update the variable's value with blank theme
$input-offset: 5px; // new variable
```

*input.scss*
``` scss
// Added missing styles for this component

.input {
    margin: $input-offset;

    @include placeholder {
        color: get-color('additional', 'middle');
    }
}
```

*styles.scss*
``` scss
    @import 'components/input';
```

*FormBundle/Resources/views/layouts/default/config/*
```
assets.yml/
    inputs:
        - 'bundles/oroform/default/scss/settings/global-settings.scss'
        - 'bundles/oroform/default/scss/variables/input-config.scss'
        - 'bundles/oroform/default/scss/styles.scss'
    filters: ['cssrewrite', 'cssmin']
    output: 'css/layout/default/styles.css'
```

In the main file for default theme we see:

*application/commerce/web/css/layout/default/styles.css.scss*


@import "../bundles/oroui/**blank**/scss/**settings**/global-settings.scss";<br>
*// Update global setting for main styles*<br>
@import "../bundles/orofrontend/**default**/scss/**settings**/global-settings.scss";<br>
*// Update global setting  for FormBundle styles*<br>
@import "../bundles/**oroform**/**default**/scss/**settings**/global-settings.scss";<br>
@import "../bundles/oroui/**blank**/scss/**variables**/base-config.scss";</span><br>
@import "../bundles/oroui/**blank**/scss/**variables**/page-container-config.scss";<br>
@import "../bundles/oroui/**blank**/scss/**variables**/page-header-config.scss";<br>
@import "../bundles/oroui/**blank**/scss/**variables**/page-content-config.scss";<br>
@import "../bundles/oroui/**blank**/scss/**variables**/page-footer-config.scss";<br>
@import "../bundles/oroui/**blank**/scss/**variables**/page-title-config.scss";<br>
*// Update setting from global components*<br>
@import "../bundles/orofrontend/**default**/scss/**variables**/page-content-config.scss"<br>
@import "../bundles/orofrontend/**default**/scss/**variables**/page-footer-config.scss"<br>
@import "../bundles/orofrontend/**default**/scss/**variables**/page-title-config.scss"<br>
*// Update settings for input component*<br>
@import "../bundles/oroform/**default**/scss/**variables**/input-config.scss"<br>
@import "../bundles/oroaccount/**blank**/scss/**styles.scss**";<br>
@import "../bundles/orofrontend/**default**/scss/**styles.scss**";<br>
@import "../bundles/oroform/**default**/scss/**styles.scss**";<br>

## Themes settings and useful recommendation

1. Main styles for **blank theme**: package/platform/src/Oro/Bundle/FormBundle/Resources/public/blank/scss/
    * mixins: package/platform/src/Oro/Bundle/UIBundle/Resources/public/blank/scss/settings/partials/mixins.scss
    * variables: package/platform/src/Oro/Bundle/UIBundle/Resources/public/blank/scss/settings/partials/variables.scss
    * typography: package/platform/src/Oro/Bundle/UIBundle/Resources/public/blank/scss/settings/partials/typography
    * color pallet: package/platform/src/Oro/Bundle/UIBundle/Resources/public/blank/scss/settings/partials/color-palette/_colors.scss

2. Form styles for **blank theme**: package/platform/src/Oro/Bundle/FormBundle/Resources/public/blank/scss

3. Main styles for **default theme** package/commerce/src/Oro/Bundle/FrontendBundle/Resources/public/default/scss
    * mixins: package/commerce/src/Oro/Bundle/FrontendBundle/Resources/public/default/scss/settings/_mixins.scss
    * variables: package/platform/src/Oro/Bundle/FormBundle/Resources/public/default/scss/settings/_variables.scss
    * typography: package/platform/src/Oro/Bundle/FormBundle/Resources/public/default/scss/settings/_typography.scss
    * color pallet: package/platform/src/Oro/Bundle/FormBundle/Resources/public/default/scss/settings/_colors.scss

4. Form styles **default theme**: package/platform/src/Oro/Bundle/FormBundle/Resources/public/default/scss

**PAY ATTENTION !!<br>
In default theme FormBundle included a first because there is a setting not related to this bundle.**


### Work with colors

To work with color, use the function **get-color()**, which returns a color from a predetermined color scheme.

Example:

```scss
.component {
    border-color: get-color('additional', 'light');
    color: get-color('primary', 'main');
}
```

If you need darker, lighter or more transparent color use native Sass functions: **darken()**, **lighten()**, **transparentize()**

```scss
.component {
    border-color: darken(get-color('additional', 'light'));
    color: lighten(get-color('primary', 'main'));
    background-color: transparentize(get-color('primary', 'main'), .8);
}
```
