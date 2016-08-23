<?php

namespace Oro\Bundle\TaxBundle\Resolver;

use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\Taxable;

class CustomerAddressResolver implements ResolverInterface
{
    /** @var CustomerAddressItemResolver */
    protected $itemResolver;

    /**
     * @param CustomerAddressItemResolver $itemResolver
     */
    public function __construct(CustomerAddressItemResolver $itemResolver)
    {
        $this->itemResolver = $itemResolver;
    }

    /** {@inheritdoc} */
    public function resolve(Taxable $taxable)
    {
        if (!$taxable->getItems()->count()) {
            return;
        }

        if ($taxable->getResult()->isResultLocked()) {
            return;
        }

        $itemsResult = [];
        foreach ($taxable->getItems() as $taxableItem) {
            $this->itemResolver->resolve($taxableItem);
            $itemsResult[] = $taxableItem->getResult();
        }

        $taxable->getResult()->offsetSet(Result::ITEMS, $itemsResult);
    }
}
