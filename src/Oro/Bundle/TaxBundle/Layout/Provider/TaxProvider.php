<?php

namespace Oro\Bundle\TaxBundle\Layout\Provider;

use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;

/**
 * Layout data provider for tax information in the storefront.
 *
 * This provider makes tax calculation results available to storefront layouts and templates.
 * It retrieves tax information for orders using the enabled tax provider and exposes it through the layout system,
 * allowing tax details to be displayed on checkout pages, order summaries, and other customer-facing interfaces.
 */
class TaxProvider
{
    /** @var TaxProviderRegistry */
    private $taxProviderRegistry;

    public function __construct(TaxProviderRegistry $taxProviderRegistry)
    {
        $this->taxProviderRegistry = $taxProviderRegistry;
    }

    /**
     * @param $order
     *
     * @return Result
     */
    public function getTax($order)
    {
        $provider = $this->taxProviderRegistry->getEnabledProvider();

        return $provider->loadTax($order);
    }
}
