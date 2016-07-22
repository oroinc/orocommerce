<?php

namespace OroB2B\Bundle\TaxBundle\Layout\Provider;

use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\TaxBundle\Manager\TaxManager;

class TaxDataProvider
{
    /** @var TaxManager */
    protected $taxManager;

    /**
     * @param TaxManager $taxManager
     */
    public function __construct(TaxManager $taxManager)
    {
        $this->taxManager = $taxManager;
    }

    /** {@inheritDoc} */
    public function getData(ContextInterface $context)
    {
        $taxable = $context->data()->get('order');

        return $this->taxManager->loadTax($taxable);
    }
}
