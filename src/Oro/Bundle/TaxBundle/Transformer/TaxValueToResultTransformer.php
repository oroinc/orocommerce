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
