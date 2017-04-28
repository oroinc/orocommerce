<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Generator;

use Oro\Bundle\ApruveBundle\Apruve\Model\ApruveOrder;

interface OrderSecureHashGeneratorInterface
{
    /**
     * @param ApruveOrder $apruveOrder
     * @param string      $apiKey Merchant API key.
     *
     * @return string
     */
    public function generate(ApruveOrder $apruveOrder, $apiKey);
}
