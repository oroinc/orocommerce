<?php

namespace OroB2B\Bundle\PricingBundle\Async\Message;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Util\JSON;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class PriceListChangeMessageFactory
{
    const PRICE_LIST = 'priceList';
    const PRODUCT = 'product';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param PriceList $priceList
     * @param Product|null $product
     * @return PriceRuleCalculateMessage
     */
    public function create(PriceList $priceList, Product $product = null)
    {
        return new PriceRuleCalculateMessage($priceList, $product);
    }

    /**
     * @param PriceRuleCalculateMessage $message
     * @return array
     */
    public function messageToArray(PriceRuleCalculateMessage $message)
    {
        return [
            self::PRICE_LIST => $message->getPriceList()->getId(),
            self::PRODUCT => $message->getProduct() ? $message->getProduct()->getId() : null,
        ];
    }

    /**
     * @param MessageInterface $message
     * @return PriceRuleCalculateMessage
     * @internal param string $messageString
     */
    public function createFromQueueMessage(MessageInterface $message)
    {
        $messageData = JSON::decode($message->getBody());

        $priceList = $this->getPriceList($messageData);
        if (!$priceList) {
            throw new Exception\InvalidMessageException(
                sprintf(
                    'Message is invalid. Price List was not found. message: "%s"',
                    $message->getBody()
                )
            );
        }
        $product = $this->getProduct($messageData);

        return $this->create($priceList, $product);
    }

    /**
     * @param array $messageData
     * @return null|PriceList
     */
    protected function getPriceList(array $messageData)
    {
        if (empty($messageData[self::PRICE_LIST])) {
            return null;
        }

        return $this->registry
            ->getManagerForClass(PriceList::class)
            ->find(PriceList::class, $messageData[self::PRICE_LIST]);
    }

    /**
     * @param array $messageData
     * @return null|PriceList
     */
    protected function getProduct(array $messageData)
    {
        if (empty($messageData[self::PRODUCT])) {
            return null;
        }

        return $this->registry
            ->getManagerForClass(Product::class)
            ->find(PriceList::class, $messageData[self::PRODUCT]);
    }
}