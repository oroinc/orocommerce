# How to organize styles assets

Files structure with styles should be next:
```
MyBundle/
    Resources/
        public/
            my-theme/
                scss/
                    components/
                        input/input.scss
                        button/button.scss
                    settings/
                        global-settings.scss
                    variables/
                        input-config/input-config.scss
                        button-config/button-config.scss
                    styles.scss
```

All styles should be placed in ```components``` folder with the same file name as block name.
For example ```components/input/input.scss```:
```scss
.input {
    display: inline-block;
    padding: $input-padding;
    font-size: $font-size;
    font-family: $input-font-family;
    line-height: $input-line-height;
    border: $border;
    color: $input-color;    
}
```
And for example ```components/button/button.scss```:
```scss
.button {
    display: inline-block;
    padding: $button-padding;
    font-size: $font-size;
    font-family: $button-font-family;
    line-height: $button-line-height;
    border: $border;
    color: $button-color;
}
```

Global settings should contain global variables for blocks.
Example ```global-settings.scss```:
```scss
$font-size: 12px;
$font-family: 'Thamoma';
$line-height: 1.1;
```

All block variables should be placed in ```variables``` folder.
Files should contain configs for blocks what can be reconfigures in ```my-custom-theme```.
For example ```input-config.scss```:
```scss
$input-padding: 8px 9px !default;
$input-font-size: $font-size !default;
$input-font-family: $font-family !default;
$input-line-height: $line-height !default;
$input-color: blue !default;
```
And for example ```button-config.scss```:
```scss
$button-padding: 18px 9px !default;
$button-font-size: $font-size !default;
$button-font-family: $font-family !default;
$button-line-height: $line-height !default;
$button-color: yellow !default;
```

To add blocks to resulting ```styles.css``` file you should include them into ```styles.scss```:
```
@import: './components/input/input';
@import: './components/button/button';
```

To include configs in resulting ```styles.css``` file you should add them
to ```assets.yml``` file witch is located in ```MyBundle/Resources/views/layouts/my-theme/config/```:
```
styles:
    inputs:
        - 'bundles/mybundle/my-theme/scss/settings/global-settings.scss'
        - 'bundles/mybundle/my-theme/scss/variables/button-config.scss'
        - 'bundles/mybundle/my-theme/scss/variables/input-config.scss'
        - 'bundles/mybundle/my-theme/scss/styles.scss'
    output: 'css/layout/my-theme/styles.css'
```

The resulting ```styles.css``` file will be next:
```css
.input {
    display: inline-block;
    padding: 8px 9px;
    font-size: 12px;
    font-family: 'Thamoma';
    line-height: 1.1;
    color: blue;
}
.button {
    display: inline-block;
    padding: 18px 9px;
    font-size: 12px;
    font-family: 'Thamoma';
    line-height: 1.1;
    color: yellow;
}

```

## Theme customization by theme extending

In custom themes we have the opportunity to change globals and settings for a particular component by changing the value of the variable under the same name. And can also make own configs for new or existing components in the exended theme.

We use styles from ```my-theme``` and configs from ```my-custom-theme```.
For example ```components/input/input.scss```:
```scss
.button {
    border: $input-border;
    &--full {
        width:  100%;
    }    
}
```
And for example ```global-settings.scss```
```scss
$font-size: 14px;
$font-family: 'Arial';

```
And for example ```input-config.scss```:
```scss
$input-border: 1px solid red;
$input-color: purple;
```

```Assets.yml``` for ```my-custom-theme``` should be next:
```
styles:
    inputs:
        - 'bundles/mybundle/my-custom-theme/scss/settings/global-settings.scss'
        - 'bundles/mybundle/my-custom-theme/scss/variables/input-config.scss'
        - 'bundles/mybundle/my-custom-theme/scss/styles.scss'
    output: 'css/layout/my-theme/styles.css'
```

The resulting ```styles.css``` file will be next:
```css
.input {
    display: inline-block;
    padding: 8px 9px;
    font-size: 14px;
    font-family: 'Arial';
    line-height: 1.1;
    color: purple;
    border: 1px solid red;
}
.button {
    display: inline-block;
    padding: 18px 9px;
    font-size: 14px;
    font-family: 'Arial';
    line-height: 1.1;
    color: yellow;
}
.button--full {
    width: 100%
}
```

Before dumps all files collects into one for each theme.
For ```my-theme``` in file ```application/commerce/web/css/layout/my-theme/styles.css.scss```:

```css
@import 'my-theme/settings/global-settings';
@import 'my-theme/variables/input-config';
@import 'my-theme/variables/button-config';
@import 'my-theme/styles';
```

For ```my-custom-theme``` in file ```application/commerce/web/css/layout/my-custom-theme/styles.css.scss```:
```css
@import 'my-theme/settings/global-settings';
@import 'my-custom-theme/settings/global-settings';
@import 'my-theme/variables/input-config';
@import 'my-theme/variables/button-config';
@import 'my-custom-theme/variables/input-config';
@import 'my-custom-theme/variables/button-config';
@import 'my-theme/styles';
@import 'my-custom-theme/styles';
```
