<?php

namespace OroB2B\Bundle\TaxBundle\Transformer;

use OroB2B\Bundle\TaxBundle\Entity\TaxApply;
use OroB2B\Bundle\TaxBundle\Entity\TaxValue;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\TaxResultElement;

class TaxValueToResultTransformer implements TaxTransformerInterface
{
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
    public function reverseTransform(Result $result)
    {
        $taxValue = new TaxValue();

        foreach ($result->getTaxes() as $taxResultElement) {
            $taxApply = new TaxApply();

            /** @todo: reference */
            $taxApply->setTax($taxResultElement->offsetGet(TaxResultElement::TAX));
            $taxApply->setRate($taxResultElement->offsetGet(TaxResultElement::RATE));
            $taxApply->setTaxableAmount($taxResultElement->offsetGet(TaxResultElement::TAXABLE_AMOUNT));
            $taxApply->setTaxAmount($taxResultElement->offsetGet(TaxResultElement::TAX_AMOUNT));

            $taxValue->addAppliedTax($taxApply);
        }

        $result->offsetUnset(Result::TAXES);
        $taxValue->setResult($result);

        return $taxValue;
    }
}
