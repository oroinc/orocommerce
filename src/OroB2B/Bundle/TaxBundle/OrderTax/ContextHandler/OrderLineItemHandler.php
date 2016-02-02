<?php

namespace OroB2B\Bundle\TaxBundle\OrderTax\ContextHandler;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Event\ContextEvent;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\TaxBundle\Provider\TaxationAddressProvider;
use OroB2B\Bundle\TaxBundle\Entity\Repository\ProductTaxCodeRepository;

class OrderLineItemHandler
{
    /**
     * @var TaxationAddressProvider
     */
    protected $addressProvider;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var string
     */
    protected $productTaxCodeClass;

    /**
     * @var string
     */
    protected $orderLineItemClass;

    /**
     * @param TaxationAddressProvider $addressProvider
     * @param DoctrineHelper $doctrineHelper
     * @param string $productTaxCodeClass
     * @param string $orderLineItemClass
     */
    public function __construct(
        TaxationAddressProvider $addressProvider,
        DoctrineHelper $doctrineHelper,
        $productTaxCodeClass,
        $orderLineItemClass
    ) {
        $this->addressProvider = $addressProvider;
        $this->doctrineHelper = $doctrineHelper;
        $this->productTaxCodeClass = $productTaxCodeClass;
        $this->orderLineItemClass = $orderLineItemClass;
    }
    /**
     * @param ContextEvent $contextEvent
     */
    public function onContextEvent(ContextEvent $contextEvent)
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $contextEvent->getMappingObject();
        $context = $contextEvent->getContext();

        if (!$lineItem instanceof $this->orderLineItemClass) {
            return;
        }

        $context->offsetSet(Taxable::DIGITAL_PRODUCT, $this->isDigitProduct($lineItem));
        $context->offsetSet(Taxable::PRODUCT_TAX_CODE, $this->getProductTaxCode($lineItem));
    }

    /**
     * @param OrderLineItem $lineItem
     * @return bool
     */
    protected function isDigitProduct(OrderLineItem $lineItem)
    {
        $productTaxCode = $this->getProductTaxCode($lineItem);

        if (null === $productTaxCode) {
            return null;
        }

        $address = $this->addressProvider->getAddressForTaxation($lineItem->getOrder());

        return $this->addressProvider->isDigitalProductTaxCode($address->getCountry()->getIso2Code(), $productTaxCode);
    }

    /**
     * @param OrderLineItem $lineItem
     * @return null|string
     */
    protected function getProductTaxCode(OrderLineItem $lineItem)
    {
        if ($lineItem->getProduct() === null) {
            return null;
        }

        /** @var ProductTaxCodeRepository $productTaxCodeRepository */
        $productTaxCodeRepository = $this->doctrineHelper->getEntityRepositoryForClass($this->productTaxCodeClass);
        $productTaxCode = $productTaxCodeRepository->findOneByProduct($lineItem->getProduct());

        return $productTaxCode->getCode();
    }
}
