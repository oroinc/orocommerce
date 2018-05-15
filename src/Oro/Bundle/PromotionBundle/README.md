# OroPromotionBundle

OroPromotionBundle adds coupon and promotion features to the OroCommerce application.

With this bundle, the management console administrator can enable or disable these features in the system configuration UI. For sales representatives, the bundle introduces UI in the management console to create and manage coupons and promotions, and apply promotions to the customer orders via special discounts and coupon codes. For the customer users on the storefront, it provides the ability to apply coupons to the orders and review the applied promotions and discounts.

## Table of Contents

 * [Discounts](#discounts)
    * [Creation and Types](#discoucnts-creation-and-types)
    * [Add discount](#add-discount)
    * [Add discount formType](#add-discount-formtype)
    * [Organize new discount options](#organize-new-discount-options)
    * [Discount applying and calculation](#discount-applying-and-calculation)
    * [Discount Context Converters](#discount-context-converters)
 * [Promotions Filtration](#promotions-filtration)
    * [Flow and Filters Types](#flow-and-filters-types)
    * [Context Data Converters](#context-data-converters)
    * [Add new filter](#add-new-filter)
    * [Filters skippability during checkout](#filters-skippability-during-checkout)
 * [Discount Strategy](#discount-strategy)
 * [Applied Promotions](#applied-promotions)

## Discounts

**<a name="discoucnts-creation-and-types">Creation and Types</a>**

Each promotion has `Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration` attached to it. With the help of this configuration, `Oro\Bundle\PromotionBundle\Executor\PromotionExecutor` using `Oro\Bundle\PromotionBundle\Provider\PromotionDiscountsProvider` creates a discount that implements `Oro\Bundle\PromotionBundle\Discount\DiscountInterface`. 

The following discount types are available in the system: 
- `Oro\Bundle\PromotionBundle\Discount\OrderDiscount` that gives discount on the order level
- `Oro\Bundle\PromotionBundle\Discount\LineItemsDiscount` that gives discount on the line-item level
- `Oro\Bundle\PromotionBundle\Discount\BuyXGetYDiscount` that gives the Buy X Get Y type of discount
- `Oro\Bundle\PromotionBundle\Discount\ShippingDiscount` that gives a shipping discount

**<a name="add-discount">Add discount</a>**

In order to add a new discount that can be selected in promotion configuration, you should first create a discount class that implements `Oro\Bundle\PromotionBundle\Discount\DiscountInterface`. You can use `Oro\Bundle\PromotionBundle\Discount\AbstractDiscount` as a base class for it. After that, register your discount as a `shared: false` service, and add it to the `Oro\Bundle\PromotionBundle\Discount\DiscountFactory` by invoking the `addType` method in the service definition:
```
    app.promotion.discount.my_discount:
        class: AppBundle\Promotion\Discount\OrderDiscount
        shared: false

    oro_promotion.discount_factory:
        class: Oro\Bundle\PromotionBundle\Discount\DiscountFactory
        public: false
        arguments:
            - '@service_container'
        calls:
            - ['addType', ['order', 'oro_promotion.discount.order_discount']]
            - ['addType', ['line_item', 'oro_promotion.discount.line_item_discount']]
            - ['addType', ['buy_x_get_y', 'oro_promotion.discount.buy_x_get_y_discount']]
            - ['addType', ['shipping', 'oro_promotion.discount.shipping_discount']]
            - ['addType', ['my_discount', 'app.promotion.discount.my_discount']]
```

**<a name="add-discount-formtype">Add discount formType</a>**

You also need to specify the FormType information for your discount. First, create a FormType for it. You can use some of the already available ones for the reference, for example `Oro\Bundle\PromotionBundle\Form\Type\LineItemDiscountOptionsType`. Next, add it to the `Oro\Bundle\PromotionBundle\Provider\DiscountFormTypeProvider` in services:
```
    oro_promotion.discount_type_to_form_type_provider:
        class: Oro\Bundle\PromotionBundle\Provider\DiscountFormTypeProvider
        calls:
            - ['setDefaultFormType', ['oro_promotion_order_discount_options']]
            - ['addFormType', ['order', 'oro_promotion_order_discount_options']]
            - ['addFormType', ['line_item', 'oro_promotion_line_item_discount_options']]
            - ['addFormType', ['buy_x_get_y', 'oro_promotion_buy_x_get_y_discount_options']]
            - ['addFormType', ['shipping', 'oro_promotion_shipping_discount_options']]
            - ['addFormType', ['my_discount', 'my_discount_options_form_type_alias']]
```

**<a name="organize-new-discount-options">Organize new discount options</a>**

You may want to add new discount options when adding a new discount. Discount options are stored as an array inside `Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration::options`. When promotions are executed, discount options are passed to the discounts configure method, for example `Oro\Bundle\PromotionBundle\Discount\LineItemsDiscount::configure`, where options become resolved and you can safely store them and use later for calculations.

In order to connect formType fields with the discount options, they should have the same key. It can be helpful to specify this key as the discount's constant and use it during form field definition like `Oro\Bundle\PromotionBundle\Discount\LineItemsDiscount::APPLY_TO` used inside `Oro\Bundle\PromotionBundle\Form\Type\LineItemDiscountOptionsType::buildForm`.


**<a name="discount-applying-and-calculation">Discount applying and calculation<a>**

When the discount is already created by `Oro\Bundle\PromotionBundle\Discount\DiscountFactory` and configured, the discount strategy applies discounts to `Oro\Bundle\PromotionBundle\Discount\DiscountContext`. This context is used as a storage for applicable discounts, calculated discounts values and related information that is necessary for discount calculation process.

At first, the strategy iterates over all discounts and calls `\Oro\Bundle\PromotionBundle\Discount\DiscountInterface::apply`, where a discount decides whether it is applicable to the current situation or not. If it is, it adds itself into one of the applicable storages: `Oro\Bundle\PromotionBundle\Discount\DiscountContext::$subtotalDiscounts`, `Oro\Bundle\PromotionBundle\Discount\DiscountContext::$shippingDiscounts` or `Oro\Bundle\PromotionBundle\Discount\DiscountLineItem::$discounts` that are stored in `Oro\Bundle\PromotionBundle\Discount\DiscountContext::$lineItems`.

Later, the strategy will iterate over all discounts that have been added to `Oro\Bundle\PromotionBundle\Discount\DiscountContext`. It will execute `Oro\Bundle\PromotionBundle\Discount\DiscountInterface::calculate`, where the discount will check whether it supports the passed entity. If it does, it should calculate the discount and return the discount amount as a float value.

**<a name="discount-context-converters">Discount Context Converters</a>**

`Oro\Bundle\PromotionBundle\Discount\DiscountContext` is created based on the source entity by discount context converters. If you need to support a new source entity, you should create a class that implements `Oro\Bundle\PromotionBundle\Discount\Converter\DiscountContextConverterInterface` and tag its service with `'oro_promotion.discount_context_converter'`, in order to be able to convert this entity into context.
```
    app.promotion.custom_entity_context_data_converter:
        class: AppBundle\Promotion\CustomEntityContextDataConverter
        public: false
        tags:
            - { name: 'oro_promotion.discount_context_converter' }
```
The discount converter should return `Oro\Bundle\PromotionBundle\Discount\DiscountContext`. Also, keep in mind that line items in `Oro\Bundle\PromotionBundle\Discount\DiscountContext::$lineItems` are stored in a unified format `Oro\Bundle\PromotionBundle\Discount\DiscountLineItem`. `Oro\Bundle\ShoppingListBundle\Entity\LineItem` and `Oro\Bundle\OrderBundle\Entity\OrderLineItem` transform line items to this format with the help of converters.

## Promotions Filtration


**<a name="flow-and-filters-types">Flow and Filters Types</a>**

When promotions are calculated, the list of applicable promotions is received with the help of `Oro\Bundle\PromotionBundle\Provider\PromotionProvider`. To get only suitable promotions, filters are used. By default, they are the following:
- `Oro\Bundle\RuleBundle\RuleFiltration\EnabledRuleFiltrationServiceDecorator` - filters enabled promotions
- `Oro\Bundle\PromotionBundle\RuleFiltration\DuplicateFiltrationService` - filters promotions that are already used to avoid duplications
- `Oro\Bundle\PromotionBundle\RuleFiltration\ScopeFiltrationService` - filters promotions with appropriate scopes
- `Oro\Bundle\RuleBundle\RuleFiltration\ExpressionLanguageRuleFiltrationServiceDecorator` - filters promotions if their expressions are evaluated as true
- `Oro\Bundle\PromotionBundle\RuleFiltration\CurrencyFiltrationService` - filters promotions by currency
- `Oro\Bundle\PromotionBundle\RuleFiltration\ScheduleFiltrationService` - filters promotions with actual schedules
- `Oro\Bundle\PromotionBundle\RuleFiltration\CouponFiltrationService` - filters promotions that have the `useCoupons` flag by applied coupons from context
- `Oro\Bundle\PromotionBundle\RuleFiltration\MatchingItemsFiltrationService` - filters promotions if some of their products match line items' products given from context
- `Oro\Bundle\PromotionBundle\RuleFiltration\ShippingFiltrationService` - filters shipping promotions by given shipping method from context
- `Oro\Bundle\RuleBundle\RuleFiltration\StopProcessingRuleFiltrationServiceDecorator` - filters out successors of promotion with `Stop Further Rule Processing` flag set, note that promotions are sorted by `Sort Order`

**<a name="context-data-converters">Context Data Converters</a>**

Promotions are filtered based on context. Each entity to which promotions can be applied, must have its own context converter.

If you need to support a new source entity, you should create a class that implements `Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface` and tag its service with `'oro_promotion.promotion_context_converter'`, in order to be able to convert this entity into context.
```
    app.promotion.custom_entity_context_data_converter:
        class: AppBundle\Promotion\CustomEntityContextDataConverter
        public: false
        tags:
            - { name: 'oro_promotion.promotion_context_converter' }
```

**<a name="add-new-filter">Add new filter</a>**

You can create your own promotion filtration service to apply additional restrictions based on the context from the context converter.
First, you need to create a class that implements `Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface` and contains the required filtration logic.
Next, define a service for this class which decorates `oro_promotion.rule_filtration.service` and accepts the decorated service as a parameter:
```
    app.promotion.rule_filtration.my_filter:
        class: AppBundle\Promotion\RuleFiltration\MyFilterFiltrationService
        public: false
        decorates: oro_promotion.rule_filtration.service
        decoration_priority: 300
        arguments:
            - '@app.promotion.rule_filtration.my_filter.inner'
```
Please keep in mind the `decoration_priority` affects the order in which filters will be executed.

**<a name="filters-skippability-during-checkout">Filters skippability during checkout</a>**

At checkout, coupons can be applied before the user provides the information based on which the promotion is calculated. For example, a shipping promotion can be applied by coupon at the first checkout step before a shipping method is chosen (that is why `ShippingFiltrationService` would filter this promotion out). Therefore, some filters need to be skipped during the coupon application process.

As a result, filters should support skippability based on the option from the context (see `AbstractSkippableFiltrationService::SKIP_FILTERS_KEY`).

To make you filters skippable, you may inherit `AbstractSkippableFiltrationService` or implement skipping logic on your own.

To skip a filter during coupon application, the `disableFilter` method should be called for the `oro_promotion.handler.frontend_coupon_handler` service with the filter's class name:
```
    oro_promotion.handler.frontend_coupon_handler:
        calls:
            - [disableFilter, ['Oro\Bundle\PromotionBundle\RuleFiltration\ShippingFiltrationService']]
```

## Discount Strategy

The way promotions discounts will be aggregated is defined by the Discount Strategy. It is specified in the system config. To get an active strategy, `Oro\Bundle\PromotionBundle\Discount\Strategy\StrategyProvider` is used. There are two discount strategies: 

- Profitable - the most profitable discount is applied
- Apply all - all discounts are applied in the order given by the `sortOrder` property of the promotion

In order to add an additional strategy, create a class that implements `Oro\Bundle\PromotionBundle\Discount\Strategy\StrategyInterface` and tag its service with the`oro_promotion.discount_strategy` tag.

The strategy decides which discounts should be applied. All information needed for discount calculation flow is stored inside `Oro\Bundle\PromotionBundle\Discount\DiscountContext`, as described in [discount applying and calculation](#discount-applying-and-calculation). This information may be used to make a strategy decision or to debug the way discount calculations were made. The strategy also decreases appropriate subtotals. Please keep in mind that subtotals must not get negative values as implemented here `Oro\Bundle\PromotionBundle\Discount\Strategy\AbstractStrategy::getSubtotalWithDiscount`.

## Applied Promotions


When saving the `Oro\Bundle\OrderBundle\Entity\Order` entity, all discounts from `Oro\Bundle\PromotionBundle\Discount\DiscountContext` are converted to `Oro\Bundle\PromotionBundle\Entity\AppliedDiscount` entities. In addition, based on the provided discount information, `Oro\Bundle\PromotionBundle\Manager\AppliedPromotionManager` creates `Oro\Bundle\PromotionBundle\Entity\AppliedPromotion`. `Oro\Bundle\PromotionBundle\Entity\AppliedPromotion` stores promotions and their discounts in the state in which they were at the time of use. So, even if a promotion was changed or deleted, you can use the old promotion configuration for discount calculation.
In order to disable the saved `Oro\Bundle\PromotionBundle\Entity\AppliedPromotion`, use `Oro\Bundle\PromotionBundle\Discount\DisabledDiscountDecorator`, `Oro\Bundle\PromotionBundle\Discount\DisabledDiscountContextDecorator`, `Oro\Bundle\PromotionBundle\Discount\DisabledDiscountLineItemDecorator` decorators that help ignore the discount that the applied promotion gives.
