<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Client;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option\OptionInterface;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Response\ResponseInterface;

interface ClientInterface
{
    /**
     * @param string $hostAddress
     * @param array $options
     * @return ResponseInterface
     */
    public function send($hostAddress, array $options = []);
}
