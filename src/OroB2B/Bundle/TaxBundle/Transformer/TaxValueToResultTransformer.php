<?php

namespace OroB2B\Bundle\TaxBundle\Transformer;

use OroB2B\Bundle\TaxBundle\Entity\TaxApply;
use OroB2B\Bundle\TaxBundle\Entity\TaxValue;
use OroB2B\Bundle\TaxBundle\Manager\TaxValueManager;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Model\TaxResultElement;

class TaxValueToResultTransformer implements TaxTransformerInterface
{
    /** @var TaxValueManager */
    protected $taxValueManager;

    /** @var string */
    protected $taxClass;

    /**
     * @param TaxValueManager $taxValueManager
     * @param string $taxClass
     */
    public function __construct(TaxValueManager $taxValueManager, $taxClass)
    {
        $this->taxValueManager = $taxValueManager;
        $this->taxClass = $taxClass;
    }

    /** {@inheritdoc} */
    public function transform(TaxValue $taxValue)
    {
        $result = $taxValue->getResult();

        $taxResultElements = [];
        foreach ($taxValue->getAppliedTaxes() as $taxApply) {
            $taxResultElements[] = TaxResultElement::create(
                $taxApply->getTax()->getId(),
                $taxApply->getRate(),
                $taxApply->getTaxableAmount(),
                $taxApply->getTaxAmount()
            );
        }

        $result->offsetSet(Result::TAXES, $taxResultElements);

        return $result;
    }

    /** {@inheritdoc} */
    public function reverseTransform(Result $result, Taxable $taxable)
    {
        $taxValue = $this->taxValueManager->getTaxValue($taxable->getClassName(), $taxable->getIdentifier());

        $taxValue->getAppliedTaxes()->clear();

        foreach ($result->getTaxes() as $taxResultElement) {
            $taxApply = new TaxApply();

            $taxId = $taxResultElement->offsetGet(TaxResultElement::TAX);
            $taxApply->setTax($this->taxValueManager->getTaxReference($this->taxClass, $taxId));
            $taxApply->setRate($taxResultElement->offsetGet(TaxResultElement::RATE));
            $taxApply->setTaxableAmount($taxResultElement->offsetGet(TaxResultElement::TAXABLE_AMOUNT));
            $taxApply->setTaxAmount($taxResultElement->offsetGet(TaxResultElement::TAX_AMOUNT));
            $taxApply->setTaxValue($taxValue);

            $taxValue->addAppliedTax($taxApply);
        }

        $result->offsetUnset(Result::TAXES);
        $taxValue->setResult($result);

        return $taxValue;
    }
}
