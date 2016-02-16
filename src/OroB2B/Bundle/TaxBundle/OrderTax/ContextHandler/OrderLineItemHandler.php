<?php

namespace OroB2B\Bundle\TaxBundle\OrderTax\ContextHandler;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Event\ContextEvent;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\TaxBundle\Provider\TaxationAddressProvider;
use OroB2B\Bundle\TaxBundle\Entity\Repository\ProductTaxCodeRepository;
use OroB2B\Bundle\TaxBundle\Entity\Repository\AccountTaxCodeRepository;

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

        $context->offsetSet(Taxable::DIGITAL_PRODUCT, $this->isDigitProduct($lineItem));
        $context->offsetSet(Taxable::PRODUCT_TAX_CODE, $this->getProductTaxCode($lineItem));
        $context->offsetSet(Taxable::ACCOUNT_TAX_CODE, $this->getAccountTaxCode($lineItem));
    }

    /**
     * @param OrderLineItem $lineItem
     * @return bool
     */
    protected function isDigitProduct(OrderLineItem $lineItem)
    {
        $productTaxCode = $this->getProductTaxCode($lineItem);

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
     * @return null|string
     */
    protected function getProductTaxCode(OrderLineItem $lineItem)
    {
        if (array_key_exists($lineItem->getId(), $this->taxCodes)) {
            return $this->taxCodes[$lineItem->getId()];
        }

        if ($lineItem->getProduct() === null) {
            $this->taxCodes[$lineItem->getId()] = null;

            return $this->taxCodes[$lineItem->getId()];
        }

        /** @var ProductTaxCodeRepository $productTaxCodeRepository */
        $productTaxCodeRepository = $this->doctrineHelper->getEntityRepositoryForClass($this->productTaxCodeClass);
        $productTaxCode = $productTaxCodeRepository->findOneByProduct($lineItem->getProduct());

        $this->taxCodes[$lineItem->getId()] = $productTaxCode ? $productTaxCode->getCode() : null;

        return $this->taxCodes[$lineItem->getId()];
    }

    /**
     * @param OrderLineItem $lineItem
     * @return null|string
     */
    protected function getAccountTaxCode(OrderLineItem $lineItem)
    {
        if ($lineItem->getOrder() === null
            || $lineItem->getOrder()->getAccount() === null
        ) {
            return null;
        }

        /** @var AccountTaxCodeRepository $accountTaxCodeRepository */
        $accountTaxCodeRepository = $this->doctrineHelper->getEntityRepositoryForClass($this->accountTaxCodeClass);
        $accountTaxCode = $accountTaxCodeRepository->findOneByAccount($lineItem->getOrder()->getAccount());

        return $accountTaxCode->getCode();
    }
}
