<?php

namespace OroB2B\Component\Checkout\DataProvider;

use OroB2B\Component\Checkout\Model\DTO\EntitySummaryDTO;

interface CheckoutDataProviderInterface
{
    /**
     * @param object $entity
     * @return EntitySummaryDTO
     */
    public function getData($entity);

    /**
     * @param object $entity
     * @return boolean
     */
    public function isEntitySupported($entity);
}
