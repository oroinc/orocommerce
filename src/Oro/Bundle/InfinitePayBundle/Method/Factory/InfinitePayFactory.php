<?php

namespace Oro\Bundle\InfinitePayBundle\Method\Factory;

use Oro\Bundle\InfinitePayBundle\Action\Registry\ActionRegistryInterface;
use Oro\Bundle\InfinitePayBundle\Method\Config\InfinitePayConfigInterface;
use Oro\Bundle\InfinitePayBundle\Method\InfinitePay;
use Oro\Bundle\InfinitePayBundle\Method\Provider\OrderProviderInterface;

class InfinitePayFactory implements InfinitePayFactoryInterface
{
    /**
     * @var ActionRegistryInterface
     */
    protected $actionRegistry;

    /**
     * @var OrderProviderInterface
     */
    protected $orderProvider;

    public function __construct(
        ActionRegistryInterface $actionRegistry,
        OrderProviderInterface $orderProvider
    ) {
        $this->actionRegistry = $actionRegistry;
        $this->orderProvider = $orderProvider;
    }

    /**
     * @inheritDoc
     */
    public function create(InfinitePayConfigInterface $config)
    {
        return new InfinitePay(
            $config,
            $this->actionRegistry,
            $this->orderProvider
        );
    }
}
