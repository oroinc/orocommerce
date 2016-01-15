<?php

namespace OroB2B\Bundle\TaxBundle\Transformer;

use OroB2B\Bundle\TaxBundle\Entity\TaxValue;
use OroB2B\Bundle\TaxBundle\Model\Result;

class TaxValueToResultTransformer implements TaxTransformerInterface
{
    /** {@inheritdoc} */
    public function transform(TaxValue $taxValue)
    {
        $result = $taxValue->getResult();

        $result->offsetSet(Result::TAXES, $taxValue->getAppliedTaxes());

        return $result;
    }

    /** {@inheritdoc} */
    public function reverseTransform(TaxValue $taxValue, Result $result)
    {
        foreach ($result->getTaxes() as $applyTax) {
            $taxValue->addAppliedTax($applyTax);
        }

        $result->offsetUnset(Result::TAXES);
        $taxValue->setResult($result);

        return $taxValue;
    }
}
