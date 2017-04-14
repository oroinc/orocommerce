<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Generator;

use Oro\Bundle\ApruveBundle\Apruve\Model\Order\ApruveOrderInterface;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;

interface OrderSecureHashGeneratorInterface
{
    /**
     * @param ApruveOrderInterface $apruveOrder
     * @param ApruveConfigInterface $config
     *
     * @return string
     */
    public function generate(ApruveOrderInterface $apruveOrder, ApruveConfigInterface $config);
}
