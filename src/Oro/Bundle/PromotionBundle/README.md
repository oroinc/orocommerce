Oro\Bundle\PromotionBundle\OroPromotionBundle
===============================================

Table of Contents
-----------------
 * [Description](#description)
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
 * [Discount Strategy](#discount-strategy)

Description
------------

This bundle introduces promotions functionality.
Administrator can setup discounts by adding promotions.
Promotions have basic info: name, sort order, status, schedule, discount options, conditions with scope and expression, matching items, etc. Matched promotions give discounts during checkout process.

Discounts
------------

**<a name="discoucnts-creation-and-types">Creation and Types</a>**

Each promotion has `Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration` attached to it, with help of this configuration `Oro\Bundle\PromotionBundle\Executor\PromotionExecutor` using `Oro\Bundle\PromotionBundle\Discount\DiscountFactory` can create discount that implements `Oro\Bundle\PromotionBundle\Discount\DiscountInterface`. System powered by next types of discounts:
- `Oro\Bundle\PromotionBundle\Discount\OrderDiscount` that gives discount on the order level
- `Oro\Bundle\PromotionBundle\Discount\LineItemsDiscount` that gives discount on line-item level
- `Oro\Bundle\PromotionBundle\Discount\BuyXGetYDiscount` that gives Buy X Get Y type of discount
- `Oro\Bundle\PromotionBundle\Discount\ShippingDiscount` that gives shipping discount

**<a name="add-discount">Add discount</a>**

In order to add new discount, that can be selected in promotion configuration you should at first create discount class that implements `Oro\Bundle\PromotionBundle\Discount\DiscountInterface`, you can use `Oro\Bundle\PromotionBundle\Discount\AbstractDiscount` as a base class for it. After that register your discount as `shared: false` service, and add it to the `Oro\Bundle\PromotionBundle\Discount\DiscountFactory` by invoking `addType` method in service definition:
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

Also you need to specify FormType information for your discount. At first create FormType for it, you can use some of already available for reference, for example `Oro\Bundle\PromotionBundle\Form\Type\LineItemDiscountOptionsType`. After that add it to the `Oro\Bundle\PromotionBundle\Provider\DiscountFormTypeProvider` in services:
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

When adding new discount likely will be needed to add new discount options. Discount options actually stored as array inside `Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration::options` and during promotion execution passed to the discounts configure method, for example `Oro\Bundle\PromotionBundle\Discount\LineItemsDiscount::configure`, where options become resolved and you can safely store them and use later for calculations.
In order to connect formType fields with discount options, they should have the same key, therefore useful to specify this key as discount's constant and use it during form field definition like `Oro\Bundle\PromotionBundle\Discount\LineItemsDiscount::APPLY_TO` used inside `Oro\Bundle\PromotionBundle\Form\Type\LineItemDiscountOptionsType::buildForm`.


**<a name="discount-applying-and-calculation">Discount applying and calculation<a>**

When discount already created by `Oro\Bundle\PromotionBundle\Discount\DiscountFactory` and configured, discount strategy applying discounts to the `Oro\Bundle\PromotionBundle\Discount\DiscountContext`. This context used as storage for applicable discounts, calculated discounts values and related information that needed during discount calculation process.

At first strategy iterate over all discounts and call `\Oro\Bundle\PromotionBundle\Discount\DiscountInterface::apply`, where discount make decision applicable it for current situation or not, and if yes, add itself into one of applicable storage: `Oro\Bundle\PromotionBundle\Discount\DiscountContext::$subtotalDiscounts`, `Oro\Bundle\PromotionBundle\Discount\DiscountContext::$shippingDiscounts` or `Oro\Bundle\PromotionBundle\Discount\DiscountLineItem::$discounts` that stored in `Oro\Bundle\PromotionBundle\Discount\DiscountContext::$lineItems`.

Later strategy will iterate over discounts that added to the `Oro\Bundle\PromotionBundle\Discount\DiscountContext` and will execute `Oro\Bundle\PromotionBundle\Discount\DiscountInterface::calculate`, where discount will check support it passed entity, and if yes, it should make calculations and return discount amount as float value.

**<a name="discount-context-converters">Discount Context Converters</a>**

`Oro\Bundle\PromotionBundle\Discount\DiscountContext` created based on the source entity by discount context converters. If you need to support new source entity, you should create class that implements `Oro\Bundle\PromotionBundle\Discount\Converter\DiscountContextConverterInterface` and tag it's service with `'oro_promotion.discount_context_converter'`, in order to be able to convert this entity into context.
```
    app.promotion.custom_entity_context_data_converter:
        class: AppBundle\Promotion\CustomEntityContextDataConverter
        public: false
        tags:
            - { name: 'oro_promotion.discount_context_converter' }
```
Discount converter should return `Oro\Bundle\PromotionBundle\Discount\DiscountContext`. Also please keep in mind that line items in `Oro\Bundle\PromotionBundle\Discount\DiscountContext::lineItems` stored in the some unified format `Oro\Bundle\PromotionBundle\Discount\DiscountLineItem` to that line items of `Oro\Bundle\CheckoutBundle\Entity\Checkout` or `Oro\Bundle\ShoppingListBundle\Entity\ShoppingList` are converted.

Promotions Filtration
------------

**<a name="flow-and-filters-types">Flow and Filters Types</a>**

During the promotions calculations with help of `Oro\Bundle\PromotionBundle\Provider\PromotionProvider` received list of applicable promotions. For getting only suitable promotions, it uses filters. By default there are next filters:
- `Oro\Bundle\RuleBundle\RuleFiltration\EnabledRuleFiltrationServiceDecorator` - filters enabled promotions
- `Oro\Bundle\PromotionBundle\RuleFiltration\ScopeFiltrationService` - filters promotions with appropriate scopes
- `Oro\Bundle\RuleBundle\RuleFiltration\ExpressionLanguageRuleFiltrationServiceDecorator` - filters promotions if its expression evaluates as true
- `Oro\Bundle\PromotionBundle\RuleFiltration\CurrencyFiltrationService` - filters promotions by currency
- `Oro\Bundle\PromotionBundle\RuleFiltration\ScheduleFiltrationService` - filters promotions with actual schedules
- `Oro\Bundle\PromotionBundle\RuleFiltration\MatchingItemsFiltrationService` - filters promotions if some of its products match line items' products given in context
- `Oro\Bundle\RuleBundle\RuleFiltration\StopProcessingRuleFiltrationServiceDecorator` - filters out successors of promotion with `Stop Further Rule Processing` flag set, note that promotions are sorted by `Sort Order`

**<a name="context-data-converters">Context Data Converters</a>**

Promotions are filtered base on context. So each entity to which promotions can be applied must have its own context converter.

If you need to support new source entity, you should create class that implements `Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface` and tag it's service with `'oro_promotion.promotion_context_converter'`, in order to be able to convert this entity into context.
```
    app.promotion.custom_entity_context_data_converter:
        class: AppBundle\Promotion\CustomEntityContextDataConverter
        public: false
        tags:
            - { name: 'oro_promotion.promotion_context_converter' }
```

**<a name="add-new-filter">Add new filter</a>**

You can create your own promotions' filtration service to apply additional restrictions based on context from context converter.
At first, you need to create class that implements `Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface` and contains needed filtration logic.
Secondly, define a service for this class which decorates `oro_promotion.rule_filtration.service` and accepts decorated service as parameter:
```
    app.promotion.rule_filtration.my_filter:
        class: AppBundle\Promotion\RuleFiltration\MyFilterFiltrationService
        public: false
        decorates: oro_promotion.rule_filtration.service
        decoration_priority: 300
        arguments:
            - '@app.promotion.rule_filtration.my_filter.inner'
```
Please keep in mind `decoration_priority` that affects the order in which filters will be executed.

Discount Strategy
------------

The way in that promotions discounts will be aggregated is defined by Discount Strategy. It specified in system config and for getting active strategy used `Oro\Bundle\PromotionBundle\Discount\Strategy\StrategyProvider`. There are two discount strategies:
- profitable - the most profitable shipping discount and the most profitable not shipping discount will be applied
- apply all - all discounts will be applied

In order to add additional strategy you should create class that implements `Oro\Bundle\PromotionBundle\Discount\Strategy\StrategyInterface` and tag it's service with `oro_promotion.discount_strategy` tag.

Strategy make a decision which discounts should be applied. All information that needed for discount calculation flow stored inside `Oro\Bundle\PromotionBundle\Discount\DiscountContext` as described in [discount applying and calculation](#discount-applying-and-calculation). This information may be used to make strategy decision or to debug how discount calculations were made. Also strategy decreases appropriate subtotals, please keep in mind that subtotals must not get negative values as implemented here `Oro\Bundle\PromotionBundle\Discount\Strategy\AbstractStrategy::getSubtotalWithDiscount`.