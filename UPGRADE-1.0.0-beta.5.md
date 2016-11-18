Upgrade from beta.4
===================

####General
- Added dependency to [fxpio/composer-asset-plugin](https://github.com/fxpio/composer-asset-plugin) сomposer plugin.
- All original third-party asset libraries were moved out from commerce and added to composer.json as bower-asset/npm-asset dependency.

AlternativeCheckoutBundle
-------------------------
- Alternative checkout workflow now disabled by default.
- Removed precondition `'@assert_account': 4` from Alternative checkout workflow configuration.
- Removed class `Oro\Bundle\AlternativeCheckoutBundle\Condition\AssertAccount`.
- removed service `oro_alternativecheckout.workflow_expression.user_in_group`.
- Alternative checkout workflow will be enabled after loading demo data. Also, in demo data to it configuration added
Scope configuration `account: 4`.

ProductBundle
-------------
- Product images filters config was removed from `app.yml`. 
These filters are now added dynamically based on `images.yml` config.
