<?php

namespace OroB2B\Bundle\ProductBundle\ComponentProcessor;

interface ComponentProcessorFilterInterface
{
    /**
     * @param array $data
     * @param array $dataParameters
     * @return array
     */
    public function filterData(array $data, array $dataParameters);
}
