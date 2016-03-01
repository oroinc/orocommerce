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

    /**
     * @param TaxValueManager $taxValueManager
     */
    public function __construct(TaxValueManager $taxValueManager)
    {
        $this->taxValueManager = $taxValueManager;
    }

    /** {@inheritdoc} */
    public function transform(TaxValue $taxValue)
    {
        $result = $taxValue->getResult();

        $taxResultElements = [];
        foreach ($taxValue->getAppliedTaxes() as $taxApply) {
            $taxResultElement = TaxResultElement::create(
                (string)$taxApply->getTax(),
                $taxApply->getRate(),
                $taxApply->getTaxableAmount(),
                $taxApply->getTaxAmount()
            );

            $taxResultElements[] = $taxResultElement;
        }

        if ($taxResultElements) {
            $result->offsetSet(Result::TAXES, $taxResultElements);
        }

        return $result;
    }

    /** {@inheritdoc} */
    public function reverseTransform(Result $result, Taxable $taxable)
    {
        $taxValue = $this->taxValueManager->getTaxValue($taxable->getClassName(), $taxable->getIdentifier());
        $taxValue->setAddress((string)$taxable->getDestination());

        $taxValue->getAppliedTaxes()->clear();

        foreach ($result->getTaxes() as $taxResultElement) {
            $taxApply = new TaxApply();

            $taxCode = $taxResultElement->offsetGet(TaxResultElement::TAX);
            $taxApply->setTax($this->taxValueManager->getTax($taxCode));
            $taxApply->setRate($taxResultElement->offsetGet(TaxResultElement::RATE));
            $taxApply->setTaxableAmount($taxResultElement->offsetGet(TaxResultElement::TAXABLE_AMOUNT));
            $taxApply->setTaxAmount($taxResultElement->offsetGet(TaxResultElement::TAX_AMOUNT));
            $taxApply->setTaxValue($taxValue);

            $taxValue->addAppliedTax($taxApply);
        }

        $taxValueResult = new Result($result->getArrayCopy());
        $taxValueResult->unsetOffset(Result::TAXES);
        $taxValue->setResult($taxValueResult);

        return $taxValue;
    }
}
