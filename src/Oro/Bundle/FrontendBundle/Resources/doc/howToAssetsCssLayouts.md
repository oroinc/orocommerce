# How to organize styles assets

Files structure with styles should be next:
```
MyBundle/
    Resources/
        public/
            my-theme/
                scss/
                    components/
                        block1/
                        block2/
                    variables/
                        block1-config/
                        block2-config/
                    styles.scss
```

All styles should be placed in ```components``` folder with the same file name as block.
For example:
```
components/block1/block1.scss
components/block2/block2.scss
```

To add this blocks to resulting css file you should include them in:
```
MyBundle/Resources/public/my-theme/scss/styles.scss
```

All block variables should be placed in ```variables``` folder.
For example:
```
variables/block1-config/block1-config.scss
variables/block2-config/block2-config.scss
```

To include this configs in resulting css file you should add them
to ```assets.yml``` file witch is located in:
```
MyBundle/Resources/views/layouts/my-theme/config/
```

For theme customization use blocks configs, for example:
```
styles:
    inputs:
        - 'bundles/mybundle/my-custom-theme/scss/variables/block1-config.scss'
        - 'bundles/mybundle/my-custom-theme/scss/styles.scss'
    output: 'css/layout/default/styles.css'
```
