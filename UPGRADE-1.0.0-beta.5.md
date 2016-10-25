Upgrade from beta.4
===================

####General
- Added dependency on [fxpio/composer-asset-plugin](https://github.com/fxpio/composer-asset-plugin) composer plugin.
- All original third-party asset libraries were moved out from commerce and added to composer.json as bower-asset/npm-asset dependency.

ProductBundle
-------------
- Product images filters config removed from `app.yml`. 
These filters are now added dynamically based on `images.yml` config.
