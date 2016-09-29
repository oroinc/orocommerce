<?php

namespace Oro\Bundle\InfinitePayBundle\Service\InfinitePay;

interface ResponseBodyInterface
{
    /**
     * @return GenericResponseInterface
     */
    public function getResponse();
}
