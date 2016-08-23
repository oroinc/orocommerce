<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Event;

use Symfony\Component\EventDispatcher\Event;

class ProductDataConverterEvent extends Event
{
    const BACKEND_HEADER = 'orob2b_product.data_converter.backend_header';
    const CONVERT_TO_EXPORT = 'orob2b_product.data_converter.convert_to_export';
    const CONVERT_TO_IMPORT = 'orob2b_product.data_converter.convert_to_import';

    /** @var array */
    protected $data = [];

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }
}
