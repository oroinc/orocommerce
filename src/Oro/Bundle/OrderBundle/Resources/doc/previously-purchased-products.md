Previously Purchased Products
=============================

* [Overview](#overview)
* [Config](#config)
* [Website Search Index](#website-search-index)
    * [onWebsiteSearchIndex](#onwebsitesearchindex)
    * [Index field](#index-field)
* [Reindex Listeners](#reindex-listeners)
    * [ReindexProductLineItemListener](#reindexproductlineitemlistener)
    * [ReindexProductOrderListener](#reindexproductorderlistener)
    * [PreviouslyPurchasedFeatureToggleListener](#previouslypurchasedfeaturetogglelistener)
* [Managers](#managers)
    * [ProductReindexManager](#productreindexmanager)
* [Providers](#providers)
    * [PreviouslyPurchasedConfigProvider](#previouslypurchasedconfigprovider)
    * [LatestOrderedProductsInfoProvider](#latestorderedproductsinfoprovider)
    * [PreviouslyPurchasedOrderStatusesProvider](#previouslypurchasedorderstatusesprovider)
    * [PreviouslyPurchasedOrderStatusesProvider](#previouslypurchasedorderstatusesprovider)
   

Overview
--------

Previously Purchased Products functionality adds the previously purchased products grid to the customer pages under `Account >  Previously Purchased` on the frontend. By default, previously purchased products are disabled. To enable this functionality, go to `System > Configuration > Orders > Purchase History > Enabled Purchase History` in the admin panel.

### Config

Here is an example of the system config section.

```yml
purchase_history:
    children:
         purchase_history:
             children:
                 - oro_order.enable_purchase_history
                 - oro_order.order_previously_purchased_period
```

The `oro_order.enable_purchase_history` option turns the feature on or off.
The `oro_order.order_previously_purchased_period` option contains the number of days used to filter products by date in the [previously purchased products grid](../config/oro/datagrids.yml#L751)

If you need more information about system_config.yml, please see the relevant [documentation](../../../../../../../platform/src/Oro/Bundle/ConfigBundle/Resources/doc/system_configuration.md).

### Website Search Index

Class [Oro\Bundle\OrderBundle\EventListener\WebsiteSearchProductIndexerListener](../../EventListener/WebsiteSearchProductIndexerListener.php)

This listener contains methods which are called when reindex process is running.

#### onWebsiteSearchIndex
```php
public function onWebsiteSearchIndex(IndexEntityEvent $event)
```

This method is triggered when search reindex process starts running. For example, we can start reindex process with the  `oro:website-search:reindex` command.
This method adds new columns to the records with the `oro_product_WEBSITE_ID` index and based on order created_at,
customer_user_id and product_id.

#### Index field
[website_search.yml](../config/oro/website_search.yml)
```yml
Oro\Bundle\ProductBundle\Entity\Product:
    alias: oro_product_WEBSITE_ID
    fields:
      -
        name: ordered_at_by_CUSTOMER_USER_ID
        type: datetime
```

We also added index field which saves information about the date of the last purchase of the product. 


This field is used to select a query in the grid config for select, filter and sort data. For more information, please see [datagrids.yml](../config/oro/datagrids.yml#L751).

```yml
    query:
        select:
            - datetime.ordered_at_by_CUSTOMER_USER_ID as recency
        where:
            and:
                - datetime.ordered_at_by_CUSTOMER_USER_ID >= @oro_order.previously_purchased.configuration->getPreviouslyPurchasedStartDateString()
```

### Reindex Listeners

#### ReindexProductLineItemListener

Class [Oro\Bundle\OrderBundle\EventListener\ORM\ReindexProductLineItemListener](../../EventListener/ORM/ReindexProductLineItemListener.php)

This listener contains methods which are called when the [OrderLineItem](../../Entity/OrderLineItem.php) entity is changed, and if 
all conditions are correct, a message is sent to the message queue to reindex product data.

##### reindexProductOnLineItemCreateOrDelete
```php
public function reindexProductOnLineItemCreateOrDelete(OrderLineItem $lineItem, LifecycleEventArgs $args)
```
This method is triggered when we create or delete an order line item and send a message to the message queue informing that reindex for a product entity is required.
But if the order has unsuitable status, or the feature has been disabled, the message for reindex is not sent.

##### reindexProductOnLineItemUpdate
```php
public function reindexProductOnLineItemUpdate(OrderLineItem $lineItem, PreUpdateEventArgs $event)
```
This method is triggered when we update the "product" field in the order line item entity and send a message to the message queue that reindex for the product entity is required.
But if the order has unsuitable status, or the feature has been disabled, the message is not sent for reindex.

#### ReindexProductOrderListener

Class [Oro\Bundle\OrderBundle\EventListener\ORM\ReindexProductOrderListener](../../EventListener/ORM/ReindexProductOrderListener.php)

This listener contains methods which are called when [Order](../../Entity/Order.php) entity is changed, and if 
all conditions are correct, a message is sent to message queue to reindex product data.

##### processIndexOnOrderStatusChange
```php
public function processIndexOnOrderStatusChange(Order $order, PreUpdateEventArgs $event)
```
This method is triggered when order status is changed. But if order status is not applicable, the message for reindex is not sent.

##### processIndexOnOrderWebsiteChange
```php
public function processIndexOnOrderWebsiteChange(Order $order, PreUpdateEventArgs $event)
```
This method is triggered when order website is changed. But if order status is not applicable, the message for reindex is not sent.

##### processOrderRemove
```php
public function processOrderRemove(Order $order)
```
This method is triggered when an order is removed. But if order status is not applicable, the message for reindex process is not sent.

##### processIndexOnCustomerUserChange
```php
public function processIndexOnCustomerUserChange(Order $order, PreUpdateEventArgs $event)
```
This method is triggered when order was updated and field `customerUser` was changed. But if order status is not applicable, the message for reindex process is not sent.

##### processIndexOnOrderCreatedAtChange
```php
public function processIndexOnOrderCreatedAtChange(Order $order, PreUpdateEventArgs $event)
```
This method is triggered when order was updated and field `createdAt` was changed. But if order status is not applicable, the message for reindex process is not sent.

#### PreviouslyPurchasedFeatureToggleListener

Class [Oro\Bundle\OrderBundle\EventListener\PreviouslyPurchasedFeatureToggleListener](../../EventListener/PreviouslyPurchasedFeatureToggleListener.php)

This listener contains methods which are called when we turn the feature on or off from the system config. 

#### reindexProducts
```php
public function reindexProducts(ConfigUpdateEvent $event)
```
This method is triggered when we change the config setting `enable_purchase_history` and send a message to reindex products in the global or website scope. 

### Managers

#### ProductReindexManager

Class [Oro\Bundle\ProductBundle\Search\Reindex\ProductReindexManager](../../Search/Reindex/ProductReindexManager.php)

This manager contains methods which are used when we need to reindex a product or a collection of products. Please use it when you need
to reindex product data. 

##### reindexProduct
```php
public function reindexProduct(Product $product, $websiteId = null)
```
This method triggers reindex process for the current product. If websiteId is not present, this method takes the default website id.

##### triggerReindexationRequestEvent
```php
public function triggerReindexationRequestEvent(array $productIds, $websiteId = null, $isScheduled = true)
```
This method triggers reindex process for a collection of product ids. 

### Providers

#### PreviouslyPurchasedConfigProvider

The [Oro\Bundle\OrderBundle\Provider\PreviouslyPurchasedConfigProvider](../../Provider/PreviouslyPurchasedConfigProvider.php) class

This provider provides you with the configuration for previously purchased products.

Here is a quick overview of its usage:

##### getDaysPeriod
```php
$this->get('oro_order.previously_purchased.configuration')->getDaysPeriod();
```
Returns the count of days for previously purchased products.

##### getPreviouslyPurchasedStartDateString
```php
$this->get('oro_order.previously_purchased.configuration')->getPreviouslyPurchasedStartDateString()
```

Returns the start date in string format for previously purchased products.

#### LatestOrderedProductsInfoProvider

Class [Oro\Bundle\OrderBundle\Provider\LatestOrderedProductsInfoProvider](../../Provider/LatestOrderedProductsInfoProvider.php)

This provider is used when we need more information who and when bought products in the order.

#### getLatestOrderedProductsInfo
```php
/**
 * Returns information about who and when bought those products
 * [
 *      product id => [
 *          'customer_user_id' => customer user who bought,
 *          'created_at'       => order create \DateTime,
 *      ],
 *      ...
 * ]
 *
 * @param array $productIds
 * @param int   $websiteId
 *
 * @return array
 */
public function getLatestOrderedProductsInfo(array $productIds, $websiteId)
```
Returns information about who and when bought those products.

#### PreviouslyPurchasedOrderStatusesProvider

Class [Oro\Bundle\OrderBundle\Provider\PreviouslyPurchasedOrderStatusesProvider](../../Provider/PreviouslyPurchasedOrderStatusesProvider.php)

This service implements [OrderStatusesProviderInterface](../../Provider/OrderStatusesProviderInterface.php) and contains methods
which returned applicable statuses for the order. For example:

```php 
/**
 * @param AbstractEnumValue|null $status
 * @return bool
 */
protected function isAllowedStatus(AbstractEnumValue $status = null)
{
    // statusProvider implements OrderStatusesProviderInterface
    $availableStatuses = $this->statusesProvider->getAvailableStatuses();
    $statusId = $status !== null ? $status->getId() : null;

    return in_array($statusId, $availableStatuses);
}
```

##### getAvailableStatuses
```php
public function getAvailableStatuses()
```
This method returns an  array of applicable statuses for an order. It is used in [ReindexProductOrderListener](#reindexproductorderlistener)
[ReindexProductLineItemListener](#reindexproductlineitemlistener) and [LatestOrderedProductsInfoProvider](#latestorderedproductsinfoprovider)
