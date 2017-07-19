Oro\Bundle\PromotionBundle\OroPromotionBundle
===============================================

Table of Contents
-----------------
 - [Description](#description)
 - [Discounts](#discounts)
 - [Promotions Filtration](#promotions-filtration)
 - [Discount Strategy](#discount-strategy)

Description
------------

This bundle introduces promotions functionality.
Administrator can setup discount by adding promotions.
Promotions have basic info (name, sort order, status, etc.), schedule, discount options, conditions (with scope and expression), matching items.

The OroPromotionBundle introduces ability to setup promotions that if matched give discounts during checkout process. This is mainly done by `Oro\Bundle\PromotionBundle\Provider\SubtotalProvider` that add shipping and usual discounts subtotals. It uses `Oro\Bundle\PromotionBundle\Executor\PromotionExecutor` in order to find out discount information that stored as `Oro\Bundle\PromotionBundle\Discount\DiscountContext`.

Discounts
------------

Each promotion has `Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration` attached to it, with help of this configuration `Oro\Bundle\PromotionBundle\Executor\PromotionExecutor` using `Oro\Bundle\PromotionBundle\Discount\DiscountFactory` can create discount that implements `Oro\Bundle\PromotionBundle\Discount\DiscountInterface`. System powered by three types of discounts:
- `Oro\Bundle\PromotionBundle\Discount\OrderDiscount` that gives discount on the order level
- `Oro\Bundle\PromotionBundle\Discount\LineItemsDiscount` that gives discount on line-item level
- `Oro\Bundle\PromotionBundle\Discount\BuyXGetYDiscount` that gives Buy X Get Y type of discount
- `Oro\Bundle\PromotionBundle\Discount\ShippingDiscount` that gives shipping discount

**Add discount**
In order to introduce new discount, that can be selected in promotion configuration you should at first create discount class that implements `Oro\Bundle\PromotionBundle\Discount\DiscountInterface`, you can use `Oro\Bundle\PromotionBundle\Discount\AbstractDiscount` as a base class for it. After that register your discount as `shared: false` service, and add it to the `Oro\Bundle\PromotionBundle\Discount\DiscountFactory` by invokind `addType` method in service definition:
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
Also you need to specify FormType information for your discount. At first create FormType for it, you can use some of already available for reference, for example `Oro\Bundle\PromotionBundle\Form\Type\LineItemDiscountOptionsType`. After that add it to the `Oro\Bundle\PromotionBundle\Provider\DiscountFormTypeProvider` in services:
```
    oro_promotion.discount_type_to_form_type_provider:
        class: Oro\Bundle\PromotionBundle\Provider\DiscountFormTypeProvider
        calls:
            - ['setDefaultFormType', ['oro_promotion_order_discount_options']]
            - ['addFormType', ['order', 'oro_promotion_order_discount_options']]
            - ['addFormType', ['line_item', 'oro_promotion_line_item_discount_options']]
            - ['addFormType', ['buy_x_get_y', 'oro_promotion_buy_x_get_y_discount_options']]
            - ['addFormType', ['buy_x_get_y', 'oro_promotion_buy_x_get_y_discount_options']]
            - ['addFormType', ['my_discount', 'my_discount_options_form_type_alias']]
```

During implementation of new discount you may be will be in need of support some custom source entity based on that information discount will be calculated. In `Oro\Bundle\PromotionBundle\Executor\PromotionExecutor` source entity converted into `Oro\Bundle\PromotionBundle\Discount\DiscountContext` with help of DiscountContextConverter, you can use `Oro\Bundle\PromotionBundle\Discount\Converter\CheckoutDiscountContextConverter` as example.

Promotions Filtration
------------

During the promotions calculations with help of `Oro\Bundle\PromotionBundle\Provider\PromotionProvider` received list of applicable promotions. For getting only suitable promotions, it uses filters. By default there are next filters:
- `Oro\Bundle\RuleBundle\RuleFiltration\EnabledRuleFiltrationServiceDecorator` - filters enabled promotions
- `Oro\Bundle\PromotionBundle\RuleFiltration\ScopeFiltrationService` - filters promotions with appropriate scopes
- `Oro\Bundle\RuleBundle\RuleFiltration\ExpressionLanguageRuleFiltrationServiceDecorator` - filters promotions if its expression evaluates as true
- `Oro\Bundle\PromotionBundle\RuleFiltration\CurrencyFiltrationService` - filters promotions by currency
- `Oro\Bundle\PromotionBundle\RuleFiltration\ScheduleFiltrationService` - filters promotions with actual schedules
- `Oro\Bundle\PromotionBundle\RuleFiltration\MatchingItemsFiltrationService` - filters promotions if some of its products match line items' products given in context
- `Oro\Bundle\RuleBundle\RuleFiltration\StopProcessingRuleFiltrationServiceDecorator` - filters out successors of promotion with `Stop Further Rule Processing` flag set, note that promotions are sorted by `Sort Order`

**Context convertes**

Promotions are filtered base on context. So each entity to which promotions can be applied must have its own context converter.

If you will be in need of support some custom source entity, you should create class that implements `Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface` and tag it's service with 'oro_promotion.promotion_context_converter', in order to be able to convert this entity into context.

```
    app.promotion.custom_entity_context_data_converter:
        class: AppBundle\Promotion\CustomEntityContextDataConverter
        public: false
        tags:
            - { name: 'oro_promotion.promotion_context_converter' }
```

**Add promotion filter**

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