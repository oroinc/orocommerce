<?php

namespace Oro\Bundle\ProductBundle\ComponentProcessor;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface ComponentProcessorInterface
{
    /**
     * @param array $data
     * @param Request $request
     * @return Response|null
     */
    public function process(array $data, Request $request);

    /**
     * @return string
     */
    public function getName();

    /**
     * @return bool
     */
    public function isValidationRequired();

    /**
     * @return boolean
     */
    public function isAllowed();
}
