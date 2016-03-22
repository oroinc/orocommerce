<?php

namespace OroB2B\Bundle\CheckoutBundle\Layout\DataProvider;

use Oro\Component\Layout\AbstractServerRenderDataProvider;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataProviderInterface;

class BackTransitionDataProvider extends AbstractServerRenderDataProvider
{
    /**
     * @var DataProviderInterface
     */
    protected $transitionsDataProvider;

    /**
     * @param DataProviderInterface $transitionsDataProvider
     */
    public function setBackTransitionsDataProvider(DataProviderInterface $transitionsDataProvider)
    {
        $this->transitionsDataProvider = $transitionsDataProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        $transitions = $this->transitionsDataProvider->getData($context);

        if ($transitions) {
            return end($transitions);
        }

        return null;
    }
}
