<?php

namespace OroB2B\Bundle\TaxBundle\Transformer;

use OroB2B\Bundle\TaxBundle\Entity\TaxValue;
use OroB2B\Bundle\TaxBundle\Manager\TaxValueManager;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\Taxable;

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
        return $taxValue->getResult();
    }

    /** {@inheritdoc} */
    public function reverseTransform(Result $result, Taxable $taxable)
    {
        $taxValue = $this->taxValueManager->getTaxValue($taxable->getClassName(), $taxable->getIdentifier());
        $taxValue->setAddress((string)$taxable->getTaxationAddress());

        $taxValue->setResult($result);

        return $taxValue;
    }
}
