<?php

namespace Oro\Bundle\ProductBundle\ComponentProcessor;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Represents a service to handle logic related to quick order process.
 */
interface ComponentProcessorInterface
{
    public function process(array $data, Request $request): ?Response;

    public function isAllowed(): bool;
}
