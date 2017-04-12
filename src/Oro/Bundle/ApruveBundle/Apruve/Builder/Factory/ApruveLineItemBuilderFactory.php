<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Builder\Factory;

use Oro\Bundle\ApruveBundle\Apruve\Builder\ApruveLineItemBuilder;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItemInterface;
use Symfony\Component\Routing\RouterInterface;

class ApruveLineItemBuilderFactory implements ApruveLineItemBuilderFactoryInterface
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * {@inheritDoc}
     */
    public function create(PaymentLineItemInterface $lineItem)
    {
        return new ApruveLineItemBuilder($lineItem, $this->router);
    }
}
