<?php

namespace Oro\Bundle\ShoppingListBundle\EventListener;

use Doctrine\ORM\Event\PostFlushEventArgs;
use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\LineItemChecksumGeneratorInterface;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;

/**
 * Update checksum for line item to check duplicate.
 */
class LineItemEventListener
{
    private LineItemChecksumGeneratorInterface $lineItemChecksumGenerator;

    private array $suspendedLineItems = [];

    public function __construct(LineItemChecksumGeneratorInterface $lineItemChecksumGenerator)
    {
        $this->lineItemChecksumGenerator = $lineItemChecksumGenerator;
    }

    public function prePersist(LineItem $lineItem): void
    {
        if ($lineItem->getShoppingList()?->getId() && $lineItem->getProduct()?->getId()) {
            $this->updateChecksum($lineItem);
        } else {
            $this->suspendedLineItems[] = $lineItem;
        }
    }

    public function preUpdate(LineItem $lineItem): void
    {
        if ($lineItem->getShoppingList()?->getId() && $lineItem->getProduct()?->getId()) {
            $this->updateChecksum($lineItem);
        } else {
            $this->suspendedLineItems[] = $lineItem;
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if (!empty($this->suspendedLineItems)) {
            $lineItems = $this->suspendedLineItems;
            $this->suspendedLineItems = [];
            $manager = $args->getObjectManager();

            foreach ($lineItems as $lineItem) {
                if ($this->updateChecksum($lineItem)) {
                    $manager->persist($lineItem);
                    $manager->flush($lineItem);
                }
            }
        }
    }

    private function updateChecksum(LineItem $lineItem): ?string
    {
        if (!$lineItem->getChecksum()) {
            $checksum = $this->lineItemChecksumGenerator->getChecksum($lineItem);
            if ($checksum !== null) {
                $lineItem->setChecksum($checksum);

                return $checksum;
            }
        }

        return null;
    }
}
