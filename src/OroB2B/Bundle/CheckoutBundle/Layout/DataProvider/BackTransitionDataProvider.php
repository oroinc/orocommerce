<?php

namespace OroB2B\Bundle\CheckoutBundle\Layout\DataProvider;

use Oro\Component\Layout\AbstractServerRenderDataProvider;
use Oro\Component\Layout\ContextInterface;

class BackTransitionDataProvider
{
    /**
     * @var object
     */
    protected $transitionsDataProvider;

    /**
     * @param object $transitionsDataProvider
     */
    public function setBackTransitionsDataProvider($transitionsDataProvider)
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
