<?php

namespace OroB2B\Bundle\TaxBundle\OrderTax\Mapper;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\TaxBundle\Entity\ProductTaxCode;
use OroB2B\Bundle\TaxBundle\Entity\Repository\ProductTaxCodeRepository;
use OroB2B\Bundle\TaxBundle\Model\Taxable;

class OrderLineItemMapper extends AbstractOrderMapper
{
    const PROCESSING_CLASS_NAME = 'OroB2B\Bundle\OrderBundle\Entity\OrderLineItem';

    /**
     * {@inheritdoc}
     * @param OrderLineItem $lineItem
     */
    public function map($lineItem)
    {
        $taxable = (new Taxable())
            ->setIdentifier($lineItem->getId())
            ->setClassName($this->getProcessingClassName())
            ->setQuantity($lineItem->getQuantity())
            ->setDestination($this->getOrderAddress($lineItem->getOrder()))
            ->addContext(Taxable::DIGITAL_PRODUCT, $this->isDigitProduct($lineItem))
            ->addContext(Taxable::PRODUCT_TAX_CODE, $this->getProductTaxCode($lineItem));

        if ($lineItem->getPrice()) {
            $taxable->setPrice($lineItem->getPrice()->getValue());
        }

        return $taxable;
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessingClassName()
    {
        return self::PROCESSING_CLASS_NAME;
    }

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function setDoctrineHelper(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param string $productTaxCodeClass
     */
    public function setProductTaxCodeClass($productTaxCodeClass)
    {
        $this->productTaxCodeClass = $productTaxCodeClass;
    }

    /**
     * @param OrderLineItem $lineItem
     * @return bool
     */
    protected function isDigitProduct(OrderLineItem $lineItem)
    {
        $productTaxCode = $this->getProductTaxCode($lineItem);
        return $this->addressProvider->isDigitalProductTaxCode($productTaxCode);
    }

    /**
     * @param OrderLineItem $lineItem
     * @return null|ProductTaxCode
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
