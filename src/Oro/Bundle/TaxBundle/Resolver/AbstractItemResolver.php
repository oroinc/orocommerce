<?php

namespace Oro\Bundle\TaxBundle\Resolver;

use Oro\Bundle\TaxBundle\Matcher\MatcherInterface;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Model\TaxCode;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;
use Oro\Bundle\TaxBundle\Model\TaxCodes;

/**
 * Provide basic methods for resolvers.
 */
abstract class AbstractItemResolver implements ResolverInterface
{
    public function __construct(
        protected UnitResolver $unitResolver,
        protected RowTotalResolver $rowTotalResolver,
        protected MatcherInterface $matcher
    ) {
    }

    protected function calculateUnitPriceAndRowTotal(Taxable $taxable): void
    {
        $result = $taxable->getResult();
        $taxRules = $this->matcher->match($taxable->getTaxationAddress(), $this->getTaxCodes($taxable));

        $this->unitResolver->resolveUnitPrice($result, $taxRules, $taxable->getPrice());
        $this->rowTotalResolver->resolveRowTotal($result, $taxRules, $taxable->getPrice(), $taxable->getQuantity());

        $result->lockResult();
    }

    protected function getTaxCodes(Taxable $taxable): TaxCodes
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
