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

The OroPromotionBundle introduces ability to setup promotions that if matched give discounts during checkout process. This is mainly done by `Oro\Bundle\PromotionBundle\Provider\SubtotalProvider` that add shipping and usual discounts subtotals. It uses `Oro\Bundle\PromotionBundle\Executor\PromotionExecutor` in order to find out discount information that stored as `Oro\Bundle\PromotionBundle\Discount\DiscountContext`.

Discounts
------------

Each promotion has `Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration` attached to it, with help of this configuration `Oro\Bundle\PromotionBundle\Executor\PromotionExecutor` using `Oro\Bundle\PromotionBundle\Discount\DiscountFactory` can create discount that implements `Oro\Bundle\PromotionBundle\Discount\DiscountInterface`. System powered by three types of discounts:
- `Oro\Bundle\PromotionBundle\Discount\OrderDiscount` that gives discount on the order level
- `Oro\Bundle\PromotionBundle\Discount\LineItemsDiscount` that gives discount on line-item level
- `Oro\Bundle\PromotionBundle\Discount\BuyXGetYDiscount` that gives Buy X Get Y type of discount

**Add discount**
In order to introduce new discount, that can be selected in promotion configuration you should at first create discount class that implements `Oro\Bundle\PromotionBundle\Discount\DiscountInterface`, you can use `Oro\Bundle\PromotionBundle\Discount\AbstractDiscount` as base. After that register your discount as `shared: false` service, and add it to the `Oro\Bundle\PromotionBundle\Discount\DiscountFactory`:
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
Also you need to specify FormType information. At first create FormType for your brand new discount, you can use some of already available for reference, for example `Oro\Bundle\PromotionBundle\Form\Type\LineItemDiscountOptionsType`. After that add it to the `Oro\Bundle\PromotionBundle\Provider\DiscountFormTypeProvider` in services:
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
- `Oro\Bundle\RuleBundle\RuleFiltration\EnabledRuleFiltrationServiceDecorator` - for filtering only enabled promotions
- `Oro\Bundle\PromotionBundle\RuleFiltration\ScopeFiltrationService` - for filtering promotions with appropriate scopes
- `Oro\Bundle\RuleBundle\RuleFiltration\ExpressionLanguageRuleFiltrationServiceDecorator` - for filtering according promotion expression
- `Oro\Bundle\PromotionBundle\RuleFiltration\CurrencyFiltrationService` - for filtering promotions by currency
- `Oro\Bundle\PromotionBundle\RuleFiltration\ScheduleFiltrationService` - filtering promotions with actual schedules
- `Oro\Bundle\PromotionBundle\RuleFiltration\MatchingItemsFiltrationService` - filtering promotions witch matching items
- `Oro\Bundle\RuleBundle\RuleFiltration\StopProcessingRuleFiltrationServiceDecorator` - filter out next promotions if promotion has this flag, please keep in mind that promotions checked by specified in their definition order


**Add promotion filter**

You can create your own filtration service for promotions filtration. For create own filter you should create class that implement `Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface` and decorate `oro_promotion.rule_filtration.service`:
```
    app.promotion.rule_filtration.my_filter:
        class: AppBundle\Promotion\RuleFiltration\MyFilterFiltrationService
        public: false
        decorates: oro_promotion.rule_filtration.service
        decoration_priority: 300
        autowire: true
```
Please keep in mind `decoration_priority` that affects the order in which filters will be executed.

Filters receive array with context information that helps to make decision. If you will be in needed of support some custom source entity, you should create class that implements `Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface` and tag it service as 'oro_promotion.promotion_context_converter', in order to be able to convert this entity into context.

Discount Strategy
------------
The way in that promotions discounts will be aggregated is defined by Discount Strategy. It specified in system config and for getting active strategy used `Oro\Bundle\PromotionBundle\Discount\Strategy\StrategyProvider`. There are two discount strategy:
- profitable - only one most profitable discount will be applied
- apply all - all discounts will be applied

In order to add additional strategy you should create class that implements `Oro\Bundle\PromotionBundle\Discount\Strategy\StrategyInterface` and tag it's service with `oro_promotion.discount_strategy` tag.