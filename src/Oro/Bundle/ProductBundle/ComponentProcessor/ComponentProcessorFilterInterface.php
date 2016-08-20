<?php

namespace Oro\Bundle\ProductBundle\ComponentProcessor;

interface ComponentProcessorFilterInterface
{
    /**
     * @param array $data
     * @param array $dataParameters
     * @return array
     */
    public function filterData(array $data, array $dataParameters);
}
