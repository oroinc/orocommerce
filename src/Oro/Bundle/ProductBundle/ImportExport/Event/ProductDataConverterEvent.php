<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Event;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Triggered by import/export product data converter.
 */
class ProductDataConverterEvent extends Event
{
    const BACKEND_HEADER = 'oro_product.data_converter.backend_header';
    const CONVERT_TO_EXPORT = 'oro_product.data_converter.convert_to_export';
    const CONVERT_TO_IMPORT = 'oro_product.data_converter.convert_to_import';

    /** @var array */
    protected $data = [];

    /** @var ContextInterface */
    protected $context;

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

    /**
     * @return null|ContextInterface
     */
    public function getContext(): ?ContextInterface
    {
        return $this->context;
    }

    /**
     * @param null|ContextInterface $context
     */
    public function setContext(?ContextInterface $context): void
    {
        $this->context = $context;
    }
}
