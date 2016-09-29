<?php

namespace Oro\Bundle\InfinitePayBundle\Service\InfinitePay\Logger;

interface InfinitePayAPILoggerInterface
{
    /**
     * @param $request
     * @param $response
     */
    public function logApiError($request, $response);
}
