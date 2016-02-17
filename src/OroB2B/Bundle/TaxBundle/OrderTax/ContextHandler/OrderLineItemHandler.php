<?php

namespace OroB2B\Bundle\TaxBundle\OrderTax\ContextHandler;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\TaxBundle\Entity\Repository\AbstractTaxCodeRepository;
use OroB2B\Bundle\TaxBundle\Event\ContextEvent;
use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Model\TaxCodeInterface;
use OroB2B\Bundle\TaxBundle\Provider\TaxationAddressProvider;

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
    protected $accountTaxCodeClass;

    /**
     * @var string
     */
    protected $orderLineItemClass;

    /**
     * @var array
     */
    protected $taxCodes = [];

    /**
     * @param TaxationAddressProvider $addressProvider
     * @param DoctrineHelper $doctrineHelper
     * @param string $productTaxCodeClass
     * @param string $accountTaxCodeClass
     * @param string $orderLineItemClass
     */
    public function __construct(
        TaxationAddressProvider $addressProvider,
        DoctrineHelper $doctrineHelper,
        $productTaxCodeClass,
        $accountTaxCodeClass,
        $orderLineItemClass
    ) {
        $this->addressProvider = $addressProvider;
        $this->doctrineHelper = $doctrineHelper;
        $this->productTaxCodeClass = $productTaxCodeClass;
        $this->accountTaxCodeClass = $accountTaxCodeClass;
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

        $context->offsetSet(Taxable::DIGITAL_PRODUCT, $this->isDigital($lineItem));
        $context->offsetSet(Taxable::PRODUCT_TAX_CODE, $this->getTaxCode($lineItem, TaxCodeInterface::TYPE_PRODUCT));
        $context->offsetSet(Taxable::ACCOUNT_TAX_CODE, $this->getTaxCode($lineItem, TaxCodeInterface::TYPE_ACCOUNT));
    }

    /**
     * @param OrderLineItem $lineItem
     * @return bool
     */
    protected function isDigital(OrderLineItem $lineItem)
    {
        $productTaxCode = $this->getTaxCode($lineItem, TaxCodeInterface::TYPE_PRODUCT);

        if (null === $productTaxCode) {
            return false;
        }

        $billingAddress = $lineItem->getOrder()->getBillingAddress();
        $shippingAddress = $lineItem->getOrder()->getShippingAddress();

        $address = $this->addressProvider->getAddressForTaxation($billingAddress, $shippingAddress);

        return $this->addressProvider->isDigitalProductTaxCode($address->getCountry()->getIso2Code(), $productTaxCode);
    }

    /**
     * @param OrderLineItem $lineItem
     * @param string $type
     * @return null|string
     */
    protected function getTaxCode(OrderLineItem $lineItem, $type)
    {
        $cacheKey = implode(':', [$type, $lineItem->getId()]);

        if (array_key_exists($cacheKey, $this->taxCodes)) {
            return $this->taxCodes[$cacheKey];
        }

        if ($lineItem->getProduct() === null) {
            $this->taxCodes[$cacheKey] = null;

            return null;
        }

        $taxCode = $this->getRepository($type)->findOneByEntity((string)$type, $lineItem->getProduct());

        $this->taxCodes[$cacheKey] = $taxCode ? $taxCode->getCode() : null;

        return $this->taxCodes[$cacheKey];
    }

    /**
     * @param string $type
     * @return AbstractTaxCodeRepository
     */
    protected function getRepository($type)
    {
        if ($type === TaxCodeInterface::TYPE_PRODUCT) {
            return $this->doctrineHelper->getEntityRepositoryForClass($this->productTaxCodeClass);
        } elseif ($type === TaxCodeInterface::TYPE_ACCOUNT) {
            return $this->doctrineHelper->getEntityRepositoryForClass($this->accountTaxCodeClass);
        }

        throw new \InvalidArgumentException(sprintf('Unknown type: %s', $type));
    }
}
