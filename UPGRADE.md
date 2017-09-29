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

