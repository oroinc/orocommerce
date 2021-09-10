<?php

namespace Oro\Bundle\TaxBundle\Transformer;

use Oro\Bundle\TaxBundle\Entity\TaxValue;
use Oro\Bundle\TaxBundle\Manager\TaxValueManager;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\Taxable;

class TaxValueToResultTransformer implements TaxTransformerInterface
{
    /** @var TaxValueManager */
    protected $taxValueManager;

    public function __construct(TaxValueManager $taxValueManager)
    {
        $this->taxValueManager = $taxValueManager;
    }

    /** {@inheritdoc} */
    public function transform(TaxValue $taxValue)
    {
        return new Result($taxValue->getResult()->getArrayCopy());
    }

    /** {@inheritdoc} */
    public function reverseTransform(Result $result, Taxable $taxable)
    {
        $taxValue = $this->taxValueManager->getTaxValue($taxable->getClassName(), $taxable->getIdentifier());
        $taxValue->setAddress((string)$taxable->getTaxationAddress());

        // We have to create new instance of Result because original TaxValue::Result
        // must not be changed from outside
        $taxValue->setResult(new Result($result->getArrayCopy()));

        return $taxValue;
    }
}
