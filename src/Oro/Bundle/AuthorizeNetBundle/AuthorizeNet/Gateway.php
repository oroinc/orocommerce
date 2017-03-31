<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Client\ClientInterface;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option\OptionInterface;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Response\ResponseInterface;

class Gateway
{
    /**
     * @var ClientInterface
     */
    protected $apiClient;


    /**
     * @param OptionInterface $option
     * @return ResponseInterface
     */
    public function request(OptionInterface $option)
    {
        return $this->apiClient->send($option);
    }
}
