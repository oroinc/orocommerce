<?php

namespace Oro\Bundle\OrderBundle\Entity;

/**
 * Provide the possibility to get order from holder entities.
 */
interface OrderHolderInterface
{
    public function getOrder(): ?Order;
}
