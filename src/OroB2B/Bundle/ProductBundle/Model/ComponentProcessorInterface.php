<?php

namespace OroB2B\Bundle\ProductBundle\Model;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface ComponentProcessorInterface
{
    const ENTITY_DATA_KEY = 'entity_data';
    const ENTITY_ITEMS_DATA_KEY = 'entity_items_data';

    const PRODUCT_SKU_FIELD_NAME = 'productSku';
    const PRODUCT_QUANTITY_FIELD_NAME = 'productQuantity';

    const TRANSFORM = 'transform';

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
