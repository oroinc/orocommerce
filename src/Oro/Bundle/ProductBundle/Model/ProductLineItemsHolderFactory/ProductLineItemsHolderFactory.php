<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderFactory;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderDTO;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderInterface;
use Oro\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface;

/**
 * Creates a DTO implementing {@see ProductLineItemsHolderInterface} to wrap the specified line items taking into
 * account the context of the original line items holder.
 */
class ProductLineItemsHolderFactory implements ProductLineItemsHolderFactoryInterface
{
    /**
     * @param Collection<ProductLineItemInterface>|array<ProductLineItemInterface> $lineItems
     *
     * @return ProductLineItemsHolderInterface
     */
    #[\Override]
    public function createFromLineItems(Collection|array $lineItems): ProductLineItemsHolderInterface
    {
        if (!$lineItems instanceof Collection) {
            $lineItems = new ArrayCollection($lineItems);
        }

        $lineItemsHolderDTO = (new ProductLineItemsHolderDTO())
            ->setLineItems($lineItems);

        if ($lineItems->count() > 0) {
            $lineItemsHolder = $this->getOriginalLineItemsHolder($lineItems->first());
            if ($lineItemsHolder !== null) {
                if ($lineItemsHolder instanceof WebsiteAwareInterface) {
                    $lineItemsHolderDTO->setWebsite($lineItemsHolder->getWebsite());
                }

                if ($lineItemsHolder instanceof CustomerOwnerAwareInterface) {
                    $lineItemsHolderDTO
                        ->setCustomer($lineItemsHolder->getCustomer())
                        ->setCustomerUser($lineItemsHolder->getCustomerUser());
                }
            }
        }

        return $lineItemsHolderDTO;
    }

    private function getOriginalLineItemsHolder(ProductLineItemInterface $lineItem): ?ProductLineItemsHolderInterface
    {
        if ($lineItem instanceof ProductLineItemsHolderAwareInterface) {
            return $lineItem->getLineItemsHolder();
        }

        return null;
    }
}
