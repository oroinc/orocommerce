## FROM 1.5.0 to 1.6.0
* Changed minimum required php version to 7.1
* Relation between Category and Product has been changed in database. Join table has been removed. Please, make sure that you have fresh database backup before updating application.

## FROM 1.4.0 to 1.5.0

Full product reindexation has to be performed after upgrade!

## FROM 1.3.0 to 1.4.0
 
Format of sluggable urls cache was changed, added support of localized slugs. Cache regeneration is required after update. 

## FROM 1.0.0 to 1.1.0

* Minimum required `php` version has changed from **5.7** to **7.0**.
* [Fxpio/composer-asset-plugin](https://github.com/fxpio/composer-asset-plugin) dependency was updated to version **1.3**.
* Composer was updated to version **1.4**; use the following commands:

  ```
      composer self-update
      composer global require "fxp/composer-asset-plugin"
  ```

* To upgrade OroCommerce from **1.0** to **1.1** use the following command:

  ```bash
  php app/console oro:platform:update --env=prod --force
  ```

