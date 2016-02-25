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
        return $taxValue->getResult();
    }

    /** {@inheritdoc} */
    public function reverseTransform(Result $result, Taxable $taxable)
    {
        $taxValue = $this->taxValueManager->getTaxValue($taxable->getClassName(), $taxable->getIdentifier());
        $taxValue->setAddress((string)$taxable->getDestination());

        $taxValueResult = new Result($result->getArrayCopy());
        $taxValue->setResult($taxValueResult);

        return $taxValue;
    }
}
