<?php

namespace Oro\Bundle\TaxBundle\Resolver;

use Oro\Bundle\TaxBundle\Matcher\MatcherInterface;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Model\TaxCode;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;
use Oro\Bundle\TaxBundle\Model\TaxCodes;

abstract class AbstractItemResolver implements ResolverInterface
{
    /**
     * @var UnitResolver
     */
    protected $unitResolver;

    /**
     * @var RowTotalResolver
     */
    protected $rowTotalResolver;

    /**
     * @var MatcherInterface
     */
    protected $matcher;

    public function __construct(
        UnitResolver $unitResolver,
        RowTotalResolver $rowTotalResolver,
        MatcherInterface $matcher
    ) {
        $this->unitResolver = $unitResolver;
        $this->rowTotalResolver = $rowTotalResolver;
        $this->matcher = $matcher;
    }

    /**
     * @param Taxable $taxable
     * @return TaxCodes
     */
    protected function getTaxCodes(Taxable $taxable)
    {
        $taxCodes = [];

        $productContextCode = $taxable->getContextValue(Taxable::PRODUCT_TAX_CODE);
        if (null !== $productContextCode) {
            $taxCodes[] = TaxCode::create($productContextCode, TaxCodeInterface::TYPE_PRODUCT);
        }

        $customerContextCode = $taxable->getContextValue(Taxable::ACCOUNT_TAX_CODE);
        if (null !== $customerContextCode) {
            $taxCodes[] = TaxCode::create($customerContextCode, TaxCodeInterface::TYPE_ACCOUNT);
        }

        return TaxCodes::create($taxCodes);
    }
}
