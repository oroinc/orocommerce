UPGRADE FROM 1.0 to 1.1
=======================

General
-------
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

The following sections provide the detailed summary of the changes in version **1.1** compared to version **1.0** per bundle.

AlternativeCheckoutBundle
-------------------------

The [`PaymentTermViewProvider::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/AlternativeCheckoutBundle/Layout/DataProvider/PaymentTermViewProvider.php "Oro\Bundle\AlternativeCheckoutBundle\Layout\DataProvider\PaymentTermViewProvider") method has been updated and now uses different data types in arguments:
* Pass [`PaymentMethodViewProviderInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/View/PaymentMethodViewProviderInterface.php "Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface") instead of [`PaymentMethodViewRegistry`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Method/View/PaymentMethodViewRegistry.php "Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewRegistry") in the first argument.
* Pass [`PaymentMethodProviderInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/Provider/PaymentMethodProviderInterface.php "Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface") instead of [`PaymentMethodRegistry`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Method/PaymentMethodRegistry.php "Oro\Bundle\PaymentBundle\Method\PaymentMethodRegistry") in the second argument.

CMSBundle
---------
* The following methods were moved from the [`CmsPageVariantType`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/CMSBundle/Form/Type/CmsPageVariantType.php "Oro\Bundle\CMSBundle\Form\Type\CmsPageVariantType") class to the [`PageVariantTypeExtension`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebCatalogBundle/Form/Extension/PageVariantTypeExtension.php "Oro\Bundle\WebCatalogBundle\Form\Extension\PageVariantTypeExtension") class:
   - `__construct`
   - `configureOptions`

CatalogBundle
-------------
* The following classes were removed:
    - [`CategoryController`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/CatalogBundle/Controller/Api/Rest/CategoryController.php "Oro\Bundle\CatalogBundle\Controller\Api\Rest\CategoryController")
    - [`ProductController`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/CatalogBundle/Controller/Frontend/ProductController.php "Oro\Bundle\CatalogBundle\Controller\Frontend\ProductController")
* The following methods were removed from the [`CategoryContentVariantIndexListener`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/CatalogBundle/EventListener/CategoryContentVariantIndexListener.php "Oro\Bundle\CatalogBundle\EventListener\CategoryContentVariantIndexListener") class:
   - `addCategory`
   - `collectCategories`
   - `onFormAfterFlush`
* The following methods were moved from the [`CategoryPageVariantType`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/CatalogBundle/Form/Type/CategoryPageVariantType.php "Oro\Bundle\CatalogBundle\Form\Type\CategoryPageVariantType") class to the [`PageVariantTypeExtension`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebCatalogBundle/Form/Extension/PageVariantTypeExtension.php "Oro\Bundle\WebCatalogBundle\Form\Extension\PageVariantTypeExtension") class:
   - `__construct`
   - `configureOptions`
* The [`CategoryExtension::setContainer`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/CatalogBundle/Twig/CategoryExtension.php "Oro\Bundle\CatalogBundle\Twig\CategoryExtension") method was removed.
* The [`CategoryController::createAction`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CatalogBundle/Controller/CategoryController.php "Oro\Bundle\CatalogBundle\Controller\CategoryController") method was updated to expect a `Symfony\Component\HttpFoundation\Request` instead of `mixed` in the second argument.
* The [`CategoryController::update`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CatalogBundle/Controller/CategoryController.php "Oro\Bundle\CatalogBundle\Controller\CategoryController") method was updated so you can pass a `Symfony\Component\HttpFoundation\Request` in the second argument.
* The [`CategoryController::updateAction`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CatalogBundle/Controller/CategoryController.php "Oro\Bundle\CatalogBundle\Controller\CategoryController") method was updated so you can pass a `Symfony\Component\HttpFoundation\Request` in the second argument.
* The [`CategoryContentVariantIndexListener::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CatalogBundle/EventListener/CategoryContentVariantIndexListener.php "Oro\Bundle\CatalogBundle\EventListener\CategoryContentVariantIndexListener") method was updated so you can:
  - Pass a [`FieldUpdatesChecker`](https://github.com/orocrm/platform/tree/2.1.0/src/Oro/Component/DoctrineUtils/ORM/FieldUpdatesChecker.php "Oro\Component\DoctrineUtils\ORM\FieldUpdatesChecker") in the third argument.
  - Pass [`WebCatalogUsageProviderInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Component/WebCatalog/Provider/WebCatalogUsageProviderInterface.php "Oro\Component\WebCatalog\Provider\WebCatalogUsageProviderInterface") in the fourth argument.
* The [`FeaturedCategoriesProvider::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CatalogBundle/Layout/DataProvider/FeaturedCategoriesProvider.php "Oro\Bundle\CatalogBundle\Layout\DataProvider\FeaturedCategoriesProvider") method was updated so you can pass a `Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface` in the second argument.
* The [`CategoryFallbackProvider::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CatalogBundle/Fallback/Provider/CategoryFallbackProvider.php "Oro\Bundle\CatalogBundle\Fallback\Provider\CategoryFallbackProvider") method was updated so you can pass a [`SystemConfigFallbackProvider`](https://github.com/orocrm/platform/tree/2.1.0/src/Oro/Bundle/EntityBundle/Fallback/Provider/SystemConfigFallbackProvider.php "Oro\Bundle\EntityBundle\Fallback\Provider\SystemConfigFallbackProvider") in the second argument.
* The [`CategoryExtension`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CatalogBundle/Twig/CategoryExtension.php "Oro\Bundle\CatalogBundle\Twig\CategoryExtension") class changed:
    - The construction signature of was changed and the constructor was updated with the new `ContainerInterface $container` parameter.
    - The `setContainer` method was removed.
* The [`CategoryPageVariantType::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CatalogBundle/Form/Type/CategoryPageVariantType.php "Oro\Bundle\CatalogBundle\Form\Type\CategoryPageVariantType") was removed and the logic moved to [`PageVariantTypeExtension`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebCatalogBundle/Form/Extension/PageVariantTypeExtension.php "Oro\Bundle\WebCatalogBundle\Form\Extension\PageVariantTypeExtension")
* The [`CategoryBreadcrumbProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CatalogBundle/Layout/DataProvider/CategoryBreadcrumbProvider.php "Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoryBreadcrumbProvider") was added as a data provider for breadcrumbs.
* The  [`CategoryProvider::getBreadcrumbs`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CatalogBundle/Layout/DataProvider/CategoryProvider.php "Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoryProvider") method  is deprecated. Please use
[`CategoryBreadcrumbProvider::getItems()`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CatalogBundle/Layout/DataProvider/CategoryBreadcrumbProvider.php "Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoryBreadcrumbProvider") instead.


CheckoutBundle
--------------
* The [`AjaxCheckoutController::getShippingCost`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/CheckoutBundle/Controller/Frontend/AjaxCheckoutController.php "Oro\Bundle\CheckoutBundle\Controller\Frontend\AjaxCheckoutController") method was removed.
* The following methods were removed from the [`CheckoutGridListener`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/CheckoutBundle/Datagrid/CheckoutGridListener.php "Oro\Bundle\CheckoutBundle\Datagrid\CheckoutGridListener") class:
   - `buildItemsCountColumn`
   - `buildStartedFromColumn`
   - `buildTotalColumn`
* The [`CheckoutController::handleTransition`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CheckoutBundle/Controller/Frontend/CheckoutController.php "Oro\Bundle\CheckoutBundle\Controller\Frontend\CheckoutController") method was updated so you can pass [`WorkflowItem`](https://github.com/orocrm/platform/tree/2.1.0/src/Oro/Bundle/WorkflowBundle/Entity/WorkflowItem.php "Oro\Bundle\WorkflowBundle\Entity\WorkflowItem") in the first argument instead of [`CheckoutInterface`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/CheckoutBundle/Entity/CheckoutInterface.php "Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface").
* The [`CheckoutRepository::findCheckoutByCustomerUserAndSourceCriteria`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CheckoutBundle/Entity/Repository/CheckoutRepository.php "Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository") method was updated so you can pass a `WorkflowName` string in the third argument.
* The [`CheckoutTotalsProvider::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CheckoutBundle/Provider/CheckoutTotalsProvider.php "Oro\Bundle\CheckoutBundle\Provider\CheckoutTotalsProvider") method was updated so you can pass a [`CheckoutShippingMethodsProviderInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CheckoutBundle/Shipping/Method/CheckoutShippingMethodsProviderInterface.php "Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface") in the fourth argument.
* The [`PriceCheckoutShippingMethodsProviderChainElement::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CheckoutBundle/Shipping/Method/Chain/Member/Price/PriceCheckoutShippingMethodsProviderChainElement.php "Oro\Bundle\CheckoutBundle\Shipping\Method\Chain\Member\Price\PriceCheckoutShippingMethodsProviderChainElement") method was updated so you can pass a [`ShippingPriceProviderInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ShippingBundle/Provider/Price/ShippingPriceProviderInterface.php "Oro\Bundle\ShippingBundle\Provider\Price\ShippingPriceProviderInterface") in the first argument instead of [`ShippingPriceProvider`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/Provider/ShippingPriceProvider.php "Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider").
* The [`LineItemsExtension::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CheckoutBundle/Twig/LineItemsExtension.php "Oro\Bundle\CheckoutBundle\Twig\LineItemsExtension") method was updated so you can pass a `Symfony\Component\DependencyInjection\ContainerInterface` in the first argument of the method instead of [`TotalProcessorProvider`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PricingBundle/SubtotalProcessor/TotalProcessorProvider.php "Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider").
* The public [`CheckoutRepository::findCheckoutByCustomerUserAndSourceCriteria()`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CheckoutBundle/Entity/Repository/CheckoutRepository.php "Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository") method was updated so you can pass a `string $workflowName` in the third argument.
* The [`LineItemsExtension`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CheckoutBundle/Twig/LineItemsExtension.php "Oro\Bundle\CheckoutBundle\Twig\LineItemsExtension") class was updated with the following changes:
    - The construction signature of was changed and the constructor accepts only one `ContainerInterface $container` parameter.
    - The following properties were removed:
      + `protected $totalsProvider`
      + `protected $lineItemSubtotalProvider`

CommerceMenuBundle
------------------
* The [`CommerceMenuBundle`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/CommerceMenuBundle "Oro\Bundle\CommerceMenuBundle") moved from the [`OroCommerce`](https://github.com/orocommerce/orocommerce) package into the [`OroCRM Customer Portal`](#"https://github.com/orocrm/customer-portal") package.
* The [`MenuExtension`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CommerceMenuBundle/Twig/MenuExtension.php "Oro\Bundle\CommerceMenuBundle\Twig\MenuExtension") class was updated with the following change:
    - The construction signature of was changed and the constructor accepts only one `ContainerInterface $container` parameter.

CustomerBundle
--------------
* The [`CustomerBundle`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/CustomerBundle "Oro\Bundle\CustomerBundle") moved from the [`OroCommerce`](https://github.com/orocommerce/orocommerce) package into the [`OroCRM Customer Portal`](https://github.com/orocrm/customer-portal) package.
* The [`FrontendOwnerTreeProvider::_construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CustomerBundle/OwnerFrontendOwnerTreeProvider.php "Oro\Bundle\CustomerBundle\Owner\FrontendOwnerTreeProvider") method was added with the following signature:

  ```
  __construct(
        ManagerRegistry $doctrine,
        DatabaseChecker $databaseChecker,
        CacheProvider $cache,
        MetadataProviderInterface $ownershipMetadataProvider,
        TokenStorageInterface $tokenStorage
    )
  ```
* The [`CustomerExtension`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CustomerBundle/Twig/CustomerExtension.php "Oro\Bundle\CustomerBundle\Twig\CustomerExtension") class was updated with the following changes:
  - The construction signature of was changed and the constructor accepts only one `ContainerInterface $container` parameter.
  - The following property was removed:
    + `protected $securityProvider`
* The `commerce` configurable permission was added for View and Edit pages of the Customer Role in backend area (aka management console) (see [configurable-permissions.md](../platform/src/Oro/Bundle/SecurityBundle/Resources/doc/configurable-permissions.md) for details.
* The `commerce_frontend` configurable permission was added for View and Edit pages of the Customer Role in frontend area (aka front store)(see [configurable-permissions.md](../platform/src/Oro/Bundle/SecurityBundle/Resources/doc/configurable-permissions.md) for details.

FlatRateBundle
--------------
* [`FlatRateBundle`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/FlatRateBundle/ "Oro\Bundle\FlatRateBundle") was renamed to [`FlatRateShippingBundle`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/FlatRateShippingBundle/ "Oro\Bundle\FlatRateShippingBundle") 

FrontendBundle
--------------
* The [`FrontendBundle`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/FrontendBundle "Oro\Bundle\FrontendBundle") moved from the [`OroCommerce`](https://github.com/orocommerce/orocommerce) package into the [`OroCRM Customer Portal`](https://github.com/orocrm/customer-portal) package.

FrontendTestFrameworkBundle
---------------------------
* The [`FrontendWebTestCase::tearDown`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/FrontendTestFrameworkBundle/Test/FrontendWebTestCase.php "Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase") method was renamed to [`FrontendWebTestCase::afterFrontendTest`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/FrontendTestFrameworkBundle/Test/FrontendWebTestCase.php "Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase").

InventoryBundle
---------------
* In the `/api/inventorylevels` REST API resource, the `productUnitPrecision.unit.code` filter was marked as deprecated. The `productUnitPrecision.unit.id` filter should be used instead.
* The [`InventoryLevelController`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/InventoryBundle/Controller/Api/Rest/InventoryLevelController.php "Oro\Bundle\InventoryBundle\Controller\Api\Rest\InventoryLevelController") class was removed.
* The [`QuantityToOrderConditionListener::isNotCorrectConditionContextForStart`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/InventoryBundle/EventListener/QuantityToOrderConditionListener.php "Oro\Bundle\InventoryBundle\EventListener\QuantityToOrderConditionListener") method was removed.
* The [`InventoryLevelReader::setSourceQueryBuilder`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/InventoryBundle/ImportExport/Reader/InventoryLevelReader.php "Oro\Bundle\InventoryBundle\ImportExport\Reader\InventoryLevelReader") method was removed.
* The [`InventoryLevelReader::setSourceEntityName`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/InventoryBundle/ImportExport/Reader/InventoryLevelReader.php "Oro\Bundle\InventoryBundle\ImportExport\Reader\InventoryLevelReader") was updated so you could pass an array of IDs in the third argument.

MoneyOrderBundle
----------------
* The `MoneyOrder` implementation was changed using `IntegrationBundle` (refer to `PaymentBundle` and `IntegrationBundle` for details).
* The [`Configuration`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/MoneyOrderBundle/DependencyInjection/Configuration.php "Oro\Bundle\MoneyOrderBundle\DependencyInjection\Configuration") class was removed. Use [`MoneyOrderSettings`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/MoneyOrderBundle/Entity/MoneyOrderSettings.php "Oro\Bundle\MoneyOrderBundle\Entity\MoneyOrderSettings") entity that extends the [`Transport`](https://github.com/orocrm/platform/tree/2.1.0/src/Oro/Bundle/IntegrationBundle/Entity/Transport.php "Oro\Bundle\IntegrationBundle\Entity\Transport") class to store payment integration properties.
* The [`MoneyOrderConfig`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/MoneyOrderBundle/Method/Config/MoneyOrderConfig.php "Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfig") class was implemented as `ParameterBag` that keeps the payment integration properties that are stored in [`MoneyOrderSettings`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/MoneyOrderBundle/Entity/MoneyOrderSettings.php "Oro\Bundle\MoneyOrderBundle\Entity\MoneyOrderSettings").
* The following methods were removed: 
  - [`MoneyOrderConfig::getPaymentExtensionAlias`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/MoneyOrderBundle/Method/Config/MoneyOrderConfig.php "Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfig")
  - [`MoneyOrder::getType`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/MoneyOrderBundle/Method/MoneyOrder.php "Oro\Bundle\MoneyOrderBundle\Method\MoneyOrder")
  - [`MoneyOrderView::getPaymentMethodType`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/MoneyOrderBundle/Method/View/MoneyOrderView.php "Oro\Bundle\MoneyOrderBundle\Method\View\MoneyOrderView")
* The [`MoneyOrderView`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/MoneyOrderBundle/Method/View/MoneyOrderView.php "Oro\Bundle\MoneyOrderBundle\Method\View\MoneyOrderView") class have got the following additional methods due to implementing [`Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface`](#"https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/View/PaymentMethodViewInterface.php"):
  * The `getAdminLabel` that is used to display labels in the management console
  * The `getPaymentMethodIdentifier` that is used to properly display different payment methods on the front store
* Based on the changes in `PaymentBundle`, the following classes were added:
  * [`MoneyOrderMethodProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/MoneyOrderBundle/Method/Provider/MoneyOrderMethodProvider.php "Oro\Bundle\MoneyOrderBundle\Method\Provider\MoneyOrderMethodProvider") that provides Money Order payment methods.
  * [`MoneyOrderMethodViewProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/MoneyOrderBundle/Method/View/Provider/MoneyOrderMethodViewProvider.php "Oro\Bundle\MoneyOrderBundle\Method\View\Provider\MoneyOrderMethodViewProvider") that provides Money Order payment method views.
* Multiple classes were added to implement payment through integration and most of them have interfaces, so they are extendable through composition:
  - [`MoneyOrderSettingsType`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/MoneyOrderBundle/Form/Type/MoneyOrderSettingsType.php "Oro\Bundle\MoneyOrderBundle\Form\Type\MoneyOrderSettingsType")
  - [`MoneyOrderChannelType`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro\Bundle/MoneyOrderBundle/Integration/MoneyOrderChannelType.php "Oro\Bundle\MoneyOrderBundle\Integration\MoneyOrderChannelType")
  - [`MoneyOrderTransport`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/MoneyOrderBundle/Integration/MoneyOrderTransport.php "Oro\Bundle\MoneyOrderBundle\Integration\MoneyOrderTransport")
  - [`MoneyOrderConfigFactory`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/MoneyOrderBundle/Method/Config/Factory/MoneyOrderConfigFactory.php "Oro\Bundle\MoneyOrderBundle\Method\Config\Factory\MoneyOrderConfigFactory")
  - [`MoneyOrderConfigProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/MoneyOrderBundle/Method/Config/Provider/MoneyOrderConfigProvider.php "Oro\Bundle\MoneyOrderBundle\Method\Config\Provider\MoneyOrderConfigProvider")
  - [`MoneyOrderPaymentMethodFactory`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/MoneyOrderBundle/Method/Factory/MoneyOrderPaymentMethodFactory.php "Oro\Bundle\MoneyOrderBundle\Method\Factory\MoneyOrderPaymentMethodFactory")
  - [`MoneyOrderPaymentMethodViewFactory`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/MoneyOrderBundle/Method/View/Factory/MoneyOrderPaymentMethodViewFactory.php "Oro\Bundle\MoneyOrderBundle\Method\View\Factory\MoneyOrderPaymentMethodViewFactory")

NavigationBundle
----------------
* The placeholders format for `navigation.yml` files changed. Please use `%` instead of `%%`.
* The following classes were removed: 
  - [`BreadcrumbManager`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/FrontendBundle/Menu/BreadcrumbManager.php "Oro\Bundle\FrontendBundle\Menu\BreadcrumbManager") - from now on, the menu name for building breadcrumbs is set directly in the 
[`NavigationTitleProvider::getTitle`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/NavigationBundle/Layout/DataProvider/NavigationTitleProvider.php "Oro\Bundle\NavigationBundle\Layout\DataProvider\NavigationTitleProvider").
  - [`RequestTitleListener`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/NavigationBundle/Event/RequestTitleListener.php "Oro\Bundle\NavigationBundle\Event\RequestTitleListener")
  - [`TitleProvider`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/NavigationBundle/Provider/TitleProvider.php "Oro\Bundle\NavigationBundle\Provider\TitleProvider") 
* The [`TitleServiceInterface::loadByRoute`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/NavigationBundle/Provider/TitleServiceInterface.php "Oro\Bundle\NavigationBundle\Provider\TitleServiceInterface") method signature changed to `($route, $menuName = null)` to provide ability to set the menu that will be used for building the title.
* The title db cache was removed. todether with the following elements:
    - The `oro:navigation:init` command  (the titles are now generated and cached on fly, there is no need to launch the process manually any more)
    - The `Oro\Bundle\NavigationBundle\Entity\Repository` repository
    - The `Oro\Bundle\NavigationBundle\Entity\Title` entity
* The [`TitleReaderRegistry`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/NavigationBundle/Title/TitleReader/TitleReaderRegistry.php "Oro\Bundle\NavigationBundle\Title\TitleReader\TitleReaderRegistry") was added and the dedicated `oro_navigation.title_reader` service tag is now used to register the custom title template reader.
* The [`ConfigurationProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/NavigationBundle/Provider/ConfigurationProvider.php "Oro\Bundle\NavigationBundle\Provider\ConfigurationProvider") was added to substitute the NavigationExtension. 
* The [`AnnotationsReader`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/NavigationBundle/Title/TitleReader/AnnotationsReader.php "Oro\Bundle\NavigationBundle\Title\TitleReader\AnnotationsReader") constructor signature changed to `__construct(RequestStack $requestStack, Reader $reader)`
* The [`TranslationExtractor`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/NavigationBundle/Title/TranslationExtractor.php "Oro\Bundle\NavigationBundle\Title\TranslationExtractor") constructor signature changed to `__construct(TitleReaderRegistry $titleReaderRegistry, RouterInterface $router)`
* The [`NavigationElementsContentProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/NavigationBundle/ContentProvider/NavigationElementsContentProvider.php "Oro\Bundle\NavigationBundle\ContentProvider\NavigationElementsContentProvider") constructor signature changed to `__construct(ConfigurationProvider $configurationProvider)`
* The [`MenuConfiguration`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/NavigationBundle/Config/MenuConfiguration.php "Oro\Bundle\NavigationBundle\Config\MenuConfiguration") constructor signature changed to `__construct(ConfigurationProvider $configurationProvider)`

OrderBundle
-----------
* Payment history section with payment transactions for current order was added to the order view page.
* The `VIEW_PAYMENT_HISTORY` permission was added for viewing payment history section.
* The following classes and methods were removed:
  - The [`OrderPossibleShippingMethodsEventListener`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/OrderBundle/EventListener/Order/OrderPossibleShippingMethodsEventListener.php "Oro\Bundle\OrderBundle\EventListener\Order\OrderPossibleShippingMethodsEventListener") class
  - The [`OrderController::successAction`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/OrderBundle/Controller/Frontend/OrderController.php "Oro\Bundle\OrderBundle\Controller\Frontend\OrderController") method
  - The [`OrderShippingExtension::setShippingLabelFormatter`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/OrderBundle/Twig/OrderShippingExtension.php "Oro\Bundle\OrderBundle\Twig\OrderShippingExtension") method
* The following methods were updated:
  - [`OrderShippingContextFactory::create`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/OrderBundle/Factory/OrderShippingContextFactory.php "Oro\Bundle\OrderBundle\Factory\OrderShippingContextFactory") accepts `mixed` in the first argument instead of [`Order`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/OrderBundle/Entity/Order.php "Oro\Bundle\OrderBundle\Entity\Order").
  [`OrderExtension::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/OrderBundle/Twig/OrderExtension.php "Oro\Bundle\OrderBundle\Twig\OrderExtension") expects the `Symfony\Component\DependencyInjection\ContainerInterface` in the first argument instead of [`SourceDocumentFormatter`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/OrderBundle/Formatter/SourceDocumentFormatter.php "Oro\Bundle\OrderBundle\Formatter\SourceDocumentFormatter").

PaymentBundle
-------------

* The [`PaymentMethodExtension`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Twig/PaymentMethodExtension.php "Oro\Bundle\PaymentBundle\Twig\PaymentMethodExtension") class was updated with the following changes:
  - The construction signature of was changed and the constructor accepts only one `ContainerInterface $container` parameter.
  - The following properties were removed:
    + `protected $paymentTransactionProvider`
    + `protected $paymentMethodLabelFormatter`
    + `protected $dispatcher`
* The [`PaymentStatusExtension`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Twig/PaymentStatusExtension.php "Oro\Bundle\PaymentBundle\Twig\PaymentStatusExtension") class was updated with the following changes:
  - The construction signature of was changed and the constructor accepts only one `ContainerInterface $container` parameter.
  - The following property was removed:
    + `protected $paymentStatusLabelFormatter`
* The following classes (that are related to the actions that disable/enable
[`PaymentMethodsConfigsRule`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Entity/PaymentMethodsConfigsRule.php "Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule")) were abstracted and moved to the [`RuleBundle`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle "Oro\Bundle\RuleBundle") (see the [`RuleBundle`](#RuleBundle)) section for more information):
  - [`StatusDisableMassAction`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Datagrid/Extension/MassAction/Actions/StatusDisableMassAction.php "Oro\Bundle\PaymentBundle\Datagrid\Extension\MassAction\Actions\StatusDisableMassAction") (is replaced with [`StatusEnableMassAction`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle/Datagrid/Extension/MassAction/Actions/StatusEnableMassAction.php "Oro\Bundle\RuleBundle\Datagrid\Extension\MassAction\Actions\StatusEnableMassAction") in the [`RuleBundle`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle "Oro\Bundle\RuleBundle"))
  - [`StatusEnableMassAction`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Datagrid/Extension/MassAction/Actions/StatusEnableMassAction.php "Oro\Bundle\PaymentBundle\Datagrid\Extension\MassAction\Actions\StatusEnableMassAction") (is replaced with [`StatusEnableMassAction`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle/Datagrid/Extension/MassAction/Actions/StatusEnableMassAction.php "Oro\Bundle\RuleBundle\Datagrid\Extension\MassAction\Actions\StatusEnableMassAction") in the [`RuleBundle`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle "Oro\Bundle\RuleBundle"))
  - [`StatusMassActionHandler`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Datagrid/Extension/MassAction/StatusMassActionHandler.php "Oro\Bundle\PaymentBundle\Datagrid\Extension\MassAction\StatusMassActionHandler") (is replaced with [`StatusMassActionHandler`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle/Datagrid/Extension/MassAction/StatusMassActionHandler.php "Oro\Bundle\RuleBundle\Datagrid\Extension\MassAction\StatusMassActionHandler") in the [`RuleBundle`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle "Oro\Bundle\RuleBundle"))
  - [`PaymentRuleActionsVisibilityProvider`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Datagrid/PaymentRuleActionsVisibilityProvider.php "Oro\Bundle\PaymentBundle\Datagrid\PaymentRuleActionsVisibilityProvider") (is replaced with [`PaymentRuleActionsVisibilityProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle/Datagrid/PaymentRuleActionsVisibilityProvider.php "Oro\Bundle\RuleBundle\Datagrid\PaymentRuleActionsVisibilityProvider") in the [`RuleBundle`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle "Oro\Bundle\RuleBundle"))
* The following classes (that are related to decorating [`Product`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ProductBundle/Entity/Product.php "Oro\Bundle\ProductBundle\Entity\Product") with virtual fields) were abstracted and moved to the [`ProductBundle`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ProductBundle "Oro\Bundle\ProductBundle") (see the [`ProductBundle`](#ProductBundle) section for more information):
  - [`SelectQueryConverter`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/QueryDesigner/SelectQueryConverter.php "Oro\Bundle\PaymentBundle\QueryDesigner\SelectQueryConverter") 
  - [`PaymentProductQueryDesigner`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/QueryDesigner/PaymentProductQueryDesigner.php "Oro\Bundle\PaymentBundle\QueryDesigner\PaymentProductQueryDesigner") 
  - [`ProductDecorator`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/ExpressionLanguage/ProductDecorator.php "Oro\Bundle\PaymentBundle\ExpressionLanguage\ProductDecorator")
  - In the [`DecoratedProductLineItemFactory`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/ExpressionLanguage/DecoratedProductLineItemFactory.php "Oro\Bundle\PaymentBundle\ExpressionLanguage\DecoratedProductLineItemFactory") class, the only dependency is now 
[`VirtualFieldsProductDecoratorFactory`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ProductBundle/VirtualFields/VirtualFieldsProductDecoratorFactory.php "Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecoratorFactory").
* The *organization* ownership type was added for the [`PaymentMethodsConfigsRule`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Entity/PaymentMethodsConfigsRule.php "Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule") entity.
* In order to have possibility to create more than one payment method of the same type, the PaymentBundle was significantly changed **with backward compatibility break**:
  - A new [`PaymentMethodProviderInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/Provider/PaymentMethodProviderInterface.php "Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface") interface was added. This interface should be implemented in any payment method provider class that is responsible for providing of any payment method.
  - A new [`PaymentMethodViewProviderInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/View/PaymentMethodViewProviderInterface.php "Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface") interface was added. This interface should be implemented in any payment method view provider class that is responsible for providing of any payment method view.
  - The [`PaymentMethodRegistry`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Method/PaymentMethodRegistry.php "Oro\Bundle\PaymentBundle\Method\PaymentMethodRegistry") class was replaced with the [`PaymentMethodProvidersRegistry`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/Provider/Registry/PaymentMethodProvidersRegistry.php "Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistry") which implements a [`PaymentMethodProvidersRegistryInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/Provider/Registry/PaymentMethodProvidersRegistryInterface.php "Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistryInterface") and this registry is responsible for collecting data from all payment method providers.
  - The [`PaymentMethodViewRegistry`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Method/View/PaymentMethodViewRegistry.php "Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewRegistry") class was replaced with the [`CompositePaymentMethodViewProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/View/CompositePaymentMethodViewProvider.php "Oro\Bundle\PaymentBundle\Method\View\CompositePaymentMethodViewProvider") which implements a [`PaymentMethodViewProviderInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/View/PaymentMethodViewProviderInterface.php "Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface"). This composite provider is a single point to provide data from all payment method view providers.
  - Any payment method provider should be registered in the service definitions with tag *oro_payment.payment_method_provider*.
  - Any payment method view provider should be registered in the service definitions with tag *oro_payment.payment_method_view_provider*.
  - Each payment method provider should provide one or more payment methods which should implement [`PaymentMethodInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/PaymentMethodInterface.php "Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface").
  - Each payment method view provider should provide one or more payment method views which should implement [`PaymentMethodViewInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/View/PaymentMethodViewInterface.php "Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface").
  - To aggregate the shared logic of all payment method providers, the [`AbstractPaymentMethodProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/Provider/AbstractPaymentMethodProvider.php "Oro\Bundle\PaymentBundle\Method\Provider\AbstractPaymentMethodProvider") was created. Any new payment method provider should extend this class.
  - To aggregate the shared logic of all payment method view providers, the [`AbstractPaymentMethodViewProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/View/AbstractPaymentMethodViewProvider.php "Oro\Bundle\PaymentBundle\Method\View\AbstractPaymentMethodViewProvider") was created. Any new payment method view provider should extend this class.
  - The following changes occurred to the [`PaymentMethodInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/PaymentMethodInterface.php "Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface"):
    + The following method was removed:
      * [`PaymentMethodInterface::getType()`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Method/PaymentMethodInterface.php "Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface")
    + The following method was added:
      * [`PaymentMethodInterface::getIdentifier()`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/PaymentMethodInterface.php "Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface")
  - The following changes occurred to the [`PaymentMethodViewInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/View/PaymentMethodViewInterface.php "Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface"):
    + The following method was removed:
      * [`PaymentMethodViewInterface::getPaymentMethodType()`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Method/View/PaymentMethodViewInterface.php "Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface")
    + The following methods were added:
      * [`PaymentMethodViewInterface::getAdminLabel()`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/View/PaymentMethodViewInterface.php "Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface")
      * [`PaymentMethodViewInterface::getPaymentMethodIdentifier()`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/View/PaymentMethodViewInterface.php "Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface")
  - The following changes occurred to the [`PaymentConfigInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/Config/PaymentConfigInterface.php "Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigInterface"):
    + The following method was removed:
      * [`PaymentConfigInterface::getAdminLabel()`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/Config/PaymentConfigInterface.php "Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigInterface")
      * [`PaymentConfigInterface::getPaymentMethodIdentifier()`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/Config/PaymentConfigInterface.php "Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigInterface")

PayPalBundle
------------
* PayPalBundle implementation was changed using IntegrationBundle (refer to PaymentBundle and IntegrationBundle for details). Notable changes:
    - Class `Oro\Bundle\PayPalBundle\DependencyInjection\Configuration` was removed and instead `Oro\Bundle\PayPalBundle\Entity\PayPalSettings` was created - entity that implements `Oro\Bundle\IntegrationBundle\Entity\Transport` to store paypal payment integration properties
    - Classes `Oro\Bundle\PayPalBundle\Method\Config\PayflowExpressCheckoutConfig`, `Oro\Bundle\PayPalBundle\Method\Config\PayPalPaymentsProExpressCheckoutConfig` were removed and instead simple parameter bag object `Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfig` is being used for holding payment integration properties that are stored in PayPalSettings
    - Classes `Oro\Bundle\PayPalBundle\Method\Config\PayflowGatewayConfig`, `Oro\Bundle\PayPalBundle\Method\Config\PayPalPaymentsProConfig` were removed and instead simple parameter bag object `Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfig` is being used for holding payment integration properties that are stored in PayPalSettings
    - Classes `Oro\Bundle\PayPalBundle\Method\PayflowExpressCheckout`, `Oro\Bundle\PayPalBundle\Method\PayPalPaymentsProExpressCheckout` were removed and instead was added `Oro\Bundle\PayPalBundle\Method\PayPalExpressCheckoutPaymentMethod`
    - Classes `Oro\Bundle\PayPalBundle\Method\PayflowGateway`, `Oro\Bundle\PayPalBundle\Method\PayPalPaymentsPro` were removed and instead was added `Oro\Bundle\PayPalBundle\Method\PayPalCreditCardPaymentMethod`
    - Classes `Oro\Bundle\PayPalBundle\Method\View\PayflowExpressCheckout`, `Oro\Bundle\PayPalBundle\Method\View\PayPalPaymentsProExpressCheckout` were removed and instead was added `Oro\Bundle\PayPalBundle\Method\View\PayPalExpressCheckoutPaymentMethodView`
    - Classes `Oro\Bundle\PayPalBundle\Method\View\PayflowGateway`, `Oro\Bundle\PayPalBundle\Method\View\PayPalPaymentsPro` were removed and instead was added `Oro\Bundle\PayPalBundle\Method\View\PayPalCreditCardPaymentMethodView`
    - According to changes in PaymentBundle were added:
        - `Oro\Bundle\PayPalBundle\Method\Provider\CreditCardMethodProvider` for providing *PayPal Credit Card Payment Methods*
        - `Oro\Bundle\PayPalBundle\Method\View\Provider\CreditCardMethodViewProvider` for providing *PayPal Credit Card Payment Method Views*
        - `Oro\Bundle\PayPalBundle\Method\Provider\ExpressCheckoutMethodProvider` for providing *PayPal Express Checkout Payment Methods*
        - `Oro\Bundle\PayPalBundle\Method\View\Provider\ExpressCheckoutMethodViewProvider` for providing *PayPal Express Checkout Payment Method Views*
    - Added multiple classes to implement payment through integration and most of them have interfaces, so they are extendable through composition:
        - `Oro\Bundle\PayPalBundle\Form\Type\PayPalSettingsType`
        - `Oro\Bundle\PayPalBundle\Integration\PayPalPayflowGatewayChannelType`
        - `Oro\Bundle\PayPalBundle\Integration\PayPalPayflowGatewayTransport`
        - `Oro\Bundle\PayPalBundle\Integration\PayPalPaymentsProChannelType`
        - `Oro\Bundle\PayPalBundle\Integration\PayPalPaymentsProTransport`
        - `Oro\Bundle\PayPalBundle\Method\Config\AbstractPayPalConfig`
        - `Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfig`
        - `Oro\Bundle\PayPalBundle\Method\Config\Factory\AbstractPayPalConfigFactory`
        - `Oro\Bundle\PayPalBundle\Method\Config\Factory\PayPalCreditCardConfigFactory`
        - `Oro\Bundle\PayPalBundle\Method\Config\Factory\PayPalExpressCheckoutConfigFactory`
        - `Oro\Bundle\PayPalBundle\Method\Config\Provider\AbstractPayPalConfigProvider`
        - `Oro\Bundle\PayPalBundle\Method\Config\Provider\PayPalCreditCardConfigProvider`
        - `Oro\Bundle\PayPalBundle\Method\Config\Provider\PayPalExpressCheckoutConfigProvider`
        - `Oro\Bundle\PayPalBundle\Method\Factory\BasicPayPalCreditCardPaymentMethodFactory`
        - `Oro\Bundle\PayPalBundle\Method\Factory\BasicPayPalExpressCheckoutPaymentMethodFactory`
        - `Oro\Bundle\PayPalBundle\Method\View\Factory\BasicPayPalCreditCardPaymentMethodViewFactory`
        - `Oro\Bundle\PayPalBundle\Method\View\Factory\BasicPayPalExpressCheckoutPaymentMethodViewFactory`
        - `Oro\Bundle\PayPalBundle\Settings\DataProvider\BasicCardTypesDataProvider`
        - `Oro\Bundle\PayPalBundle\Settings\DataProvider\BasicPaymentActionsDataProvider`
* The method [`PayflowExpressCheckoutListener::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/EventListener/Callback/PayflowExpressCheckoutListener.php "Oro\Bundle\PayPalBundle\EventListener\Callback\PayflowExpressCheckoutListener") has been updated. Pass [`PaymentMethodProviderInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/Provider/PaymentMethodProviderInterface.php "Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface") as a first argument of the method instead of [`PaymentMethodRegistry`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Method/PaymentMethodRegistry.php "Oro\Bundle\PaymentBundle\Method\PaymentMethodRegistry").
* The method [`PayflowExpressCheckoutRedirectListener::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/EventListener/Callback/PayflowExpressCheckoutRedirectListener.php "Oro\Bundle\PayPalBundle\EventListener\Callback\PayflowExpressCheckoutRedirectListener") has been updated. Pass [`PaymentMethodProviderInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/Provider/PaymentMethodProviderInterface.php "Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface") as a second argument of the method. Pass [`PaymentMethodProviderInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/Provider/PaymentMethodProviderInterface.php "Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface") as a second argument of the method instead of `mixed`.
* The method [`PayflowIPCheckListener::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/EventListener/Callback/PayflowIPCheckListener.php "Oro\Bundle\PayPalBundle\EventListener\Callback\PayflowIPCheckListener") has been updated. Pass [`PaymentMethodProviderInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/Provider/PaymentMethodProviderInterface.php "Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface") as a second argument of the method. Pass [`PaymentMethodProviderInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/Provider/PaymentMethodProviderInterface.php "Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface") as a second argument of the method instead of `mixed`.
* The method [`PayflowListener::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/EventListener/Callback/PayflowListener.php "Oro\Bundle\PayPalBundle\EventListener\Callback\PayflowListener") has been updated. Pass [`PaymentMethodProviderInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/Provider/PaymentMethodProviderInterface.php "Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface") as a second argument of the method instead of [`PaymentMethodRegistry`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Method/PaymentMethodRegistry.php "Oro\Bundle\PaymentBundle\Method\PaymentMethodRegistry").
* The method [`ZeroAmountAuthorizationRedirectListener::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/EventListener/ZeroAmountAuthorizationRedirectListener.php "Oro\Bundle\PayPalBundle\EventListener\ZeroAmountAuthorizationRedirectListener") has been updated. Pass [`PayPalCreditCardConfigProviderInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/Config/Provider/PayPalCreditCardConfigProviderInterface.php "Oro\Bundle\PayPalBundle\Method\Config\Provider\PayPalCreditCardConfigProviderInterface") as a first argument of the method instead of [`PayflowGatewayConfigInterface`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PayPalBundle/Method/Config/PayflowGatewayConfigInterface.php "Oro\Bundle\PayPalBundle\Method\Config\PayflowGatewayConfigInterface").

PaymentBundle
-------------
* The following classes were removed:
    - [`StatusDisableMassAction`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Datagrid/Extension/MassAction/Actions/StatusDisableMassAction.php "Oro\Bundle\PaymentBundle\Datagrid\Extension\MassAction\Actions\StatusDisableMassAction")
    - [`StatusEnableMassAction`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Datagrid/Extension/MassAction/Actions/StatusEnableMassAction.php "Oro\Bundle\PaymentBundle\Datagrid\Extension\MassAction\Actions\StatusEnableMassAction")
    - [`StatusMassActionHandler`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Datagrid/Extension/MassAction/StatusMassActionHandler.php "Oro\Bundle\PaymentBundle\Datagrid\Extension\MassAction\StatusMassActionHandler")
    - [`PaymentRuleActionsVisibilityProvider`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Datagrid/PaymentRuleActionsVisibilityProvider.php "Oro\Bundle\PaymentBundle\Datagrid\PaymentRuleActionsVisibilityProvider")
    - [`PaymentMethodPass`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/DependencyInjection/Compiler/PaymentMethodPass.php "Oro\Bundle\PaymentBundle\DependencyInjection\Compiler\PaymentMethodPass")
    - [`PaymentMethodViewPass`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/DependencyInjection/Compiler/PaymentMethodViewPass.php "Oro\Bundle\PaymentBundle\DependencyInjection\Compiler\PaymentMethodViewPass")
    - [`ProductDecorator`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/ExpressionLanguage/ProductDecorator.php "Oro\Bundle\PaymentBundle\ExpressionLanguage\ProductDecorator")
    - [`PaymentMethodRegistry`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Method/PaymentMethodRegistry.php "Oro\Bundle\PaymentBundle\Method\PaymentMethodRegistry")
    - [`PaymentMethodViewRegistry`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Method/View/PaymentMethodViewRegistry.php "Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewRegistry")
    - [`PaymentContextProvider`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Provider/PaymentContextProvider.php "Oro\Bundle\PaymentBundle\Provider\PaymentContextProvider")
    - [`PaymentProductQueryDesigner`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/QueryDesigner/PaymentProductQueryDesigner.php "Oro\Bundle\PaymentBundle\QueryDesigner\PaymentProductQueryDesigner")
    - [`SelectQueryConverter`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/QueryDesigner/SelectQueryConverter.php "Oro\Bundle\PaymentBundle\QueryDesigner\SelectQueryConverter")
* The following methods in class [`PaymentDiscountSurchargeListener`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/EventListener/PaymentDiscountSurchargeListener.php "Oro\Bundle\PaymentBundle\EventListener\PaymentDiscountSurchargeListener") were removed:
   - `__construct`
   - `onCollectSurcharge`
* The following methods in class [`PaymentShippingSurchargeListener`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/EventListener/PaymentShippingSurchargeListener.php "Oro\Bundle\PaymentBundle\EventListener\PaymentShippingSurchargeListener") were removed:
   - `__construct`
   - `onCollectSurcharge`
* The method [`AbstractPaymentConfig::getPaymentExtensionAlias`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Method/Config/AbstractPaymentConfig.php "Oro\Bundle\PaymentBundle\Method\Config\AbstractPaymentConfig") was removed.
* The method [`AbstractPaymentMethodAction::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Action/AbstractPaymentMethodAction.php "Oro\Bundle\PaymentBundle\Action\AbstractPaymentMethodAction") has been updated. Pass [`PaymentMethodProvidersRegistryInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/Provider/Registry/PaymentMethodProvidersRegistryInterface.php "Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistryInterface") as a second argument of the method instead of [`PaymentMethodRegistry`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Method/PaymentMethodRegistry.php "Oro\Bundle\PaymentBundle\Method\PaymentMethodRegistry").
* The method [`PaymentMethodSupports::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Condition/PaymentMethodSupports.php "Oro\Bundle\PaymentBundle\Condition\PaymentMethodSupports") has been updated. Pass [`PaymentMethodProvidersRegistryInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/Provider/Registry/PaymentMethodProvidersRegistryInterface.php "Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistryInterface") as a first argument of the method instead of [`PaymentMethodRegistry`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Method/PaymentMethodRegistry.php "Oro\Bundle\PaymentBundle\Method\PaymentMethodRegistry").
* The method [`RequirePaymentRedirect::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Condition/RequirePaymentRedirect.php "Oro\Bundle\PaymentBundle\Condition\RequirePaymentRedirect") has been updated. Pass [`PaymentMethodProvidersRegistryInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/Provider/Registry/PaymentMethodProvidersRegistryInterface.php "Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistryInterface") as a first argument of the method instead of [`PaymentMethodRegistry`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Method/PaymentMethodRegistry.php "Oro\Bundle\PaymentBundle\Method\PaymentMethodRegistry").
* The method [`PaymentMethodsConfigsRule::setRule`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Entity/PaymentMethodsConfigsRule.php "Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule") has been updated. Pass [`RuleInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle/Entity/RuleInterface.php "Oro\Bundle\RuleBundle\Entity\RuleInterface") as a first argument of the method instead of [`Rule`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/RuleBundle/Entity/Rule.php "Oro\Bundle\RuleBundle\Entity\Rule").
* The method [`DecoratedProductLineItemFactory::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/ExpressionLanguage/DecoratedProductLineItemFactory.php "Oro\Bundle\PaymentBundle\ExpressionLanguage\DecoratedProductLineItemFactory") has been updated. Pass [`VirtualFieldsProductDecoratorFactory`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ProductBundle/VirtualFields/VirtualFieldsProductDecoratorFactory.php "Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecoratorFactory") as a first argument of the method instead of [`EntityFieldProvider`](https://github.com/orocrm/platform/tree/2.0.0/src/Oro/Bundle/EntityBundle/Provider/EntityFieldProvider.php "Oro\Bundle\EntityBundle\Provider\EntityFieldProvider").
* The method [`RuleMethodConfigCollectionSubscriber::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Form/EventSubscriber/RuleMethodConfigCollectionSubscriber.php "Oro\Bundle\PaymentBundle\Form\EventSubscriber\RuleMethodConfigCollectionSubscriber") has been updated. Pass [`PaymentMethodProvidersRegistryInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/Provider/Registry/PaymentMethodProvidersRegistryInterface.php "Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistryInterface") as a first argument of the method instead of [`PaymentMethodRegistry`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Method/PaymentMethodRegistry.php "Oro\Bundle\PaymentBundle\Method\PaymentMethodRegistry").
* The method [`PaymentMethodConfigType::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Form/Type/PaymentMethodConfigType.php "Oro\Bundle\PaymentBundle\Form\Type\PaymentMethodConfigType") has been updated. Pass [`PaymentMethodProvidersRegistryInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/Provider/Registry/PaymentMethodProvidersRegistryInterface.php "Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistryInterface") as a first argument of the method instead of [`PaymentMethodRegistry`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Method/PaymentMethodRegistry.php "Oro\Bundle\PaymentBundle\Method\PaymentMethodRegistry"). Pass [`PaymentMethodViewProviderInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/View/PaymentMethodViewProviderInterface.php "Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface") as a second argument of the method instead of `Symfony\Component\Translation\TranslatorInterface`.
* The method [`PaymentMethodsConfigsRuleType::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Form/Type/PaymentMethodsConfigsRuleType.php "Oro\Bundle\PaymentBundle\Form\Type\PaymentMethodsConfigsRuleType") has been updated. Pass [`PaymentMethodProvidersRegistryInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/Provider/Registry/PaymentMethodProvidersRegistryInterface.php "Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistryInterface") as a first argument of the method instead of [`PaymentMethodRegistry`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Method/PaymentMethodRegistry.php "Oro\Bundle\PaymentBundle\Method\PaymentMethodRegistry"). Pass [`PaymentMethodViewProviderInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/View/PaymentMethodViewProviderInterface.php "Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface") as a second argument of the method instead of `Symfony\Component\Translation\TranslatorInterface`.
* The method [`PaymentMethodLabelFormatter::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Formatter/PaymentMethodLabelFormatter.php "Oro\Bundle\PaymentBundle\Formatter\PaymentMethodLabelFormatter") has been updated. Pass [`PaymentMethodViewProviderInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/View/PaymentMethodViewProviderInterface.php "Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface") as a first argument of the method instead of [`PaymentMethodViewRegistry`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Method/View/PaymentMethodViewRegistry.php "Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewRegistry").
* The method [`PaymentMethodViewsProvider::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Layout/DataProvider/PaymentMethodViewsProvider.php "Oro\Bundle\PaymentBundle\Layout\DataProvider\PaymentMethodViewsProvider") has been updated. Pass [`PaymentMethodViewProviderInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/View/PaymentMethodViewProviderInterface.php "Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface") as a first argument of the method instead of [`PaymentMethodViewRegistry`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Method/View/PaymentMethodViewRegistry.php "Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewRegistry").
* The method [`AbstractPaymentConfig::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/Config/AbstractPaymentConfig.php "Oro\Bundle\PaymentBundle\Method\Config\AbstractPaymentConfig") has been updated. Pass [`Channel`](https://github.com/orocrm/platform/tree/2.1.0/src/Oro/Bundle/IntegrationBundle/Entity/Channel.php "Oro\Bundle\IntegrationBundle\Entity\Channel") as a first argument of the method instead of [`ConfigManager`](https://github.com/orocrm/platform/tree/2.0.0/src/Oro/Bundle/ConfigBundle/Config/ConfigManager.php "Oro\Bundle\ConfigBundle\Config\ConfigManager").
* The method [`PaymentMethodProvider::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/Provider/PaymentMethodProvider.php "Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProvider") has been updated. Pass [`PaymentMethodProvidersRegistryInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/Provider/Registry/PaymentMethodProvidersRegistryInterface.php "Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistryInterface") as a first argument of the method instead of [`PaymentMethodRegistry`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Method/PaymentMethodRegistry.php "Oro\Bundle\PaymentBundle\Method\PaymentMethodRegistry").
* The method [`PaymentMethodExtension::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Twig/PaymentMethodExtension.php "Oro\Bundle\PaymentBundle\Twig\PaymentMethodExtension") has been updated. Pass `Symfony\Component\DependencyInjection\ContainerInterface` as a first argument of the method instead of [`PaymentTransactionProvider`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Provider/PaymentTransactionProvider.php "Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider").
* The method [`PaymentStatusExtension::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Twig/PaymentStatusExtension.php "Oro\Bundle\PaymentBundle\Twig\PaymentStatusExtension") has been updated. Pass `Symfony\Component\DependencyInjection\ContainerInterface` as a first argument of the method instead of [`PaymentStatusLabelFormatter`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Formatter/PaymentStatusLabelFormatter.php "Oro\Bundle\PaymentBundle\Formatter\PaymentStatusLabelFormatter").
* In order to have possibility to create more than one payment method of same type PaymentBundle was significantly changed **with breaking backwards compatibility**.
    * To realize this was added `Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface` which should be implemented in any class(payment method provider) which is responsible for providing of any payment method.
    * Also was added `Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface` which should be implemented in any class(payment method view provider) which is responsible for providing of any payment method view.
    * Class `Oro\Bundle\PaymentBundle\Method\PaymentMethodRegistry` was changed to `Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistry` which implements `Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistryInterface` and this registry is responsible for collecting data from all payment method providers
    * Class `Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewRegistry` was changed to `Oro\Bundle\PaymentBundle\Method\View\CompositePaymentMethodViewProvider` which implements `Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface` this composite provider is single point to provide data from all payment method view providers
    * Any payment method provider should be registered in service definitions with tag *oro_payment.payment_method_provider*
    * Any payment method view provider should be registered in service definitions with tag *oro_payment.payment_method_view_provider*
    * Each payment method provider should provide payment method(one or many) which should implement `Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface`.
    * Each payment method view provider should provide payment method view(one or many) which should implement `Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface`.
    * In order to keep all common logic of all payment method providers was created `Oro\Bundle\PaymentBundle\Method\Provider\AbstractPaymentMethodProvider` which should be extended by any payment method provider
    * In order to keep all common logic of all payment method view providers was created `Oro\Bundle\PaymentBundle\Method\View\AbstractPaymentMethodViewProvider` which should be extended by any payment method view provider
    * Class`Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface` was modified:
        * removed methods:
            * `getType`
        * added methods:
            * `getIdentifier`
    * Class`Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface` was modified:
        * removed methods:
            * `getPaymentMethodType`
        * added methods:
            * `getAdminLabel`
            * `getPaymentMethodIdentifier`
    * Class`Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigInterface` was modified:
        * added methods:
            * `getAdminLabel`
            * `getPaymentMethodIdentifier`

PaymentTermBundle
-----------------
* The following classes were removed:
    - [`PaymentTermController`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentTermBundle/Controller/Api/Rest/PaymentTermController.php "Oro\Bundle\PaymentTermBundle\Controller\Api\Rest\PaymentTermController")
    - [`Configuration`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentTermBundle/DependencyInjection/Configuration.php "Oro\Bundle\PaymentTermBundle\DependencyInjection\Configuration")
    - [`PaymentTermConfig`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentTermBundle/Method/Config/PaymentTermConfig.php "Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfig")
* The method [`PaymentTerm::getType`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentTermBundle/Method/PaymentTerm.php "Oro\Bundle\PaymentTermBundle\Method\PaymentTerm") was removed.
* The method [`PaymentTermView::getPaymentMethodType`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentTermBundle/Method/View/PaymentTermView.php "Oro\Bundle\PaymentTermBundle\Method\View\PaymentTermView") was removed.
* The method [`PaymentTerm::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentTermBundle/Method/PaymentTerm.php "Oro\Bundle\PaymentTermBundle\Method\PaymentTerm") has been updated. Pass [`PaymentTermConfigInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentTermBundle/Method/Config/PaymentTermConfigInterface.php "Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface") as a fourth argument of the method. Pass [`PaymentTermConfigInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentTermBundle/Method/Config/PaymentTermConfigInterface.php "Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface") as a fourth argument of the method instead of `mixed`.
* The method [`DeleteMessageTextExtension::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentTermBundle/Twig/DeleteMessageTextExtension.php "Oro\Bundle\PaymentTermBundle\Twig\DeleteMessageTextExtension") has been updated. Pass `Symfony\Component\DependencyInjection\ContainerInterface` as a first argument of the method instead of [`DeleteMessageTextGenerator`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentTermBundle/Twig/DeleteMessageTextGenerator.php "Oro\Bundle\PaymentTermBundle\Twig\DeleteMessageTextGenerator").
* PaymentTerm implementation was changed using IntegrationBundle (refer to PaymentBundle and IntegrationBundle for details). Notable changes:
    * Class `Oro\Bundle\PaymentTermBundle\DependencyInjection\Configuration` was removed and instead `Oro\Bundle\PaymentTermBundle\Entity\PaymentTermSettings` was created - entity that implements `Oro\Bundle\IntegrationBundle\Entity\Transport` to store payment integration properties
    * Class `Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfig` was removed and instead simple parameter bag object `Oro\Bundle\PaymentTermBundle\Method\Config\ParameterBagPaymentTermConfig` is being used for holding payment integration properties that are stored in PaymentTermSettings
    * Class `Oro\Bundle\PaymentTermBundle\Method\PaymentTerm` method getIdentifier now uses PaymentTermConfig to retrieve identifier of a concrete method
    * Class `Oro\Bundle\PaymentTermBundle\Method\View\PaymentTermView` now has two additional methods due to implementing `Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface`
        getAdminLabel() is used to display labels in admin panel
        getPaymentMethodIdentifier() used to properly display different methods in frontend
    * Added multiple classes to implement payment through integration and most of them have interfaces, so they are extendable through composition:
        * `Oro\Bundle\PaymentTermBundle\Entity\Repository\PaymentTermSettingsRepository`
        * `Oro\Bundle\PaymentTermBundle\Form\Type\PaymentTermSettingsType`
        * `Oro\Bundle\PaymentTermBundle\Integration\PaymentTermChannelType`
        * `Oro\Bundle\PaymentTermBundle\Integration\PaymentTermTransport`
        * `Oro\Bundle\PaymentTermBundle\Method\Config\ParameterBag\ParameterBagPaymentTermConfig`
        * `Oro\Bundle\PaymentTermBundle\Method\Config\Provider\Basic\BasicPaymentTermConfigProvider`
        * `Oro\Bundle\PaymentTermBundle\Method\Config\Provider\Cached\Memory\CachedMemoryPaymentTermConfigProvider`
        * `Oro\Bundle\PaymentTermBundle\Method\Factory\PaymentTermPaymentMethodFactory`
        * `Oro\Bundle\PaymentTermBundle\Method\Provider\PaymentTermMethodProvider`
        * `Oro\Bundle\PaymentTermBundle\Method\View\Factory\PaymentTermPaymentMethodViewFactory`
        * `Oro\Bundle\PaymentTermBundle\Method\View\Provider\PaymentTermMethodViewProvider`

PricingBundle
-------------
* The following classes were removed:
    - [`ProductController`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PricingBundle/Controller/Frontend/ProductController.php "Oro\Bundle\PricingBundle\Controller\Frontend\ProductController")
    - [`MinimalProductPrice`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PricingBundle/Entity/MinimalProductPrice.php "Oro\Bundle\PricingBundle\Entity\MinimalProductPrice")
    - [`MinimalProductPriceRepository`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PricingBundle/Entity/Repository/MinimalProductPriceRepository.php "Oro\Bundle\PricingBundle\Entity\Repository\MinimalProductPriceRepository")
* The following methods in class [`BasePriceListRelation`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PricingBundle/Entity/BasePriceListRelation.php "Oro\Bundle\PricingBundle\Entity\BasePriceListRelation") were removed:
   - `getPriority`
   - `setPriority`
* The method [`PriceListProductPricesReader::setSourceQueryBuilder`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PricingBundle/ImportExport/Reader/PriceListProductPricesReader.php "Oro\Bundle\PricingBundle\ImportExport\Reader\PriceListProductPricesReader") was removed.
* The following methods in class [`PriceListConfig`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PricingBundle/SystemConfig/PriceListConfig.php "Oro\Bundle\PricingBundle\SystemConfig\PriceListConfig") were removed:
   - `getPriority`
   - `setPriority`
* The method [`CombinedProductPriceResolver::combinePrices`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PricingBundle/Resolver/CombinedProductPriceResolver.php "Oro\Bundle\PricingBundle\Resolver\CombinedProductPriceResolver") has been updated. Pass `mixed` as a third argument of the method.
* Class `Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository` changes:
    - changed the return type of `getCombinedPriceListsByPriceList` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
    - changed the return type of `getCombinedPriceListsByPriceLists` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
    - changed the return type of `getCPLsForPriceCollectByTimeOffset` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
* Class `Oro\Bundle\PricingBundle\Entity\Repository\PriceListCustomerFallbackRepository` changes:
    - changed the return type of `getCustomerIdentityByGroup` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
* Class `Oro\Bundle\PricingBundle\Entity\Repository\PriceListCustomerGroupFallbackRepository` changes:
    - changed the return type of `getCustomerIdentityByWebsite` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
* Class `Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository` changes:
    - changed the return type of `getPriceListsWithRules` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
* Class `Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerGroupRepository` changes:
    - changed the return type of `getCustomerGroupIteratorByDefaultFallback` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
    - changed the return type of `getIteratorByPriceList` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
* Class `Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerRepository` changes:
    - changed the return type of `getCustomerIteratorByDefaultFallback` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
    - changed the return type of `getCustomerWebsitePairsByCustomerGroupIterator` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
    - changed the return type of `getIteratorByPriceList` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
* Class `Oro\Bundle\PricingBundle\Entity\Repository\PriceListToWebsiteRepository` changes:
    - changed the return type of `getWebsiteIteratorByDefaultFallback` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
* Class `Oro\Bundle\PricingBundle\Form\Type\PriceListSelectWithPriorityType` changes:
    - field `priority` was removed. Field `_position` from `Oro\Bundle\FormBundle\Form\Extension\SortableExtension` will be used instead.
* Class `Oro\Bundle\PricingBundle\Entity\BasePriceListRelation` changes:
    - property `$priority` was renamed to `$sortOrder`
    - methods `getPriority` and `setPriority` were renamed to `getSortOrder` and `setSortOrder` accordingly
* Class `Oro\Bundle\PricingBundle\SystemConfig\PriceListConfig` changes:
    - property `$priority` was renamed to `$sortOrder`
    - methods `getPriority` and `setPriority` were renamed to `getSortOrder` and `setSortOrder` accordingly
* Interface `Oro\Bundle\PricingBundle\Entity\PriceListAwareInterface` changes:
    - method `getPriority` was renamed to `getSortOrder`
* Class `Oro\Bundle\PricingBundle\SystemConfig\PriceListConfigConverter` changes:
    - constant `PRIORITY_KEY` was renamed to `SORT_ORDER_KEY`

ProductBundle
-------------
* The method [`ProductController::infoAction`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ProductBundle/Controller/Frontend/ProductController.php "Oro\Bundle\ProductBundle\Controller\Frontend\ProductController") was removed.
* The method [`ProductContentVariantReindexEventListener::onFormAfterFlush`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ProductBundle/EventListener/ProductContentVariantReindexEventListener.php "Oro\Bundle\ProductBundle\EventListener\ProductContentVariantReindexEventListener") was removed.
* The following methods in class [`ProductPageVariantType`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ProductBundle/Form/Type/ProductPageVariantType.php "Oro\Bundle\ProductBundle\Form\Type\ProductPageVariantType") were removed:
   - `__construct`
   - `configureOptions`
* The following methods in class [`ProductStrategy`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ProductBundle/ImportExport/Strategy/ProductStrategy.php "Oro\Bundle\ProductBundle\ImportExport\Strategy\ProductStrategy") were removed:
   - `combineIdentityValues`
   - `getInversedFieldName`
   - `updateRelations`
* The method [`ProductContentVariantReindexEventListener::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ProductBundle/EventListener/ProductContentVariantReindexEventListener.php "Oro\Bundle\ProductBundle\EventListener\ProductContentVariantReindexEventListener") has been updated. Pass [`FieldUpdatesChecker`](https://github.com/orocrm/platform/tree/2.1.0/src/Oro/Component/DoctrineUtils/ORM/FieldUpdatesChecker.php "Oro\Component\DoctrineUtils\ORM\FieldUpdatesChecker") as a second argument of the method. Pass [`FieldUpdatesChecker`](https://github.com/orocrm/platform/tree/2.1.0/src/Oro/Component/DoctrineUtils/ORM/FieldUpdatesChecker.php "Oro\Component\DoctrineUtils\ORM\FieldUpdatesChecker") as a second argument of the method instead of `mixed`. Pass [`WebCatalogUsageProviderInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Component/WebCatalog/Provider/WebCatalogUsageProviderInterface.php "Oro\Component\WebCatalog\Provider\WebCatalogUsageProviderInterface") as a third argument of the method. Pass [`WebCatalogUsageProviderInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Component/WebCatalog/Provider/WebCatalogUsageProviderInterface.php "Oro\Component\WebCatalog\Provider\WebCatalogUsageProviderInterface") as a third argument of the method instead of `mixed`.
* The method [`ProductType::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ProductBundle/Form/Type/ProductType.php "Oro\Bundle\ProductBundle\Form\Type\ProductType") has been updated. Pass `Symfony\Component\Routing\Generator\UrlGeneratorInterface` as a second argument of the method. Pass `Symfony\Component\Routing\Generator\UrlGeneratorInterface` as a second argument of the method instead of `mixed`.
* The method [`FeaturedProductsProvider::getAll`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ProductBundle/Layout/DataProvider/FeaturedProductsProvider.php "Oro\Bundle\ProductBundle\Layout\DataProvider\FeaturedProductsProvider") has been updated. Pass `mixed` as a first argument of the method.
* The method [`FrontendVariantFiledType::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ProductBundle/ProductVariant/Form/Type/FrontendVariantFiledType.php "Oro\Bundle\ProductBundle\ProductVariant\Form\Type\FrontendVariantFiledType") has been updated. Pass [`CustomFieldProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ProductBundle/Provider/CustomFieldProvider.php "Oro\Bundle\ProductBundle\Provider\CustomFieldProvider") as a third argument of the method instead of `Symfony\Component\PropertyAccess\PropertyAccessor`. Pass `Symfony\Component\PropertyAccess\PropertyAccessor` as a fourth argument of the method instead of `mixed`. Pass `mixed` as a fifth argument of the method.
* The method [`ProductExtension::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ProductBundle/Twig/ProductExtension.php "Oro\Bundle\ProductBundle\Twig\ProductExtension") has been updated. Pass `Symfony\Component\DependencyInjection\ContainerInterface` as a first argument of the method instead of [`AutocompleteFieldsProvider`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ProductBundle/Expression/Autocomplete/AutocompleteFieldsProvider.php "Oro\Bundle\ProductBundle\Expression\Autocomplete\AutocompleteFieldsProvider").
* The method [`ProductUnitFieldsSettingsExtension::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ProductBundle/Twig/ProductUnitFieldsSettingsExtension.php "Oro\Bundle\ProductBundle\Twig\ProductUnitFieldsSettingsExtension") has been updated. Pass `Symfony\Component\DependencyInjection\ContainerInterface` as a first argument of the method instead of [`ProductUnitFieldsSettingsInterface`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ProductBundle/Visibility/ProductUnitFieldsSettingsInterface.php "Oro\Bundle\ProductBundle\Visibility\ProductUnitFieldsSettingsInterface").
* The method [`ProductUnitLabelExtension::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ProductBundle/Twig/ProductUnitLabelExtension.php "Oro\Bundle\ProductBundle\Twig\ProductUnitLabelExtension") has been updated. Pass `Symfony\Component\DependencyInjection\ContainerInterface` as a first argument of the method instead of [`ProductUnitLabelFormatter`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ProductBundle/Formatter/ProductUnitLabelFormatter.php "Oro\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter").
* The method [`ProductUnitValueExtension::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ProductBundle/Twig/ProductUnitValueExtension.php "Oro\Bundle\ProductBundle\Twig\ProductUnitValueExtension") has been updated. Pass `Symfony\Component\DependencyInjection\ContainerInterface` as a first argument of the method instead of [`UnitValueFormatterInterface`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ProductBundle/Formatter/UnitValueFormatterInterface.php "Oro\Bundle\ProductBundle\Formatter\UnitValueFormatterInterface").
* The method [`UnitVisibilityExtension::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ProductBundle/Twig/UnitVisibilityExtension.php "Oro\Bundle\ProductBundle\Twig\UnitVisibilityExtension") has been updated. Pass `Symfony\Component\DependencyInjection\ContainerInterface` as a first argument of the method instead of [`UnitVisibilityInterface`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ProductBundle/Visibility/UnitVisibilityInterface.php "Oro\Bundle\ProductBundle\Visibility\UnitVisibilityInterface").
* Class `Oro\Bundle\ProductBundle\Twig\ProductExtension` changes:
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
* Class `Oro\Bundle\ProductBundle\Twig\ProductUnitFieldsSettingsExtension` changes:
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $productUnitFieldsSettings`
* Class `Oro\Bundle\ProductBundle\Twig\ProductUnitLabelExtension` changes:
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $formatter`
* Class `Oro\Bundle\ProductBundle\Twig\ProductUnitValueExtension` changes:
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $formatter`
* Class `Oro\Bundle\ProductBundle\Twig\UnitVisibilityExtension` changes:
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $unitVisibility`
* Added classes that can decorate `Oro\Bundle\ProductBundle\Entity\Product` to have virtual fields:
    - `Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecoratorFactory` is the class that should be used to create a decorated `Product`
    - `Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecorator` is the class that decorates `Product`
    - `Oro\Bundle\ProductBundle\VirtualFields\QueryDesigner\VirtualFieldsSelectQueryConverter` this converter is used inside of `VirtualFieldsProductDecorator`
    - `Oro\Bundle\ProductBundle\VirtualFields\QueryDesigner\VirtualFieldsProductQueryDesigner` this query designer is used inside of `VirtualFieldsProductDecorator`
* Removed constructor of `Oro\Bundle\ProductBundle\Form\Type\ProductPageVariantType`.
    - corresponding logic moved to `Oro\Bundle\WebCatalogBundle\Form\Extension\PageVariantTypeExtension`

RFPBundle
---------
* The following classes were removed:
    - [`Duplicate`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/RFPBundle/Action/Duplicate.php "Oro\Bundle\RFPBundle\Action\Duplicate")
    - [`RequestStatusController`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/RFPBundle/Controller/Api/Rest/RequestStatusController.php "Oro\Bundle\RFPBundle\Controller\Api\Rest\RequestStatusController")
    - [`RequestStatusController`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/RFPBundle/Controller/RequestStatusController.php "Oro\Bundle\RFPBundle\Controller\RequestStatusController")
    - [`ActionPermissionProvider`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/RFPBundle/Datagrid/ActionPermissionProvider.php "Oro\Bundle\RFPBundle\Datagrid\ActionPermissionProvider")
    - [`DuplicatorFilterPass`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/RFPBundle/DependencyInjection/CompilerPass/DuplicatorFilterPass.php "Oro\Bundle\RFPBundle\DependencyInjection\CompilerPass\DuplicatorFilterPass")
    - [`DuplicatorMatcherPass`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/RFPBundle/DependencyInjection/CompilerPass/DuplicatorMatcherPass.php "Oro\Bundle\RFPBundle\DependencyInjection\CompilerPass\DuplicatorMatcherPass")
    - [`RequestStatusRepository`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/RFPBundle/Entity/Repository/RequestStatusRepository.php "Oro\Bundle\RFPBundle\Entity\Repository\RequestStatusRepository")
    - [`RequestStatus`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/RFPBundle/Entity/RequestStatus.php "Oro\Bundle\RFPBundle\Entity\RequestStatus")
    - [`RequestStatusTranslation`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/RFPBundle/Entity/RequestStatusTranslation.php "Oro\Bundle\RFPBundle\Entity\RequestStatusTranslation")
    - [`DuplicatorFactory`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/RFPBundle/Factory/DuplicatorFactory.php "Oro\Bundle\RFPBundle\Factory\DuplicatorFactory")
    - [`RequestStatusHandler`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/RFPBundle/Form/Handler/RequestStatusHandler.php "Oro\Bundle\RFPBundle\Form\Handler\RequestStatusHandler")
    - [`DefaulRequestStatusType`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/RFPBundle/Form/Type/DefaulRequestStatusType.php "Oro\Bundle\RFPBundle\Form\Type\DefaulRequestStatusType")
    - [`RequestStatusSelectType`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/RFPBundle/Form/Type/RequestStatusSelectType.php "Oro\Bundle\RFPBundle\Form\Type\RequestStatusSelectType")
    - [`RequestStatusTranslationType`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/RFPBundle/Form/Type/RequestStatusTranslationType.php "Oro\Bundle\RFPBundle\Form\Type\RequestStatusTranslationType")
    - [`RequestStatusType`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/RFPBundle/Form/Type/RequestStatusType.php "Oro\Bundle\RFPBundle\Form\Type\RequestStatusType")
    - [`RequestStatusWithDeletedSelectType`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/RFPBundle/Form/Type/RequestStatusWithDeletedSelectType.php "Oro\Bundle\RFPBundle\Form\Type\RequestStatusWithDeletedSelectType")
* The method [`RequestController::getDefaultRequestStatus`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/RFPBundle/Controller/Frontend/RequestController.php "Oro\Bundle\RFPBundle\Controller\Frontend\RequestController") was removed.
* The following methods in class [`Request`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/RFPBundle/Entity/Request.php "Oro\Bundle\RFPBundle\Entity\Request") were removed:
   - `getStatus`
   - `setStatus`
* The following methods in class [`CustomerViewListener`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/RFPBundle/EventListener/CustomerViewListener.php "Oro\Bundle\RFPBundle\EventListener\CustomerViewListener") were removed:
   - `__construct`
   - `addRequestForQuotesBlock`
   - `getEntityFromRequestId`
* The following methods in class [`RequestType`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/RFPBundle/Form/Type/Frontend/RequestType.php "Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestType") were removed:
   - `getDefaultRequestStatus`
   - `postSubmit`
   - `setRequestStatusClass`

RuleBundle
----------
* Added `Oro\Bundle\RuleBundle\Entity\RuleInterface` this interface should now be used for injection instead of `Rule` in bundles that implement `RuleBundle` functionality
* Added classes for handling enable/disable `Rule` actions - use them to define corresponding services
    * `Oro\Bundle\RuleBundle\Datagrid\Extension\MassAction\StatusMassActionHandler`
    * `Oro\Bundle\RuleBundle\Datagrid\Extension\MassAction\Actions\StatusEnableMassAction`
    * `Oro\Bundle\RuleBundle\Datagrid\RuleActionsVisibilityProvider`
* Added `RuleActionsVisibilityProvider` that should be used to define action visibility configuration in datagrids with `Rule` entity fields

RedirectBundle
-------------
* The following classes were removed:
    - [`RoutingCompilerPass`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/RedirectBundle/DependencyInjection/Compiler/RoutingCompilerPass.php "Oro\Bundle\RedirectBundle\DependencyInjection\Compiler\RoutingCompilerPass")
    - [`SlugGenerator`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/RedirectBundle/Generator/SlugGenerator.php "Oro\Bundle\RedirectBundle\Generator\SlugGenerator")
* The following methods in class [`Redirect`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/RedirectBundle/Entity/Redirect.php "Oro\Bundle\RedirectBundle\Entity\Redirect") were removed:
   - `getWebsite`
   - `setWebsite`
* The method [`RedirectRepository::findByFrom`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/RedirectBundle/Entity/Repository/RedirectRepository.php "Oro\Bundle\RedirectBundle\Entity\Repository\RedirectRepository") was removed.
* The method [`Slug::getSlugUrl`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/RedirectBundle/Entity/Slug.php "Oro\Bundle\RedirectBundle\Entity\Slug") was removed.
* The following methods in class [`SlugEntityGenerator`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/RedirectBundle/Generator/SlugEntityGenerator.php "Oro\Bundle\RedirectBundle\Generator\SlugEntityGenerator") were removed:
   - `getLocalizationId`
   - `getSlugUrls`
* The method [`DirectUrlMessageFactory::getEntityFromMessage`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/RedirectBundle/Model/DirectUrlMessageFactory.php "Oro\Bundle\RedirectBundle\Model\DirectUrlMessageFactory") was removed.
* The method [`SlugUrlMatcher::getSlug`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/RedirectBundle/Routing/SlugUrlMatcher.php "Oro\Bundle\RedirectBundle\Routing\SlugUrlMatcher") was removed.
* The method [`DirectUrlProcessor::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RedirectBundle/Async/DirectUrlProcessor.php "Oro\Bundle\RedirectBundle\Async\DirectUrlProcessor") has been updated. Pass [`UrlStorageCache`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RedirectBundle/Cache/UrlStorageCache.php "Oro\Bundle\RedirectBundle\Cache\UrlStorageCache") as a 6 argument of the method. Pass [`UrlStorageCache`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RedirectBundle/Cache/UrlStorageCache.php "Oro\Bundle\RedirectBundle\Cache\UrlStorageCache") as a 6 argument of the method instead of `mixed`.
* The method [`SlugUrl::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RedirectBundle/Generator/DTO/SlugUrl.php "Oro\Bundle\RedirectBundle\Generator\DTO\SlugUrl") has been updated. Pass `mixed` as a third argument of the method.
* The method [`SlugEntityGenerator::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RedirectBundle/Generator/SlugEntityGenerator.php "Oro\Bundle\RedirectBundle\Generator\SlugEntityGenerator") has been updated. Pass [`UniqueSlugResolver`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RedirectBundle/Generator/UniqueSlugResolver.php "Oro\Bundle\RedirectBundle\Generator\UniqueSlugResolver") as a second argument of the method. Pass [`UniqueSlugResolver`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RedirectBundle/Generator/UniqueSlugResolver.php "Oro\Bundle\RedirectBundle\Generator\UniqueSlugResolver") as a second argument of the method instead of `mixed`. Pass [`RedirectGenerator`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RedirectBundle/Generator/RedirectGenerator.php "Oro\Bundle\RedirectBundle\Generator\RedirectGenerator") as a third argument of the method. Pass [`RedirectGenerator`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RedirectBundle/Generator/RedirectGenerator.php "Oro\Bundle\RedirectBundle\Generator\RedirectGenerator") as a third argument of the method instead of `mixed`. Pass [`UrlStorageCache`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RedirectBundle/Cache/UrlStorageCache.php "Oro\Bundle\RedirectBundle\Cache\UrlStorageCache") as a fourth argument of the method. Pass [`UrlStorageCache`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RedirectBundle/Cache/UrlStorageCache.php "Oro\Bundle\RedirectBundle\Cache\UrlStorageCache") as a fourth argument of the method instead of `mixed`.
* The method [`SlugEntityGenerator::generate`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RedirectBundle/Generator/SlugEntityGenerator.php "Oro\Bundle\RedirectBundle\Generator\SlugEntityGenerator") has been updated. Pass `mixed` as a second argument of the method.
* The method [`DirectUrlMessageFactory::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RedirectBundle/Model/DirectUrlMessageFactory.php "Oro\Bundle\RedirectBundle\Model\DirectUrlMessageFactory") has been updated. Pass [`ConfigManager`](https://github.com/orocrm/platform/tree/2.1.0/src/Oro/Bundle/ConfigBundle/Config/ConfigManager.php "Oro\Bundle\ConfigBundle\Config\ConfigManager") as a second argument of the method. Pass [`ConfigManager`](https://github.com/orocrm/platform/tree/2.1.0/src/Oro/Bundle/ConfigBundle/Config/ConfigManager.php "Oro\Bundle\ConfigBundle\Config\ConfigManager") as a second argument of the method instead of `mixed`.
* The method [`RoutingInformationProvider::registerProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RedirectBundle/Provider/RoutingInformationProvider.php "Oro\Bundle\RedirectBundle\Provider\RoutingInformationProvider") has been updated. Pass `mixed` as a second argument of the method.
* The method [`SlugUrlMatcher::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RedirectBundle/Routing/SlugUrlMatcher.php "Oro\Bundle\RedirectBundle\Routing\SlugUrlMatcher") has been updated. Pass `Symfony\Component\Routing\RouterInterface` as a first argument of the method instead of `mixed`. Pass [`SlugRepository`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RedirectBundle/Entity/Repository/SlugRepository.php "Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository") as a second argument of the method instead of `Symfony\Component\Routing\RouterInterface`. Pass [`ScopeManager`](https://github.com/orocrm/platform/tree/2.1.0/src/Oro/Bundle/ScopeBundle/Manager/ScopeManager.php "Oro\Bundle\ScopeBundle\Manager\ScopeManager") as a third argument of the method instead of `Doctrine\Common\Persistence\ManagerRegistry`. Pass [`MatchedUrlDecisionMaker`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RedirectBundle/Routing/MatchedUrlDecisionMaker.php "Oro\Bundle\RedirectBundle\Routing\MatchedUrlDecisionMaker") as a fourth argument of the method instead of [`ScopeManager`](https://github.com/orocrm/platform/tree/2.0.0/src/Oro/Bundle/ScopeBundle/Manager/ScopeManager.php "Oro\Bundle\ScopeBundle\Manager\ScopeManager").
* `Oro\Bundle\RedirectBundle\Entity\Redirect` changes:
    - removed property `website` in favour of `scopes` collection using

SEOBundle
---------
* The method [`ProductSearchIndexListener::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/SEOBundle/EventListener/ProductSearchIndexListener.php "Oro\Bundle\SEOBundle\EventListener\ProductSearchIndexListener") has been updated. Pass [`DoctrineHelper`](https://github.com/orocrm/platform/tree/2.1.0/src/Oro/Bundle/EntityBundle/ORM/DoctrineHelper.php "Oro\Bundle\EntityBundle\ORM\DoctrineHelper") as a first argument of the method instead of [`AbstractWebsiteLocalizationProvider`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/WebsiteBundle/Provider/AbstractWebsiteLocalizationProvider.php "Oro\Bundle\WebsiteBundle\Provider\AbstractWebsiteLocalizationProvider"). Pass [`AbstractWebsiteLocalizationProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebsiteBundle/Provider/AbstractWebsiteLocalizationProvider.php "Oro\Bundle\WebsiteBundle\Provider\AbstractWebsiteLocalizationProvider") as a second argument of the method instead of [`WebsiteContextManager`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/WebsiteSearchBundle/Manager/WebsiteContextManager.php "Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager"). Pass [`WebsiteContextManager`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebsiteSearchBundle/Manager/WebsiteContextManager.php "Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager") as a third argument of the method. Pass [`WebsiteContextManager`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebsiteSearchBundle/Manager/WebsiteContextManager.php "Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager") as a third argument of the method instead of `mixed`.

SaleBundle
----------
* The class [`QuotePossibleShippingMethodsEventListener`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/SaleBundle/EventListener/Quote/QuotePossibleShippingMethodsEventListener.php "Oro\Bundle\SaleBundle\EventListener\Quote\QuotePossibleShippingMethodsEventListener") was removed.
* The method [`QuoteController::infoAction`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/SaleBundle/Controller/Frontend/QuoteController.php "Oro\Bundle\SaleBundle\Controller\Frontend\QuoteController") was removed.
* The following methods in class [`QuoteController`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/SaleBundle/Controller/QuoteController.php "Oro\Bundle\SaleBundle\Controller\QuoteController") were removed:
   - `getQuoteAddressSecurityProvider`
   - `getQuoteHandler`
   - `getQuoteProductPriceProvider`
* The following methods in class [`Quote`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/SaleBundle/Entity/Quote.php "Oro\Bundle\SaleBundle\Entity\Quote") were removed:
   - `isLocked`
   - `setLocked`
* The following methods in class [`CustomerViewListener`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/SaleBundle/EventListener/CustomerViewListener.php "Oro\Bundle\SaleBundle\EventListener\CustomerViewListener") were removed:
   - `__construct`
   - `addRequestForQuotesBlock`
   - `getEntityFromRequestId`
   - `onCustomerUserView`
   - `onCustomerView`
* The method [`NotificationHelper::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/SaleBundle/Notification/NotificationHelper.php "Oro\Bundle\SaleBundle\Notification\NotificationHelper") has been updated. Pass [`EmailModelBuilder`](https://github.com/orocrm/platform/tree/2.1.0/src/Oro/Bundle/EmailBundle/Builder/EmailModelBuilder.php "Oro\Bundle\EmailBundle\Builder\EmailModelBuilder") as a second argument of the method instead of `Symfony\Component\HttpFoundation\Request`. Pass [`Processor`](https://github.com/orocrm/platform/tree/2.1.0/src/Oro/Bundle/EmailBundle/Mailer/Processor.php "Oro\Bundle\EmailBundle\Mailer\Processor") as a third argument of the method instead of [`EmailModelBuilder`](https://github.com/orocrm/platform/tree/2.0.0/src/Oro/Bundle/EmailBundle/Builder/EmailModelBuilder.php "Oro\Bundle\EmailBundle\Builder\EmailModelBuilder").
* The method [`ShippingCostQuoteDemandSubtotalsCalculatorDecorator::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/SaleBundle/Quote/Demand/Subtotals/Calculator/Decorator/ShippingCost/ShippingCostQuoteDemandSubtotalsCalculatorDecorator.php "Oro\Bundle\SaleBundle\Quote\Demand\Subtotals\Calculator\Decorator\ShippingCost\ShippingCostQuoteDemandSubtotalsCalculatorDecorator") has been updated. Pass [`ShippingContextFactoryInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ShippingBundle/Context/ShippingContextFactoryInterface.php "Oro\Bundle\ShippingBundle\Context\ShippingContextFactoryInterface") as a first argument of the method instead of [`QuoteShippingContextFactoryInterface`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/SaleBundle/Quote/Shipping/Context/Factory/QuoteShippingContextFactoryInterface.php "Oro\Bundle\SaleBundle\Quote\Shipping\Context\Factory\QuoteShippingContextFactoryInterface").
* The method [`BasicQuoteShippingContextFactory::create`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/SaleBundle/Quote/Shipping/Context/Factory/Basic/BasicQuoteShippingContextFactory.php "Oro\Bundle\SaleBundle\Quote\Shipping\Context\Factory\Basic\BasicQuoteShippingContextFactory") has been updated. Pass `mixed` as a first argument of the method instead of [`Quote`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/SaleBundle/Entity/Quote.php "Oro\Bundle\SaleBundle\Entity\Quote").
* The method [`QuoteExtension::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/SaleBundle/Twig/QuoteExtension.php "Oro\Bundle\SaleBundle\Twig\QuoteExtension") has been updated. Pass `Symfony\Component\DependencyInjection\ContainerInterface` as a first argument of the method instead of [`QuoteProductFormatter`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/SaleBundle/Formatter/QuoteProductFormatter.php "Oro\Bundle\SaleBundle\Formatter\QuoteProductFormatter").
* The [`QuoteExtension`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/SaleBundle/Twig/QuoteExtension.php "Oro\Bundle\SaleBundle\Twig\QuoteExtension") class changed:
  - The construction signature of was changed and the constructor was updated to have only one parameter: `ContainerInterface $container`
  - The `protected $quoteProductFormatter` and `protected $configManager` properties were removed.
* The [`QuotePossibleShippingMethodsEventListener`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/SaleBundle/EventListener/Quote/QuotePossibleShippingMethodsEventListener "Oro\Bundle\SaleBundle\EventListener\Quote\QuotePossibleShippingMethodsEventListener") was removed. Use [`PossibleShippingMethodEventListener`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/OrderBundle/EventListener/PossibleShippingMethodEventListener "Oro\Bundle\OrderBundle\EventListener\PossibleShippingMethodEventListener") instead.
* Removed property `locked` from entity class `Oro\Bundle\SaleBundle\Entity\Quote` with related methods
* Class `Oro\Bundle\SaleBundle\Notification\NotificationHelper`
  - removed parameter `request` from constructor

ShippingBundle
--------------

* The classes that are related to actions that disable/enable [`ShippingMethodsConfigsRule`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/Entity/ShippingMethodsConfigsRule.php "Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule") were abstracted and moved to the [`RuleBundle`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle "Oro\Bundle\RuleBundle") (see the [`RuleBundle`](#RuleBundle)) section for more information):
  - Removed [`StatusDisableMassAction`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/Datagrid/Extension/MassAction/Actions/StatusDisableMassAction.php "Oro\Bundle\ShippingBundle\Datagrid\Extension\MassAction\Actions\StatusDisableMassAction") and switched definition to [`StatusEnableMassAction`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle/Datagrid/Extension/MassAction/Actions/StatusEnableMassAction.php "Oro\Bundle\RuleBundle\Datagrid\Extension\MassAction\Actions\StatusEnableMassAction") in the [`RuleBundle`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle "Oro\Bundle\RuleBundle")
  - [`StatusEnableMassAction`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/Datagrid/Extension/MassAction/Actions/StatusEnableMassAction.php "Oro\Bundle\ShippingBundle\Datagrid\Extension\MassAction\Actions\StatusEnableMassAction") and switched definition to [`StatusEnableMassAction`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle/Datagrid/Extension/MassAction/Actions/StatusEnableMassAction.php "Oro\Bundle\RuleBundle\Datagrid\Extension\MassAction\Actions\StatusEnableMassAction") in the [`RuleBundle`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle "Oro\Bundle\RuleBundle")
  - [`StatusMassActionHandler`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/Datagrid/Extension/MassAction/StatusMassActionHandler.php "Oro\Bundle\ShippingBundle\Datagrid\Extension\MassAction\StatusMassActionHandler") and switched definition to [`StatusMassActionHandler`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle/Datagrid/Extension/MassAction/StatusMassActionHandler.php "Oro\Bundle\RuleBundle\Datagrid\Extension\MassAction\StatusMassActionHandler") in the [`RuleBundle`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle "Oro\Bundle\RuleBundle")
  - [`ShippingRuleActionsVisibilityProvider`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/Datagrid/ShippingRuleActionsVisibilityProvider.php "Oro\Bundle\ShippingBundle\Datagrid\ShippingRuleActionsVisibilityProvider") and switched definition to [`RuleActionsVisibilityProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle/Datagrid/RuleActionsVisibilityProvider.php "Oro\Bundle\RuleBundle\Datagrid\RuleActionsVisibilityProvider") in the [`RuleBundle`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle "Oro\Bundle\RuleBundle")
* The following classes that are related to decorating [`Product`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ProductBundle/Entity/Product.php "Oro\Bundle\ProductBundle\Entity\Product") with virtual fields) were abstracted and moved to the [`ProductBundle`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ProductBundle "Oro\Bundle\ProductBundle") (see the [`ProductBundle`](#ProductBundle) section for more information):
  - [`SelectQueryConverter`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/QueryDesigner/SelectQueryConverter.php "Oro\Bundle\ShippingBundle\QueryDesigner\SelectQueryConverter") 
  - [`ShippingProductQueryDesigner`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/QueryDesigner/ShippingProductQueryDesigner.php "Oro\Bundle\ShippingBundle\QueryDesigner\ShippingProductQueryDesigner") 
  - [`ProductDecorator`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/ExpressionLanguage/ProductDecorator.php "Oro\Bundle\ShippingBundle\ExpressionLanguage\ProductDecorator")
  - In the [`DecoratedProductLineItemFactory`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ShippingBundle/ExpressionLanguage/DecoratedProductLineItemFactory.php "Oro\Bundle\ShippingBundle\ExpressionLanguage\DecoratedProductLineItemFactory") class, the only dependency is now 
[`VirtualFieldsProductDecoratorFactory`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ProductBundle/VirtualFields/VirtualFieldsProductDecoratorFactory.php "Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecoratorFactory").
  - [`AbstractIntegrationRemovalListener`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/Method/EventListener/AbstractIntegrationRemovalListener.php "Oro\Bundle\ShippingBundle\Method\EventListener\AbstractIntegrationRemovalListener") was deprecated, [`IntegrationRemovalListener`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/Method/EventListener/IntegrationRemovalListener.php "Oro\Bundle\ShippingBundle\Method\EventListener\IntegrationRemovalListener") was created instead.
* The following classes were removed:
  - [`StatusDisableMassAction`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/Datagrid/Extension/MassAction/Actions/StatusDisableMassAction.php "Oro\Bundle\ShippingBundle\Datagrid\Extension\MassAction\Actions\StatusDisableMassAction")
  - [`StatusEnableMassAction`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/Datagrid/Extension/MassAction/Actions/StatusEnableMassAction.php "Oro\Bundle\ShippingBundle\Datagrid\Extension\MassAction\Actions\StatusEnableMassAction")
  - [`StatusMassActionHandler`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/Datagrid/Extension/MassAction/StatusMassActionHandler.php "Oro\Bundle\ShippingBundle\Datagrid\Extension\MassAction\StatusMassActionHandler")
  - [`ShippingRuleActionsVisibilityProvider`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/Datagrid/ShippingRuleActionsVisibilityProvider.php "Oro\Bundle\ShippingBundle\Datagrid\ShippingRuleActionsVisibilityProvider")
  - [`ProductDecorator`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/ExpressionLanguage/ProductDecorator.php "Oro\Bundle\ShippingBundle\ExpressionLanguage\ProductDecorator")
  - [`DestinationCollectionTypeSubscriber`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/Form/EventSubscriber/DestinationCollectionTypeSubscriber.php "Oro\Bundle\ShippingBundle\Form\EventSubscriber\DestinationCollectionTypeSubscriber")
  - [`SelectQueryConverter`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/QueryDesigner/SelectQueryConverter.php "Oro\Bundle\ShippingBundle\QueryDesigner\SelectQueryConverter")
  - [`ShippingProductQueryDesigner`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/QueryDesigner/ShippingProductQueryDesigner.php "Oro\Bundle\ShippingBundle\QueryDesigner\ShippingProductQueryDesigner")
* The following methods were removed:
  - [`ShippingMethodTypeConfigRepository::deleteByMethodAndType`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/Entity/Repository/ShippingMethodTypeConfigRepository.php "Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodTypeConfigRepository")
  - [`ShippingMethodsConfigsRuleType::getMethods`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/Form/Type/ShippingMethodsConfigsRuleType.php "Oro\Bundle\ShippingBundle\Form\Type\ShippingMethodsConfigsRuleType")
* The following methods were updated:
  - [`HasApplicableShippingMethods::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ShippingBundle/Condition/HasApplicableShippingMethods.php "Oro\Bundle\ShippingBundle\Condition\HasApplicableShippingMethods"):
    + Pass [`ShippingPriceProviderInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ShippingBundle/Provider/Price/ShippingPriceProviderInterface.php "Oro\Bundle\ShippingBundle\Provider\Price\ShippingPriceProviderInterface") as a second argument of the method instead of [`ShippingPriceProvider`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/Provider/ShippingPriceProvider.php "Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider").
  - [`ShippingRuleChangeListener::isShippingRule`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ShippingBundle/EventListener/Cache/ShippingRuleChangeListener.php "Oro\Bundle\ShippingBundle\EventListener\Cache\ShippingRuleChangeListener"):
    + Pass [`RuleInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle/Entity/RuleInterface.php "Oro\Bundle\RuleBundle\Entity\RuleInterface") as a first argument of the method instead of [`Rule`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/RuleBundle/Entity/Rule.php "Oro\Bundle\RuleBundle\Entity\Rule").
  - [`DecoratedProductLineItemFactory::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ShippingBundle/ExpressionLanguage/DecoratedProductLineItemFactory.php "Oro\Bundle\ShippingBundle\ExpressionLanguage\DecoratedProductLineItemFactory"):
    + Pass [`VirtualFieldsProductDecoratorFactory`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ProductBundle/VirtualFields/VirtualFieldsProductDecoratorFactory.php "Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecoratorFactory") as a first argument of the method instead of [`EntityFieldProvider`](https://github.com/orocrm/platform/tree/2.0.0/src/Oro/Bundle/EntityBundle/Provider/EntityFieldProvider.php "Oro\Bundle\EntityBundle\Provider\EntityFieldProvider").
  - [`ShippingMethodsConfigsRuleType::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ShippingBundle/Form/Type/ShippingMethodsConfigsRuleType.php "Oro\Bundle\ShippingBundle\Form\Type\ShippingMethodsConfigsRuleType"):
    + Pass [`ShippingMethodChoicesProviderInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ShippingBundle/Provider/ShippingMethodChoicesProviderInterface.php "Oro\Bundle\ShippingBundle\Provider\ShippingMethodChoicesProviderInterface") as a first argument of the method instead of [`ShippingMethodRegistry`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/Method/ShippingMethodRegistry.php "Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry").
  - [`ShippingMethodsProvider::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ShippingBundle/Layout/DataProvider/ShippingMethodsProvider.php "Oro\Bundle\ShippingBundle\Layout\DataProvider\ShippingMethodsProvider"):
    + Pass [`ShippingPriceProviderInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ShippingBundle/Provider/Price/ShippingPriceProviderInterface.php "Oro\Bundle\ShippingBundle\Provider\Price\ShippingPriceProviderInterface") as a first argument of the method instead of [`ShippingPriceProvider`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/Provider/ShippingPriceProvider.php "Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider").
  - [`DimensionsUnitValueExtension::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ShippingBundle/Twig/DimensionsUnitValueExtension.php "Oro\Bundle\ShippingBundle\Twig\DimensionsUnitValueExtension"):
    + Pass `Symfony\Component\DependencyInjection\ContainerInterface` as a first argument of the method instead of [`UnitValueFormatter`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ProductBundle/Formatter/UnitValueFormatter.php "Oro\Bundle\ProductBundle\Formatter\UnitValueFormatter").
  - [`ShippingMethodExtension::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ShippingBundle/Twig/ShippingMethodExtension.php "Oro\Bundle\ShippingBundle\Twig\ShippingMethodExtension"):
    + Pass [`ShippingMethodEnabledByIdentifierCheckerInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ShippingBundle/Checker/ShippingMethodEnabledByIdentifierCheckerInterface.php "Oro\Bundle\ShippingBundle\Checker\ShippingMethodEnabledByIdentifierCheckerInterface") as a third argument of the method. Pass [`ShippingMethodEnabledByIdentifierCheckerInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ShippingBundle/Checker/ShippingMethodEnabledByIdentifierCheckerInterface.php "Oro\Bundle\ShippingBundle\Checker\ShippingMethodEnabledByIdentifierCheckerInterface") as a third argument of the method instead of `mixed`.
  - [`ShippingOptionLabelExtension::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ShippingBundle/Twig/ShippingOptionLabelExtension.php "Oro\Bundle\ShippingBundle\Twig\ShippingOptionLabelExtension"):
    + Pass `Symfony\Component\DependencyInjection\ContainerInterface` as a first argument of the method instead of [`UnitLabelFormatter`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ProductBundle/Formatter/UnitLabelFormatter.php "Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatter").
  - [`WeightUnitValueExtension::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ShippingBundle/Twig/WeightUnitValueExtension.php "Oro\Bundle\ShippingBundle\Twig\WeightUnitValueExtension")
    + Pass `Symfony\Component\DependencyInjection\ContainerInterface` as a first argument of the method instead of [`UnitValueFormatter`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ProductBundle/Formatter/UnitValueFormatter.php "Oro\Bundle\ProductBundle\Formatter\UnitValueFormatter").

ShoppingListBundle
------------------
* In [`ShoppingListTotalRepository`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ShoppingListBundle/Entity/Repository/ShoppingListTotalRepository.php "Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListTotalRepository") class, the signature of `invalidateTotals` method canged from `invalidateTotals(BufferedQueryResultIterator $iterator)` to `invalidateTotals(\Iterator $iterator)`
* The [`DimensionsUnitValueExtension`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ShippingBundle/Twig/DimensionsUnitValueExtension.php "Oro\Bundle\ShippingBundle\Twig\DimensionsUnitValueExtension") class changed:
    - The construction signature of was changed and the constructor was updated to have only one parameter: `ContainerInterface $container`
    - The `protected $formatter` property was removed
* The [`ShippingMethodExtension`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ShippingBundle/Twig/ShippingMethodExtension.php "Oro\Bundle\ShippingBundle\Twig\ShippingMethodExtension") class changed:
    - The construction signature of was changed and the constructor was updated to have only one parameter: `ContainerInterface $container`
    - The `protected $shippingMethodLabelFormatter` and `protected $dispatcher` properties were removed.
* The [`ShippingOptionLabelExtension`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ShippingBundle/Twig/ShippingOptionLabelExtension.php "Oro\Bundle\ShippingBundle\Twig\ShippingOptionLabelExtension") class changed:
    - The construction signature of was changed and the constructor was updated to have only one parameter: `ContainerInterface $container`
    - The following properties were removed:
      + `protected $lengthUnitLabelFormatter`
      + `protected $weightUnitLabelFormatter`
      + `protected $freightClassLabelFormatter`
* The [`WeightUnitValueExtension`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ShippingBundle/Twig/WeightUnitValueExtension.php "Oro\Bundle\ShippingBundle\Twig\WeightUnitValueExtension") class changed:
    - The construction signature of was changed and the constructor was updated to have only one parameter: `ContainerInterface $container`
    - The `protected $formatter` property was removed
* The following methods were removed: 
  - [`HasPriceInShoppingLineItems`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShoppingListBundle/Condition/HasPriceInShoppingLineItems.php "Oro\Bundle\ShoppingListBundle\Condition\HasPriceInShoppingLineItems")
  - [`AjaxLineItemController::getSuccessMessage`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShoppingListBundle/Controller/Frontend/AjaxLineItemController.php "Oro\Bundle\ShoppingListBundle\Controller\Frontend\AjaxLineItemController")
  - [`ShoppingListController::deleteAction`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShoppingListBundle/Controller/Frontend/Api/Rest/ShoppingListController.php "Oro\Bundle\ShoppingListBundle\Controller\Frontend\Api\Rest\ShoppingListController")
* The following methods were updated: 
  - [`ShoppingListTotalRepository::invalidateTotals`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ShoppingListBundle/Entity/Repository/ShoppingListTotalRepository.php "Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListTotalRepository"):
    + Pass `\Iterator` in the first argument instead of [`BufferedQueryResultIterator`](https://github.com/orocrm/platform/tree/2.0.0/src/Oro/Bundle/BatchBundle/ORM/Query/BufferedQueryResultIterator.php "Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator")
  - [`LineItemValidateEvent::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ShoppingListBundle/Event/LineItemValidateEvent.php "Oro\Bundle\ShoppingListBundle\Event\LineItemValidateEvent"):
    + Pass `mixed` in the second argument
  - [`LineItemErrorsProvider::getLineItemErrors`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ShoppingListBundle/Validator/LineItemErrorsProvider.php "Oro\Bundle\ShoppingListBundle\Validator\LineItemErrorsProvider"):
    + Pass `mixed` in the second argument

TaxBundle
---------
* The following methods were removed: 
  - [`AbstractTaxCode::prePersist`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/TaxBundle/Entity/AbstractTaxCode.php "Oro\Bundle\TaxBundle\Entity\AbstractTaxCode")
  - [`AbstractTaxCode::preUpdate`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/TaxBundle/Entity/AbstractTaxCode.php "Oro\Bundle\TaxBundle\Entity\AbstractTaxCode")
  - [`Tax::prePersist`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/TaxBundle/Entity/Tax.php "Oro\Bundle\TaxBundle\Entity\Tax")
  - [`Tax::preUpdate`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/TaxBundle/Entity/Tax.php "Oro\Bundle\TaxBundle\Entity\Tax")
  - [`TaxJurisdiction::prePersist`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/TaxBundle/Entity/TaxJurisdiction.php "Oro\Bundle\TaxBundle\Entity\TaxJurisdiction")
  - [`TaxJurisdiction::preUpdate`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/TaxBundle/Entity/TaxJurisdiction.php "Oro\Bundle\TaxBundle\Entity\TaxJurisdiction")
  - [`TaxRule::prePersist`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/TaxBundle/Entity/TaxRule.php "Oro\Bundle\TaxBundle\Entity\TaxRule")
  - [`TaxRule::preUpdate`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/TaxBundle/Entity/TaxRule.php "Oro\Bundle\TaxBundle\Entity\TaxRule")
  - [`ZipCode::prePersist`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/TaxBundle/Entity/ZipCode.php "Oro\Bundle\TaxBundle\Entity\ZipCode")
  - [`ZipCode::preUpdate`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/TaxBundle/Entity/ZipCode.php "Oro\Bundle\TaxBundle\Entity\ZipCode")
  - [`OrderHandler::getRepository`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/TaxBundle/OrderTax/ContextHandler/OrderHandler.php "Oro\Bundle\TaxBundle\OrderTax\ContextHandler\OrderHandler")
  - [`OrderLineItemHandler::getRepository`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/TaxBundle/OrderTax/ContextHandler/OrderLineItemHandler.php "Oro\Bundle\TaxBundle\OrderTax\ContextHandler\OrderLineItemHandler")

* The following methods were updated: 
  - [`AbstractTaxCode::setCreatedAt`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/TaxBundle/Entity/AbstractTaxCode.php "Oro\Bundle\TaxBundle\Entity\AbstractTaxCode") (pass `\DateTime` as a first argument of the method instead of `mixed`)
  - [`AbstractTaxCode::setUpdatedAt`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/TaxBundle/Entity/AbstractTaxCode.php "Oro\Bundle\TaxBundle\Entity\AbstractTaxCode") (pass `\DateTime` as a first argument of the method instead of `mixed`)
  - [`Tax::setCreatedAt`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/TaxBundle/Entity/Tax.php "Oro\Bundle\TaxBundle\Entity\Tax") (pass `\DateTime` as a first argument of the method instead of `mixed`)
  - [`Tax::setUpdatedAt`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/TaxBundle/Entity/Tax.php "Oro\Bundle\TaxBundle\Entity\Tax") (pass `\DateTime` as a first argument of the method instead of `mixed`)
  - [`TaxJurisdiction::setCreatedAt`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/TaxBundle/Entity/TaxJurisdiction.php "Oro\Bundle\TaxBundle\Entity\TaxJurisdiction") (pass `\DateTime` as a first argument of the method instead of `mixed`)
  - [`TaxJurisdiction::setUpdatedAt`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/TaxBundle/Entity/TaxJurisdiction.php "Oro\Bundle\TaxBundle\Entity\TaxJurisdiction") (pass `\DateTime` as a first argument of the method instead of `mixed`)
  - [`TaxRule::setCreatedAt`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/TaxBundle/Entity/TaxRule.php "Oro\Bundle\TaxBundle\Entity\TaxRule") (pass `\DateTime` as a first argument of the method instead of `mixed`)
  - [`TaxRule::setUpdatedAt`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/TaxBundle/Entity/TaxRule.php "Oro\Bundle\TaxBundle\Entity\TaxRule") (pass `\DateTime` as a first argument of the method instead of `mixed`)
  - [`ZipCode::setCreatedAt`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/TaxBundle/Entity/ZipCode.php "Oro\Bundle\TaxBundle\Entity\ZipCode") (pass `\DateTime` as a first argument of the method instead of `mixed`)
  - [`ZipCode::setUpdatedAt`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/TaxBundle/Entity/ZipCode.php "Oro\Bundle\TaxBundle\Entity\ZipCode") (pass `\DateTime` as a first argument of the method instead of `mixed`)
  - [`OrderHandler::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/TaxBundle/OrderTax/ContextHandler/OrderHandler.php "Oro\Bundle\TaxBundle\OrderTax\ContextHandler\OrderHandler") (pass [`TaxCodeProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/TaxBundle/Provider/TaxCodeProvider.php "Oro\Bundle\TaxBundle\Provider\TaxCodeProvider") as a first argument of the method instead of [`DoctrineHelper`](https://github.com/orocrm/platform/tree/2.0.0/src/Oro/Bundle/EntityBundle/ORM/DoctrineHelper.php "Oro\Bundle\EntityBundle\ORM\DoctrineHelper"))
  - [`OrderLineItemHandler::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/TaxBundle/OrderTax/ContextHandler/OrderLineItemHandler.php "Oro\Bundle\TaxBundle\OrderTax\ContextHandler\OrderLineItemHandler") (pass [`TaxCodeProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/TaxBundle/Provider/TaxCodeProvider.php "Oro\Bundle\TaxBundle\Provider\TaxCodeProvider") as a second argument of the method instead of [`DoctrineHelper`](https://github.com/orocrm/platform/tree/2.0.0/src/Oro/Bundle/EntityBundle/ORM/DoctrineHelper.php "Oro\Bundle\EntityBundle\ORM\DoctrineHelper")).

UPSBundle
---------

* **Check UPS Connection** button was added on UPS integration page. Please, see [documentation](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Resources/doc/credentials-validation.md) for more information.
* The following classes and methods were removed:
  - [`UPSChannelEntityListener`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/UPSBundle/EventListener/UPSChannelEntityListener.php "Oro\Bundle\UPSBundle\EventListener\UPSChannelEntityListener")
  - [`UPSTransport::getBaseUrl`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/UPSBundle/Entity/UPSTransport.php "Oro\Bundle\UPSBundle\Entity\UPSTransport")
  - [`UPSTransport::setBaseUrl`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/UPSBundle/Entity/UPSTransport.php "Oro\Bundle\UPSBundle\Entity\UPSTransport")
  - [`UPSShippingMethod::fetchPrices`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/UPSBundle/Method/UPSShippingMethod.php "Oro\Bundle\UPSBundle\Method\UPSShippingMethod")
* The following methods were updated: 
  - [`UPSShippingMethod::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Method/UPSShippingMethod.php "Oro\Bundle\UPSBundle\Method\UPSShippingMethod"):
    + Pass `string $identifier` in the first argument instead of [`UPSTransport`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/UPSBundle/Provider/UPSTransport.php "Oro\Bundle\UPSBundle\Provider\UPSTransport")
    + Pass `string $label` in the second argument instead of [`Channel`](https://github.com/orocrm/platform/tree/2.0.0/src/Oro/Bundle/IntegrationBundle/Entity/Channel.php "Oro\Bundle\IntegrationBundle\Entity\Channel")
    + Pass `array $types` in the third argument instead of [`PriceRequestFactory`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/UPSBundle/Factory/PriceRequestFactory.php "Oro\Bundle\UPSBundle\Factory\PriceRequestFactory")
    + Pass [`UPSTransport`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Entity/UPSTransport.php "Oro\Bundle\UPSBundle\Entity\UPSTransport") entity in the fourth argument instead of [`LocalizationHelper`](https://github.com/orocrm/platform/tree/2.0.0/src/Oro/Bundle/LocaleBundle/Helper/LocalizationHelper.php "Oro\Bundle\LocaleBundle\Helper\LocalizationHelper").
    + Pass [`UPSTransport`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Provider/UPSTransport.php "Oro\Bundle\UPSBundle\Provider\UPSTransport") provider in the fifth argument instead of [`ShippingPriceCache`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/UPSBundle/Cache/ShippingPriceCache.php "Oro\Bundle\UPSBundle\Cache\ShippingPriceCache").
    + Pass [`PriceRequestFactory`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Factory/PriceRequestFactory.php "Oro\Bundle\UPSBundle\Factory\PriceRequestFactory") in the 6th argument
    + Pass [`ShippingPriceCache`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Cache/ShippingPriceCache.php "Oro\Bundle\UPSBundle\Cache\ShippingPriceCache") in the 7th argument
    + Pass `bool $enabled` in the 8th argument.         
  - [`UPSShippingMethodProvider::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Method/UPSShippingMethodProvider.php "Oro\Bundle\UPSBundle\Method\UPSShippingMethodProvider"):
    + Pass [`DoctrineHelper`](https://github.com/orocrm/platform/tree/2.1.0/src/Oro/Bundle/EntityBundle/ORM/DoctrineHelper.php "Oro\Bundle\EntityBundle\ORM\DoctrineHelper") in the first argument instead of [`UPSTransport`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/UPSBundle/Provider/UPSTransport.php "Oro\Bundle\UPSBundle\Provider\UPSTransport")
    + Pass [`IntegrationShippingMethodFactoryInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ShippingBundle/Method/Factory/IntegrationShippingMethodFactoryInterface.php "Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface") in the second argument instead of `Symfony\Bridge\Doctrine\ManagerRegistry`
  - [`UPSShippingMethodType::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Method/UPSShippingMethodType.php "Oro\Bundle\UPSBundle\Method\UPSShippingMethodType"):
    + Pass `mixed` in the second argument instead of [`UPSTransport`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/UPSBundle/Entity/UPSTransport.php "Oro\Bundle\UPSBundle\Entity\UPSTransport").
    + Pass `string $label` in the third argument instead of [`UPSTransport`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/UPSBundle/Provider/UPSTransport.php "Oro\Bundle\UPSBundle\Provider\UPSTransport").
    + Pass [`UPSTransport`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Entity/UPSTransport.php "Oro\Bundle\UPSBundle\Entity\UPSTransport") in the fifth argument instead of [`PriceRequestFactory`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/UPSBundle/Factory/PriceRequestFactory.php "Oro\Bundle\UPSBundle\Factory\PriceRequestFactory").
    + Pass [`UPSTransport`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Provider/UPSTransport.php "Oro\Bundle\UPSBundle\Provider\UPSTransport") in the 6th argument instead of [`ShippingPriceCache`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/UPSBundle/Cache/ShippingPriceCache.php "Oro\Bundle\UPSBundle\Cache\ShippingPriceCache").
    + Pass [`PriceRequestFactory`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Factory/PriceRequestFactory.php "Oro\Bundle\UPSBundle\Factory\PriceRequestFactory") in the 7th argument
    + Pass [`ShippingPriceCache`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Cache/ShippingPriceCache.php "Oro\Bundle\UPSBundle\Cache\ShippingPriceCache") in the 8th argument.
  - [`UPSTransport::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Provider/UPSTransport.php "Oro\Bundle\UPSBundle\Provider\UPSTransport"):
    + Pass [`UpsClientUrlProviderInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Client/Url/Provider/UpsClientUrlProviderInterface.php "Oro\Bundle\UPSBundle\Client\Url\Provider\UpsClientUrlProviderInterface") in the first argument instead of `Psr\Log\LoggerInterface`
* The class [`InvalidateCacheScheduleCommand`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/UPSBundle/Command/InvalidateCacheScheduleCommand.php "Oro\Bundle\UPSBundle\Command\InvalidateCacheScheduleCommand") was removed, [`InvalidateCacheScheduleCommand`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CacheBundle/Command/InvalidateCacheScheduleCommand.php "Oro\Bundle\CacheBundle\Command\InvalidateCacheScheduleCommand") should be used instead
* The class [`InvalidateCacheAtHandler`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/UPSBundle/Handler/InvalidateCacheAtHandler.php "Oro\Bundle\UPSBundle\Handler\InvalidateCacheAtHandler") was removed, [`InvalidateCacheActionHandler`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Handler/InvalidateCacheActionHandler.php "Oro\Bundle\UPSBundle\Handler\InvalidateCacheActionHandler") should be used instead
* Resource [`invalidateCache.html.twig`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/UPSBundle/Resources/views/Action/invalidateCache.html.twig "Oro\Bundle\UPSBundle\Resources\views\Action\invalidateCache.html.twig") was moved to CacheBundle
* Resource [`invalidate-cache-button-component.js`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/UPSBundle/Resources/public/js/app/components/invalidate-cache-button-component.js "Oro\Bundle\UPSBundle\Resources\public\js\app\components\invalidate-cache-button-component.js") was moved to CacheBundle

VisibilityBundle
----------------
* The following methods in [`VisibilityGridListener`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/VisibilityBundle/EventListener/VisibilityGridListener.php "Oro\Bundle\VisibilityBundle\EventListener\VisibilityGridListener") class were removed:
   - `addSubscribedGridConfig`
   - `isDefaultValue`
   - `isFilteredByDefaultValue`
   - `onOrmResultBeforeQuery`
   - `onResultBefore`
* In [`AbstractCustomerPartialUpdateDriver`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/VisibilityBundle/Driver/AbstractCustomerPartialUpdateDriver.php "Oro\Bundle\VisibilityBundle\Driver\AbstractCustomerPartialUpdateDriver"), the return type of the `getCustomerVisibilityIterator` method changed from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`.

WebCatalogBundle
----------------
* The [`ContentNodeListener::getSlugGeneratorLink`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/WebCatalogBundle/EventListener/ContentNodeListener.php "Oro\Bundle\WebCatalogBundle\EventListener\ContentNodeListener") method was removed.
* The [`SlugGenerator::findFallbackSlug`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/WebCatalogBundle/Generator/SlugGenerator.php "Oro\Bundle\WebCatalogBundle\Generator\SlugGenerator") method was removed.
* The [`ContentNodeSlugsProcessor::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebCatalogBundle/Async/ContentNodeSlugsProcessor.php "Oro\Bundle\WebCatalogBundle\Async\ContentNodeSlugsProcessor") method was updated to accpt:
  - [`ResolveNodeSlugsMessageFactory`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebCatalogBundle/Model/ResolveNodeSlugsMessageFactory.php "Oro\Bundle\WebCatalogBundle\Model\ResolveNodeSlugsMessageFactory") in the fifth argument instead of `Psr\Log\LoggerInterface`
  - `Psr\Log\LoggerInterface` in the 6th argument
* The [`ResolvedContentNode::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebCatalogBundle/Cache/ResolvedData/ResolvedContentNode.php "Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode") method was updated to accept `mixed` as a fifth argument.
* The [`ContentNodeListener::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebCatalogBundle/EventListener/ContentNodeListener.php "Oro\Bundle\WebCatalogBundle\EventListener\ContentNodeListener") method was updated to accept: 
  - [`MessageProducerInterface`](https://github.com/orocrm/platform/tree/2.1.0/src/Oro/Component/MessageQueue/Client/MessageProducerInterface.php "Oro\Component\MessageQueue\Client\MessageProducerInterface") in the third argument instead of [`ServiceLink`](https://github.com/orocrm/platform/tree/2.0.0/src/Oro/Component/DependencyInjection/ServiceLink.php "Oro\Component\DependencyInjection\ServiceLink")
  - [`ResolveNodeSlugsMessageFactory`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebCatalogBundle/Model/ResolveNodeSlugsMessageFactory.php "Oro\Bundle\WebCatalogBundle\Model\ResolveNodeSlugsMessageFactory") n the fourth argument instead of [`MessageProducerInterface`](https://github.com/orocrm/platform/tree/2.0.0/src/Oro/Component/MessageQueue/Client/MessageProducerInterface.php "Oro\Component\MessageQueue\Client\MessageProducerInterface")
* The [`ContentNodeListener::postRemove`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebCatalogBundle/EventListener/ContentNodeListener.php "Oro\Bundle\WebCatalogBundle\EventListener\ContentNodeListener") method was updated to accept the `Doctrine\ORM\Event\LifecycleEventArgs` in the second argument
* The [`ScopeRequestListener::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebCatalogBundle/EventListener/ScopeRequestListener.php "Oro\Bundle\WebCatalogBundle\EventListener\ScopeRequestListener") method was updated to accept the following values:
  - [`SlugRepository`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RedirectBundle/Entity/Repository/SlugRepository.php "Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository") in the second argument
  - [`MatchedUrlDecisionMaker`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RedirectBundle/Routing/MatchedUrlDecisionMaker.php "Oro\Bundle\RedirectBundle\Routing\MatchedUrlDecisionMaker") in the third argument
* The [`SlugGenerator::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebCatalogBundle/Generator/SlugGenerator.php "Oro\Bundle\WebCatalogBundle\Generator\SlugGenerator") method was updated to accept the following values:
  - [`RedirectGenerator`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RedirectBundle/Generator/RedirectGenerator.php "Oro\Bundle\RedirectBundle\Generator\RedirectGenerator") in the second argument
  - [`LocalizationHelper`](https://github.com/orocrm/platform/tree/2.1.0/src/Oro/Bundle/LocaleBundle/Helper/LocalizationHelper.php "Oro\Bundle\LocaleBundle\Helper\LocalizationHelper") in the third argument
  - [`SlugUrlDiffer`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RedirectBundle/Generator/SlugUrlDiffer.php "Oro\Bundle\RedirectBundle\Generator\SlugUrlDiffer") in the fourth argument
* The [`SlugGenerator::bindSlugs`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebCatalogBundle/Generator/SlugGenerator.php "Oro\Bundle\WebCatalogBundle\Generator\SlugGenerator") method was updated. Pass `mixed` as a third argument of the method.
* The [`SlugGenerator::generate`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebCatalogBundle/Generator/SlugGenerator.php "Oro\Bundle\WebCatalogBundle\Generator\SlugGenerator") method was updated. Pass `mixed` as a second argument of the method.
* The [`ContentNodeTreeHandler::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebCatalogBundle/JsTree/ContentNodeTreeHandler.php "Oro\Bundle\WebCatalogBundle\JsTree\ContentNodeTreeHandler") method was updated to accept the following values:
  - [`MessageProducerInterface`](https://github.com/orocrm/platform/tree/2.1.0/src/Oro/Component/MessageQueue/Client/MessageProducerInterface.php "Oro\Component\MessageQueue\Client\MessageProducerInterface") in the fourth argument
  - [`ResolveNodeSlugsMessageFactory`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebCatalogBundle/Model/ResolveNodeSlugsMessageFactory.php "Oro\Bundle\WebCatalogBundle\Model\ResolveNodeSlugsMessageFactory") in the fifth argument.
* The [`WebCatalogUsageProvider::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebCatalogBundle/Provider/WebCatalogUsageProvider.php "Oro\Bundle\WebCatalogBundle\Provider\WebCatalogUsageProvider") method was updated. Pass `Doctrine\Common\Persistence\ManagerRegistry` as a second argument of the method.
* The [`WebCatalogUsageProvider::isInUse`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebCatalogBundle/Provider/WebCatalogUsageProvider.php "Oro\Bundle\WebCatalogBundle\Provider\WebCatalogUsageProvider") method was updated. Pass [`WebCatalogInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Component/WebCatalog/Entity/WebCatalogInterface.php "Oro\Component\WebCatalog\Entity\WebCatalogInterface") as a first argument of the method instead of [`WebCatalog`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/WebCatalogBundle/Entity/WebCatalog.php "Oro\Bundle\WebCatalogBundle\Entity\WebCatalog").
* The [`WebCatalogExtension::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebCatalogBundle/Twig/WebCatalogExtension.php "Oro\Bundle\WebCatalogBundle\Twig\WebCatalogExtension") method was updated. Pass `Symfony\Component\DependencyInjection\ContainerInterface` as a first argument of the method instead of [`ContentNodeTreeHandler`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/WebCatalogBundle/JsTree/ContentNodeTreeHandler.php "Oro\Bundle\WebCatalogBundle\JsTree\ContentNodeTreeHandler").
* The [`WebCatalogExtension`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebCatalogBundle/Twig/WebCatalogExtension.php "Oro\Bundle\WebCatalogBundle\Twig\WebCatalogExtension") class changed:
    - The construction signature of was changed and the constructor was updated to have only one parameter: `ContainerInterface $container`.
    - The `protected $treeHandler` and `protected $contentVariantTypeRegistry` properties were removed.
* The [`AbstractWebCatalogDataProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebCatalogBundle/Layout/DataProvider/AbstractWebCatalogDataProvider.php "Oro\Bundle\WebCatalogBundle\Layout\DataProvider\AbstractWebCatalogDataProvider") class was created to unify Providers for MenuData and WebCatalogBreadcrumb
* The [`WebCatalogBreadcrumbDataProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebCatalogBundle/Layout/DataProvider/WebCatalogBreadcrumbDataProvider.php "Oro\Bundle\WebCatalogBundle\Layout\DataProvider\WebCatalogBreadcrumbDataProvider") class was created. 
    - `getItems` method returns breadcrumbs array

WebsiteBundle
-------------
* The [`WebsiteBundle`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/WebsiteBundle "Oro\Bundle\WebsiteBundle") moved from the [`OroCommerce`](https://github.com/orocommerce/orocommerce) package into the [`OroCRM Customer Portal`](https://github.com/orocrm/customer-portal) package.
* The [`OroWebsiteExtension`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebsiteBundle/Twig/OroWebsiteExtension.php "Oro\Bundle\WebsiteBundle\Twig\OroWebsiteExtension") class changed:
    - The construction signature of was changed and the constructor was updated to have only one parameter: `ContainerInterface $container`.
    - The `protected $websiteManager` property was removed.
* The [`WebsitePathExtension`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebsiteBundle/Twig/WebsitePathExtension.php "Oro\Bundle\WebsiteBundle\Twig\WebsitePathExtension") class changed:
        - The construction signature of was changed and the constructor was updated to have only one parameter: `ContainerInterface $container`.
        - The `protected $websiteUrlResolver` property was removed.

WebsiteSearchBundle
-------------------
* The `Driver::writeItem` and `Driver::flushWrites` should be used instead of `Driver::saveItems`
* The following classes and methods were removed:
  - [`CustomerIdPlaceholder`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/WebsiteSearchBundle/Placeholder/CustomerIdPlaceholder.php "Oro\Bundle\WebsiteSearchBundle\Placeholder\CustomerIdPlaceholder") class
[`AbstractIndexer::getWebsiteIdsToIndex`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/WebsiteSearchBundle/Engine/AbstractIndexer.php "Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer") was removed.
  - [`IndexationRequestListener::getEntitiesWithUpdatedIndexedFields`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/WebsiteSearchBundle/EventListener/IndexationRequestListener.php "Oro\Bundle\WebsiteSearchBundle\EventListener\IndexationRequestListener") method
* The [`AbstractIndexer::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebsiteSearchBundle/Engine/AbstractIndexer.php "Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer") method was updated to accept:
  - [`IndexerInputValidator`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebsiteSearchBundle/Engine/IndexerInputValidator.php "Oro\Bundle\WebsiteSearchBundle\Engine\IndexerInputValidator") in the 6th argument
* The [`AsyncIndexer::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebsiteSearchBundle/Engine/AsyncIndexer.php "Oro\Bundle\WebsiteSearchBundle\Engine\AsyncIndexer") method was updated to accept:
  - [`IndexerInputValidator`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebsiteSearchBundle/Engine/IndexerInputValidator.php "Oro\Bundle\WebsiteSearchBundle\Engine\IndexerInputValidator") in the third argument
  - [`ReindexMessageGranularizer`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebsiteSearchBundle/Engine/AsyncMessaging/ReindexMessageGranularizer.php "Oro\Bundle\WebsiteSearchBundle\Engine\AsyncMessaging\ReindexMessageGranularizer") in the fourth argument
* The [`WebsiteQueryFactory::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebsiteSearchBundle/Query/Factory/WebsiteQueryFactory.php "Oro\Bundle\WebsiteSearchBundle\Query\Factory\WebsiteQueryFactory") method was updated to accept: 
  - [`EngineInterface`](https://github.com/orocrm/platform/tree/2.1.0/src/Oro/Bundle/SearchBundle/Engine/EngineInterface.php "Oro\Bundle\SearchBundle\Engine\EngineInterface") in the first argument instead of [`EngineV2Interface`](https://github.com/orocrm/platform/tree/2.0.0/src/Oro/Bundle/SearchBundle/Engine/EngineV2Interface.php "Oro\Bundle\SearchBundle\Engine\EngineV2Interface").
* The [`WebsiteSearchQuery::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebsiteSearchBundle/Query/WebsiteSearchQuery.php "Oro\Bundle\WebsiteSearchBundle\Query\WebsiteSearchQuery") method was updated to accept:
  - [`EngineInterface`](https://github.com/orocrm/platform/tree/2.1.0/src/Oro/Bundle/SearchBundle/Engine/EngineInterface.php "Oro\Bundle\SearchBundle\Engine\EngineInterface") in the first argument instead of [`EngineV2Interface`](https://github.com/orocrm/platform/tree/2.0.0/src/Oro/Bundle/SearchBundle/Engine/EngineV2Interface.php "Oro\Bundle\SearchBundle\Engine\EngineV2Interface").

WebCatalog Component
--------------------
* New [`WebCatalogAwareInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Component/WebCatalog/Entity/WebCatalogAwareInterface.php "Oro\Component\WebCatalog\Entity\WebCatalogAwareInterface") became available for entities which are aware of `WebCatalogs`.
* New [`WebCatalogUsageProviderInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Component/WebCatalog/Provider/WebCatalogUsageProviderInterface.php "Oro\Component\WebCatalog\Provider\WebCatalogUsageProviderInterface") interface:
    - provides information about assigned `WebCatalogs` to given entities (passed as an argument)
    - provides information about usage of `WebCatalog` by id

UPSBundle
---------
* "Check UPS Connection" button was added on UPS integration page. Please, see [documentation](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Resources/doc/credentials-validation.md) for more information.
* The [`UPSChannelEntityListener`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/UPSBundle/EventListener/UPSChannelEntityListener.php "Oro\Bundle\UPSBundle\EventListener\UPSChannelEntityListener") class was removed.
* The following methods in the [`UPSTransport`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/UPSBundle/Entity/UPSTransport.php "Oro\Bundle\UPSBundle\Entity\UPSTransport") class were removed:
   - `getBaseUrl`
   - `setBaseUrl`
* The [`UPSShippingMethod::fetchPrices`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/UPSBundle/Method/UPSShippingMethod.php "Oro\Bundle\UPSBundle\Method\UPSShippingMethod") method was removed.
* The [`UPSShippingMethod::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Method/UPSShippingMethod.php "Oro\Bundle\UPSBundle\Method\UPSShippingMethod") method was updated to accept:
  - `string $identifier` in the first argument instead of [`UPSTransport`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/UPSBundle/Provider/UPSTransport.php "Oro\Bundle\UPSBundle\Provider\UPSTransport")
  - `string $label` in the second argument instead of [`Channel`](https://github.com/orocrm/platform/tree/2.0.0/src/Oro/Bundle/IntegrationBundle/Entity/Channel.php "Oro\Bundle\IntegrationBundle\Entity\Channel").
  - `array $types` in the third argument instead of [`PriceRequestFactory`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/UPSBundle/Factory/PriceRequestFactory.php "Oro\Bundle\UPSBundle\Factory\PriceRequestFactory").
  - [`UPSTransport`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Entity/UPSTransport.php "Oro\Bundle\UPSBundle\Entity\UPSTransport") in the fourth argument instead of [`LocalizationHelper`](https://github.com/orocrm/platform/tree/2.0.0/src/Oro/Bundle/LocaleBundle/Helper/LocalizationHelper.php "Oro\Bundle\LocaleBundle\Helper\LocalizationHelper")
  - [`UPSTransport`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Provider/UPSTransport.php "Oro\Bundle\UPSBundle\Provider\UPSTransport") in the fifth argument instead of [`ShippingPriceCache`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/UPSBundle/Cache/ShippingPriceCache.php "Oro\Bundle\UPSBundle\Cache\ShippingPriceCache"). 
  - [`PriceRequestFactory`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Factory/PriceRequestFactory.php "Oro\Bundle\UPSBundle\Factory\PriceRequestFactory") in the sixth argument
  - [`ShippingPriceCache`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Cache/ShippingPriceCache.php "Oro\Bundle\UPSBundle\Cache\ShippingPriceCache") in the seventh argument
  - `bool $enabled` in the 8th argument.
* The [`UPSShippingMethodProvider::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Method/UPSShippingMethodProvider.php "Oro\Bundle\UPSBundle\Method\UPSShippingMethodProvider") method was updated to accept:
  - [`DoctrineHelper`](https://github.com/orocrm/platform/tree/2.1.0/src/Oro/Bundle/EntityBundle/ORM/DoctrineHelper.php "Oro\Bundle\EntityBundle\ORM\DoctrineHelper") in the first argument instead of [`UPSTransport`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/UPSBundle/Provider/UPSTransport.php "Oro\Bundle\UPSBundle\Provider\UPSTransport")
  - [`IntegrationShippingMethodFactoryInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ShippingBundle/Method/Factory/IntegrationShippingMethodFactoryInterface.php "Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface") in the second argument instead of `Symfony\Bridge\Doctrine\ManagerRegistry`.
* The [`UPSShippingMethodType::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Method/UPSShippingMethodType.php "Oro\Bundle\UPSBundle\Method\UPSShippingMethodType") method was updated to accept:
  - `string $label` in the second argument instead of [`UPSTransport`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/UPSBundle/Entity/UPSTransport.php "Oro\Bundle\UPSBundle\Entity\UPSTransport")
  - `string $methodId` in the third argument instead of [`UPSTransport`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/UPSBundle/Provider/UPSTransport.php "Oro\Bundle\UPSBundle\Provider\UPSTransport")
  - [`UPSTransport`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Entity/UPSTransport.php "Oro\Bundle\UPSBundle\Entity\UPSTransport") in the fifth argument instead of [`PriceRequestFactory`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/UPSBundle/Factory/PriceRequestFactory.php "Oro\Bundle\UPSBundle\Factory\PriceRequestFactory")
  - [`ShippingService`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Entity/ShippingService.php "Oro\Bundle\UPSBundle\Entity\ShippingService") in the 6th argument instead of [`ShippingPriceCache`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/UPSBundle/Cache/ShippingPriceCache.php "Oro\Bundle\UPSBundle\Cache\ShippingPriceCache")
  - [`PriceRequestFactory`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Factory/PriceRequestFactory.php "Oro\Bundle\UPSBundle\Factory\PriceRequestFactory") in the 7th argument.
  - [`ShippingPriceCache`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Cache/ShippingPriceCache.php "Oro\Bundle\UPSBundle\Cache\ShippingPriceCache") in the 8th argument
* The [`UPSTransport::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Provider/UPSTransport.php "Oro\Bundle\UPSBundle\Provider\UPSTransport") method was updated to accept:
  - [`UpsClientUrlProviderInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Client/Url/Provider/UpsClientUrlProviderInterface.php "Oro\Bundle\UPSBundle\Client\Url\Provider\UpsClientUrlProviderInterface") in the first argument instead of `Psr\Log\LoggerInterface`
  - `Psr\Log\LoggerInterface` in the second argument

FrontendLocalizationBundle
--------------------------
- The [`TranslationPackagesProviderExtension`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/FrontendLocalizationBundle/Provider/TranslationPackagesProviderExtension.php "Oro\Bundle\FrontendLocalizationBundle\Provider\TranslationPackagesProviderExtension") was removed.

- The service definition for `oro_frontend_localization.extension.transtation_packages_provider` was updated in a following way: 
    - the class changed to [`UPSTransport::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/FrontendBundle/Provider/TranslationPackagesProviderExtension.php "Oro\Bundle\FrontendBundle\Provider\TranslationPackagesProviderExtension")
    - the publicity set to `false`

Tree Component
--------------

The [`AbstractTreeHandler::getTreeItemList()`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Component/Tree/Handler/AbstractTreeHandler.php "Oro\Component\Tree\Handler\AbstractTreeHandler") method was added.
