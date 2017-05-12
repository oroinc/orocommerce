<?php

namespace Oro\Bundle\ApruveBundle\Services;

use Symfony\Component\HttpFoundation\Request;

interface SignatureVerifyingServiceInterface
{
    /**
     * @param Request $request
     *
     * @return bool
     */
    public function verifyRequestSignature(Request $request);
}
