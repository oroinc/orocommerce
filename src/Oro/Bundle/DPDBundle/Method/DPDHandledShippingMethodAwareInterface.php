<?php

namespace Oro\Bundle\DPDBundle\Method;

interface DPDHandledShippingMethodAwareInterface
{
    /**
     * @return DPDHandler[]
     */
    public function getDPDHandlers();

    /**
     * @param string $identifier
     *
     * @return DPDHandler|null
     */
    public function getDPDHandler($identifier);
}
