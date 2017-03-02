<?php

namespace Oro\Bundle\DPDBundle\Method;

interface DPDHandledShippingMethodAwareInterface
{
    /**
     * @return DPDHandlerInterface[]
     */
    public function getDPDHandlers();

    /**
     * @param string $identifier
     *
     * @return DPDHandlerInterface|null
     */
    public function getDPDHandler($identifier);
}
