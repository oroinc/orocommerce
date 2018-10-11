# Search Relevance Weight

This article describes the purpose of search relevance weigh and describes the way to customize this relevance 
in the website search index.

## What Search Relevance Weight Is

_Search relevance weight_ (also called _search weight_ or _search relevance_) is a positive decimal number that affects the order of search results. It is used as a multiplier for the original relevance calculated by the search engine. 
The original search relevance may differ depending on the search query and additional conditions. The multiplier enables a developer to change the original search relevance which in turn changes the order of the results.

From the code level perspective, search relevance weight is just another search index decimal field called `relevance_weight`. This name is stored in the `Oro\Bundle\SearchBundle\Engine\IndexerInterface::WEIGHT_FIELD` constant that enables a developer to fill this field with the data during the indexation and pass it to the search engine. Then the search engine will start processing the data. 

The default search relevance weight is `1.0`. If no custom value is specified for this multiplier, then the original search engine relevance is used.

## Relevance Weight Customization

To customize search relevance weight, add an event listener to fill the `relevance_weight` field using the `oro_website_search.event.index_entity` event (or a similar event for a specific entity, such as `oro_website_search.event.index_entity.product`) and an associated `Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent` event class.

First, create an event listener class:

```php
<?php

namespace Acme\Bundle\TestBundle\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;

class SetWebsiteSearchRelevanceWeightListener
{
    /**
     * @param IndexEntityEvent $event
     */
    public function onIndexEntity(IndexEntityEvent $event)
    {
        /** @var Product $product */
        foreach ($event->getEntities() as $product) {
            $relevanceWeight = $this->calculateRelevanceWeight($product);
            $event->addField($product->getId(), IndexerInterface::WEIGHT_FIELD, $relevanceWeight);
        }
    }

    /**
     * @param Product $product
     * @return float
     */
    private function calculateRelevanceWeight(Product $product)
    {
        switch ($product->getInventoryStatus()->getId()) {
            case Product::INVENTORY_STATUS_IN_STOCK:
                return 1.0;
            case Product::INVENTORY_STATUS_OUT_OF_STOCK:
                return 0.5;
            default:
                return 0.3;
        }
    }
}
```

Then, register this event listener in the DI container:

```yml
    acme_test.event_listener.website_search.set_website_search_relevance_weight:
        class: 'Acme\Bundle\TestBundle\EventListener\SetWebsiteSearchRelevanceWeightListener'
        tags:
            - { name: kernel.event_listener, event: oro_website_search.event.index_entity.product, method: onIndexEntity }
```

Specify a mapping for the added `relevance_weight` field in the `Resources/config/oro/website_search.yml` file if it wasn't specified before:

```yml
Oro\Bundle\ProductBundle\Entity\Product:
    fields:
      -
        name: relevance_weight
        type: decimal

```

Finally, clear the cache using the `bin/console cache:clear --env=prod` command and trigger reindexation 
of the required entity using the `bin/console oro:website-search:reindex --class=OroProductBundle:Product --env=prod` 
command. 
