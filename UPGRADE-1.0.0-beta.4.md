Upgrade from beta.3
===================

General
-------
- All code was moved from `OroB2B` namespace to `Oro` namespace
- Name prefix for all OroCommerce tables, routes and ACL identities was changed from `orob2b_` to `oro_` 

FrontendBundle:
---------------
- Value for parameter `applications` for `Frontend` part of OroCommerce in operation configuration renamed from `frontend` to `commerce`.

CheckoutBundle:
---------------
- Second argument `$checkoutType = null` of method `Oro\Bundle\CheckoutBundle\Controller\Frontend\CheckoutController::checkoutAction` was removed.
- Added ninth argument `WorkflowManager $workflowManager` to constructor of `Oro\Bundle\CheckoutBundle\Model\Action\StartCheckout`;
- Protected method `Oro\Bundle\CheckoutBundle\Model\Action\StartCheckout::getCheckout` was renamed to `getCheckoutWithWorkflowName`.
- Added second argument to protected method `string $workflowName` to method `Oro\Bundle\CheckoutBundle\Model\Action\StartCheckout::isNewCheckoutEntity`.
- Removed fields `workflowItem` and `workflowStep` from entity `Oro\Bundle\CheckoutBundle\Entity\BaseCheckout` - not using `WorkflowAwareTrait` more. It means that for entity `Oro\Bundle\CheckoutBundle\Entity\Checkout` these fields removed too. 
- Interface `Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface` no longer implements `Oro\Bundle\WorkflowBundle\Entity\WorkflowAwareInterface`.
- Added new property `string $workflowName` to `Oro\Bundle\CheckoutBundle\Event\CheckoutEntityEvent` and added related `setter` and `getter`.
- Added argument `CheckoutInterface $checkout` to method `Oro\Bundle\CheckoutBundle\EventListener\CheckoutEntityListener::getWorkflowName`.
- `oro_checkout.repository.checkout` inherits `oro_entity.abstract_repository`

AlternativeCheckoutBundle:
--------------------------
- Removed fields `workflowItem` and `workflowStep` from entity `Oro\Bundle\AlternativeCheckoutBundle\Entity\AlternativeCheckout` - not using `WorkflowAwareTrait` more.

WebsiteBundle:
--------------
- Field `localization` removed from entity `Website`.

FrontendLocalizationBundle
--------------------------
- Introduced `FrontendLocalizationBundle` - allow to work with `Oro\Bundle\LocaleBundle\Entity\Localization` in
frontend. Provides possibility to manage current AccountUser localization-settings. Provides Language Switcher for
Frontend.
- Added ACL voter `Oro\Bundle\FrontendLocalizationBundle\Acl\Voter\LocalizationVoter` - prevent removing localizations
that used by default for any WebSite.
- Added `Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManager` - for manage current user's
localizations for websites.
- Added `Oro\Bundle\FrontendLocalizationBundle\Extension\CurrentLocalizationExtension` - provide current localization from UserLocalizationManager.

AccountUser
-----------
- Added field `localization` to Entity `AccountUserSettings` - for storing selected `Localization` for websites.
- Field `currency` in Entity `AccountUserSettings` is nullable.

PaymentBundle
-------------
- Added `EventDispatcherInterface` argument to `Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider` constructor.
- Added `getPaymentMethods` method to `Oro\Bundle\PaymentBundle\Layout\DataProvider\PaymentMethodsProvider`.
- Added `PaymentTransactionProvider` argument to `Oro\Bundle\PaymentBundle\Layout\DataProvider\PaymentMethodsProvider` constructor.
- Added `Oro\Bundle\PaymentBundle\Manager\PaymentStatusManager` for saving payment status for certain entity.
- Added `Oro\Bundle\PaymentBundle\Formatter\PaymentStatusLabelFormatter` for translating payment status labels and getting all available payment statuses.
- Added `Oro\Bundle\PaymentBundle\Twig\PaymentStatusExtension` with twig function `get_payment_status_label` which returns translated payment label.
- Argument `context` of `Oro\Bundle\PaymentBundle\Provider\PaymentContextProvider::processContext` was removed.
- Added `Oro\Bundle\PaymentBundle\Event\ResolvePaymentTermEvent`.
- Added `oropayment\js\app\views\payment-term-view` js component.

OrderBundle:
------------
- Moved `get_payment_status_label` twig function to `PaymentBundle` to `Oro\Bundle\PaymentBundle\Twig\PaymentStatusExtension`.
- Removed `PaymentStatusProvider` constructor argument from `Oro/Bundle/OrderBundle/Twig/OrderExtension`.
- Removed `Oro\Bundle\OrderBundle\Layout\DataProvider\OrderPaymentMethodProvider`.
- Removed method `Oro\Bundle\OrderBundle\Twig\OrderExtension::formatSourceDocument`
- Removed `Oro\Bundle\OrderBundle\Twig\OrderExtension` constructor first argument `Doctrine\Common\Persistence\ManagerRegistry`

PricingBundle:
-------------

- Removed `getWebsiteIdsByAccountGroup` method from `PriceListToAccountGroupRepository`
- Removed method `getAccountWebsitePairsByAccountGroup` from `PriceListToAccountRepository`
- Removed method `getAccountWebsitePairsByAccountGroupQueryBuilder` from `PriceListToAccountRepository`
- Removed method `getAccountWebsitePairsByAccountGroup` from `PriceListToAccountRepository`
- Changed arguments of `PriceListChangeTriggerHandler` constructor

SaleBundle:
-----------
- Modified `Oro\Bundle\SaleBundle\Entity\Quote` with property `paymentTerm` as many-to-one relation to `Oro\Bundle\PaymentBundle\Entity\PaymentTerm`.

CatalogBundle
-------------
- `oro_catalog.repository.category` inherits `oro_entity.abstract_repository`

ProductBundle
-------------
- `oro_product.repository.product` inherits `oro_entity.abstract_repository`

ShippingBundle
--------------
- `oro_shipping.repository.product_shipping_options` inherits `oro_entity.abstract_repository`
- `oro_shipping.repository.length_unit` inherits `oro_entity.abstract_repository`
- `oro_shipping.repository.weight_unit` inherits `oro_entity.abstract_repository`
- `oro_shipping.repository.freight_class` inherits `oro_entity.abstract_repository`

ShoppingListBundle
------------------
- `oro_shopping_list.repository.line_item` inherits `oro_entity.abstract_repository`

EntityBundle
------------
- Added entity fallback functionality
- Added EntityFieldFallbackValue entity to store fallback information
- Added EntityFallbackResolver service which handles fallback resolution
- Added SystemConfigFallbackProvider service which handles `systemConfig` fallback type
- Added GetEntityFallbackExtension service which reads fallback values of entities in twig
- Added AbstractEntityFallbackProvider abstract service to ease adding new fallback types, please refer 
to the [Fallback documentation](../platform/src/Oro/Bundle/EntityBundle/Resources/doc/entity_fallback.md) for details

WarehouseBundle
---------------
- added manageInventory field to Category entity and related admin pages with fallback support
- added manageInventory field to Product entity and related admin pages with fallback support
- added CategoryFallbackProvider with fallback id `category`
- added ParentCategoryFallbackProvider with fallback id `parentCategory`
