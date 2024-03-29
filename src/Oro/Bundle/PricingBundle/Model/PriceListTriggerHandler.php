<?php

namespace Oro\Bundle\PricingBundle\Model;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Provides a set of methods to handle price list related MQ topics.
 *
 * @see \Oro\Bundle\PricingBundle\Async\PriceListMessageFilter
 */
class PriceListTriggerHandler
{
    /** @var MessageProducerInterface */
    private $messageProducer;

    public function __construct(MessageProducerInterface $messageProducer)
    {
        $this->messageProducer = $messageProducer;
    }

    /**
     * @param string          $topic
     * @param PriceList       $priceList
     * @param Product[]|int[] $products
     */
    public function handlePriceListTopic(string $topic, PriceList $priceList, array $products = []): void
    {
        if (!$priceList->isActive()) {
            return;
        }

        if ($products) {
            $productIds = [];
            $priceListOrganizationId = $priceList->getOrganization()->getId();

            foreach ($products as $product) {
                if (null !== $product) {
                    if (\is_object($product) && $priceListOrganizationId !== $product->getOrganization()->getId()) {
                        continue;
                    }

                    $productIds[] = $product instanceof Product ? $product->getId() : $product;
                }
            }
            if ($this->isMessageShouldBeSend($products, $productIds)) {
                $this->messageProducer->send($topic, ['product' => [$priceList->getId() => $productIds]]);
            }
        } else {
            $this->messageProducer->send($topic, ['product' => [$priceList->getId() => []]]);
        }
    }

    private function isMessageShouldBeSend(array $products, array $productIds): bool
    {
        return $products === [null] || count($productIds) !== 0;
    }
}
