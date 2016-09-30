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
- Added ninth argument `WorkflowManager $workflowManager` to constructor of `Oro\Bundle\CheckoutBundle\Model\Action\StartCheckout`.
- Protected method `Oro\Bundle\CheckoutBundle\Model\Action\StartCheckout::getCheckout` was renamed to `getCheckoutWithWorkflowName`.
- Added second argument to protected method `string $workflowName` to method `Oro\Bundle\CheckoutBundle\Model\Action\StartCheckout::isNewCheckoutEntity`.
- Removed fields `workflowItem` and `workflowStep` from entity `Oro\Bundle\CheckoutBundle\Entity\BaseCheckout` - not using `WorkflowAwareTrait` more. It means that for entity `Oro\Bundle\CheckoutBundle\Entity\Checkout` these fields removed too. 
- Interface `Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface` no longer implements `Oro\Bundle\WorkflowBundle\Entity\WorkflowAwareInterface`.
- Added new property `string $workflowName` to `Oro\Bundle\CheckoutBundle\Event\CheckoutEntityEvent` and added related `setter` and `getter`.
- Added argument `CheckoutInterface $checkout` to method `Oro\Bundle\CheckoutBundle\EventListener\CheckoutEntityListener::getWorkflowName`.
- `oro_checkout.repository.checkout` inherits `oro_entity.abstract_repository`.
- Second argument `ShippingRulesProvider $rulesProvider` changed to `ShippingPriceProvider $priceProvider` in constructor of `Oro\Bundle\CheckoutBundle\Action\DefaultShippingMethodSetter`.
- Second argument `ShippingRulesProvider $shippingRulesProvider` changed to `ShippingPriceProvider $priceProvider` in constructor of `Oro\Bundle\CheckoutBundle\Condition\HasApplicableShippingMethods`.
- Third argument `ShippingCostCalculationProvider $costCalculationProvider` was removed from constructor of `Oro\Bundle\CheckoutBundle\Action\DefaultShippingMethodSetter`.
- Second argument `ShippingRulesProvider $shippingRulesProvider` changed to `ShippingPriceProvider $priceProvider` in constructor of `Oro\Bundle\CheckoutBundle\Condition\ShippingMethodSupports`.
- First argument `ShippingMethodRegistry $shippingMethodRegistry` was removed from constructor of `Oro\Bundle\CheckoutBundle\Condition\ShippingMethodSupports`.
- Added constructor to `Oro\Bundle\CheckoutBundle\Factory\ShippingContextProviderFactory`.
- Added `Oro\Bundle\CheckoutBundle\Layout\DataProvider\CheckoutShippingContextProvider` - shipping data provider for frontend checkout layout.
- Moved `Oro\Bundle\CheckoutBundle\Layout\DataProvider\ShippingMethodsProvider` to `Oro\Bundle\ShippingBundle\Layout\DataProvider\ShippingMethodsProvider`.
- Removed `Oro\Bundle\CheckoutBundle\Provider\ShippingCostCalculationProvider`

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
--------------

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
- Moved `Oro\Bundle\CheckoutBundle\Layout\DataProvider\ShippingMethodsProvider` to `Oro\Bundle\ShippingBundle\Layout\DataProvider\ShippingMethodsProvider`.
- Second argument `ShippingRulesProvider $shippingRulesProvider` changed to `ShippingPriceProvider $priceProvider` in constructor of `Oro\Bundle\ShippingBundle\Layout\DataProvider\ShippingMethodsProvider`.
- First argument `ShippingMethodRegistry $shippingMethodRegistry` was removed from constructor of `Oro\Bundle\ShippingBundle\Layout\DataProvider\ShippingMethodsProvider`.
- Added `Oro\Bundle\ShippingBundle\Context\ShippingContextInterface` - for shipping context implementation.
- Added `Oro\Bundle\ShippingBundle\Context\ShippingContext` - for managing sipping context data.
- Added `Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface` - for shipping line item implementation.
- Added `Oro\Bundle\ShippingBundle\Context\ShippingLineItem` - for managing shipping information about product.
- Value of constant `TAG` was changed from `oro_shipping_method` to `oro_shipping_method_provider` in `Oro\Bundle\ShippingBundle\DependencyInjection\CompilerPass\ShippingMethodsCompilerPass`
- Removed `Oro\Bundle\ShippingBundle\Entity\FlatRateRuleConfiguration`.
- Modified `Oro\Bundle\ShippingBundle\Entity\ShippingRule`:
    - Field `configurations` changed to `methodConfigs`.
    - Method `addConfiguration` changed to `addMethodConfig`.
    - Method `hasConfiguration` changed to `hasMethodConfig`.
    - Method `removeConfiguration` changed to `removeMethodConfig`.
    - Method `setConfigurations` was removed.
    - Method `getConfigurations` changed to `getMethodConfigs`.
- Table name `oro_shipping_rl_destination` was changed to `oro_shipping_rule_destination` in `Oro\Bundle\ShippingBundle\Entity\ShippingRuleDestination`.
- Added `Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodConfig` - for storing shipping rule method configuration.
- Modified `Oro\Bundle\ShippingBundle\Entity\ShippingRuleConfiguration`:
    - Moved to `Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodTypeConfig`.
    - Class not abstract anymore.
    - Method `__toString` removed.
    - Method `getRule` removed.
    - Method `setRule` removed.
    - Method `getCurrency` removed.
    - Method `setMethod` removed.
    - Method `getOptions` added.
    - Method `setOptions` added.
    - Method `getMethodConfig` added.
    - Method `setMethodConfig` added.
- Added `Oro\Bundle\ShippingBundle\Factory\ShippingContextFactory` - for sipping context creation.
- Removed `Oro\Bundle\ShippingBundle\Form\EventSubscriber\RuleConfigurationSubscriber`.
- Added `Oro\Bundle\ShippingBundle\Form\EventSubscriber\RuleMethodConfigCollectionSubscriber` - for removing shipping methods which does not exists.
- Added `Oro\Bundle\ShippingBundle\Form\EventSubscriber\RuleMethodConfigSubscriber` - for setting shipping methods.
- Added `Oro\Bundle\ShippingBundle\Form\EventSubscriber\RuleMethodTypeConfigCollectionSubscriber` - for setting shipping methods types.
- Added `Oro\Bundle\ShippingBundle\Form\Handler\ShippingRuleHandler` - for storing shipping rules.
- Moved `Oro\Bundle\ShippingBundle\Form\Type\FlatRateShippingConfigurationType` to `Oro\Bundle\ShippingBundle\Form\Type\FlatRateShippingConfigurationType`.
- Added method `getParent` in `Oro\Bundle\ShippingBundle\Form\Type\FlatRateShippingConfigurationType`
- Added `Oro\Bundle\ShippingBundle\Form\Type\ShippingRuleMethodConfigCollectionType` - form for shipping rules method configuration.
- Added `Oro\Bundle\ShippingBundle\Form\Type\ShippingRuleMethodConfigType` - form for shipping rules method type configuration.
- Added `Oro\Bundle\ShippingBundle\Form\Type\ShippingRuleMethodTypeConfigCollectionType` - form for shipping rules method type configuration block view.
- Moved `Oro\Bundle\ShippingBundle\Form\Type\ShippingRuleConfigurationType` to `Oro\Bundle\ShippingBundle\Form\Type\ShippingRuleMethodTypeConfigType`.
- Modified `Oro\Bundle\ShippingBundle\Form\Type\ShippingRuleType`:
    - Added constructor.
    - Added method `buildView`.
    - Removed protected method `getMethods`.
- Added method `formatShippingMethodWithType` in `Oro\Bundle\ShippingBundle\Formatter\ShippingMethodLabelFormatter`.
- Added `Oro\Bundle\ShippingBundle\Layout\DataProvider\ShippingMethodsProvider` - shipping methods data provider.
- Added `Oro\Bundle\ShippingBundle\Method\FlatRate\FlatRateShippingMethod` - flat rate shipping method.
- Added `Oro\Bundle\ShippingBundle\Method\FlatRate\FlatRateShippingMethodProvider` - provider for flat rate shipping method.
- Added `Oro\Bundle\ShippingBundle\Method\FlatRate\FlatRateShippingMethodType` - primary type for flat rate shipping method.
- Removed `Oro\Bundle\ShippingBundle\Method\FlatRateShippingMethod`.
- Added `Oro\Bundle\ShippingBundle\Context\ShippingContextInterface\PricesAwareShippingMethodInterface` - interface for shipping method price calculations.
- Modified `Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface`:
    - Moved method `getName` to `getIdentifier`.
    - Moved method `getShippingTypes` to `getTypes`.
    - Moved method `getShippingTypes` to `getTypes`.
    - Removed method `getShippingTypeLabel`.
    - Added method `getType`.
    - Added method `getType`.
    - Moved method `getRuleConfigurationClass` to `getOptionsConfigurationFormType`.
    - Removed method `calculatePrice`.
- Added `Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface` - interface for shipping method providers.
- Added `Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface` - interface for shipping method types.
- Modified `Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry`:
    - Removed protected field `shippingMethods`.
    - Removed method `addShippingMethod`.
    - Added protected field `providers`.
    - Added method `addProvider`.
    - Added method `hasShippingMethod`.
- Moved `Oro\Bundle\ShippingBundle\Model\ExtendShippingRuleConfiguration` to `Oro\Bundle\ShippingBundle\Model\ExtendShippingRuleMethodConfig`.
- Added `Oro\Bundle\ShippingBundle\Model\ExtendShippingRuleMethodTypeConfig`.
- Removed `Oro\Bundle\ShippingBundle\Provider\ShippingContextAwareInterface`.
- Removed `Oro\Bundle\ShippingBundle\Provider\ShippingContextProvider`.
- Removed `Oro\Bundle\ShippingBundle\Provider\ShippingRulesProvider`.
- Added `Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider` - for getting prices for shipping method types.
- Removed `Oro\Bundle\ShippingBundle\Validator\Constraints\EnabledConfigurationValidationGroupValidator`.
- Added `Oro\Bundle\ShippingBundle\Validator\Constraints\EnabledTypeConfigsValidationGroupValidator`.
- Moved `Oro\Bundle\ShippingBundle\Validator\Constraints\EnabledConfigurationValidationGroup` to `Oro\Bundle\ShippingBundle\Validator\Constraints\EnabledTypeConfigsValidationGroup`.

ShoppingListBundle
------------------
- `oro_shopping_list.repository.line_item` inherits `oro_entity.abstract_repository`.
- Removed `Oro\Bundle\ShoppingListBundle\Condition\HasPriceInShoppingLineItems`.

UPSBundle:
---------
- Added bundle that adds UPS shipping method with power of OroShippingBundle.

WarehouseBundle
---------------
- added manageInventory field to Category entity and related admin pages with fallback support
- added manageInventory field to Product entity and related admin pages with fallback support
- added CategoryFallbackProvider with fallback id `category`
- added ParentCategoryFallbackProvider with fallback id `parentCategory`
