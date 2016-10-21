# ORO Frontend Styles Architecture

* [Terminology](#user-content-theme-terminology)
* [Theme structure](#user-content-theme-structure)
* [Theme extending](#user-content-theme-extending)
* [Themes settings and useful recommendations](#user-content-themes-settings-and-useful-recommendation)

A mostly reasonable approach to develop and maintain CSS and [SASS](http://sass-lang.com/) in **ORO commerce** project.

## Terminology

ORO Commerce project consists of bundles. Each bundle of the project has own set of CSS related to particular bundle. 
Additionally ORO Commerce project has three themes: **blank**, **default**, **custom**. CSS for each theme is located in particular theme folder: *BundleName/Resources/public/theme_name/scss/*.

1. **blank** - skeleton theme. It has basic @mixins, @functions, variables, color palette and typography. Blank theme includes basic functionality without reference to design;*
2. **default** - expanded theme with own settings and dependencies. It is based on the **blank** theme;
3. **custom** - slightly modified **default** theme;


## Theme structure

Core styles are located in UIBundle: *UIBundle/Resources/public/blank/scss/*.
CSS structure has three folders: **components**, **settings**, **variables**.

1. **components** - folder for bundle components;
2. **settings** - folder for @mixins, @functions, settings, etc for particular theme
3. **variables** - folder for all configuration variables for particular bundle

Each bundle has its own **styles.scss**, that gathers all variables, settings, components styles.
To enable css for particular theme, you should add styles.scss file to **assets.yml** file in an appropriate bundle: *BundleName/Resources/views/layouts/theme_name/config/assets.yml*

Folders tree structure example:

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

Compiler collects all styles in one file for the theme.
All files should be sorted by priority: there are files with **settings folder** at the top, then **variables**, and in the end all **styles.scss**.

Example:

*application/commerce/web/css/layout/base/styles.css.scss*
```
@import "../bundles/oroui/blank/scss/**settings**/global-settings.scss";
@import "../bundles/oroui/blank/scss/**variables**/base-config.scss";
@import "../bundles/oroui/blank/scss/**variables**/page-container-config.scss";
@import "../bundles/oroui/blank/scss/**variables**/page-header-config.scss";
@import "../bundles/oroui/blank/scss/**variables**/page-content-config.scss";
@import "../bundles/oroui/blank/scss/**variables**/page-footer-config.scss";
@import "../bundles/oroui/blank/scss/**variables**/page-title-config.scss";
@import "../bundles/oroaccount/blank/scss/**styles.scss**";
```

This structure allows us to change styles for components on bundle level, on component level and just for particular theme.
The main idea of this approach not to override styles from parent theme in child theme. 
We just change settings and add additional CSS(SASS).

## Theme extending

In order to inherite one theme from another you should define parent theme in **theme.yml** file.<br>

For example: if you need to inherit default theme from blank do as follows:

*/theme.yml*		
```		
parent: blank		
```

It allows you to inherit all styles from parent theme and have access to all mixins, variables, etc from parent theme.

Let's look at an example using default theme.
In default theme we'd like to change global and form elements styles.
In corresponding bundles (FrontEndBundle, FormBundle) we create theme folders and some scss files.


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


Update and add new variables for this component

*input-config.scss*
``` scss
$input-padding: 10px 12px; // update the variable's value with blank theme
$input-font-size: 13px; // update the variable's value with blank theme
$input-offset: 5px; // new variable
```

Add missing styles for this component

*input.scss*
``` scss
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

In the main file for default theme we have:

*application/commerce/web/css/layout/default/styles.css.scss*

```
@import "../bundles/oroui/**blank**/scss/**settings**/global-settings.scss";

*// Update global setting for main styles*
@import "../bundles/orofrontend/**default**/scss/**settings**/global-settings.scss";

*// Update global setting  for FormBundle styles*
@import "../bundles/**oroform**/**default**/scss/**settings**/global-settings.scss";
@import "../bundles/oroui/**blank**/scss/**variables**/base-config.scss";
@import "../bundles/oroui/**blank**/scss/**variables**/page-container-config.scss";
@import "../bundles/oroui/**blank**/scss/**variables**/page-header-config.scss";
@import "../bundles/oroui/**blank**/scss/**variables**/page-content-config.scss";
@import "../bundles/oroui/**blank**/scss/**variables**/page-footer-config.scss";
@import "../bundles/oroui/**blank**/scss/**variables**/page-title-config.scss";

*// Update setting from global components*
@import "../bundles/orofrontend/**default**/scss/**variables**/page-content-config.scss"
@import "../bundles/orofrontend/**default**/scss/**variables**/page-footer-config.scss"
@import "../bundles/orofrontend/**default**/scss/**variables**/page-title-config.scss"

*// Update settings for input component*
@import "../bundles/oroform/**default**/scss/**variables**/input-config.scss"
@import "../bundles/oroaccount/**blank**/scss/**styles.scss**";
@import "../bundles/orofrontend/**default**/scss/**styles.scss**";
@import "../bundles/oroform/**default**/scss/**styles.scss**";
```

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

**PAY ATTENTION!!!**<br>
In default theme FormBundle goes first because there is a settings, that are not related to this bundle.**


### How to work with colors

To work with color, use **get-color()** function, which returns a color from a predefined color scheme.

Example:

```scss
.component {
    border-color: get-color('additional', 'light');
    color: get-color('primary', 'main');
}
```

If you need darker or lighter or more transparent color use native Sass functions: **darken()**, **lighten()**, **transparentize()**, etc

```scss
.component {
    border-color: darken(get-color('additional', 'light'));
    color: lighten(get-color('primary', 'main'));
    background-color: transparentize(get-color('primary', 'main'), .8);
}
```
