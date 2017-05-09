<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Client\RequestConfigurator;

class RequestConfiguratorRegistry
{
    /** @var RequestConfiguratorInterface[][] */
    protected $requestConfigurators = [];

    /** @var RequestConfiguratorInterface[] */
    protected $sortedRequestConfigurators = [];

    /**
     * @param RequestConfiguratorInterface $requestConfigurator
     */
    public function addRequestConfigurator(RequestConfiguratorInterface $requestConfigurator)
    {
        $this->requestConfigurators[$requestConfigurator->getPriority()][] = $requestConfigurator;

        // sort by priority and save it to separated sorted array
        krsort($this->requestConfigurators);
        $this->sortedRequestConfigurators = call_user_func_array('array_merge', $this->requestConfigurators);
    }

    /**
     * @return RequestConfiguratorInterface[]
     */
    public function getRequestConfigurators()
    {
        return $this->sortedRequestConfigurators;
    }
}
