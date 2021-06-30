<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Frontend\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Fires after frontend product export header collecting. Stores data of processed headers and header rules. New headers
 * and rules could be mixed during this event.
 */
class ProductExportDataConverterEvent extends Event
{
    public const FRONTEND_PRODUCT_CONVERT_TO_EXPORT_DATA = 'oro_product.frontend_product_export.convert_to_export';

    private array $headerRules;
    private array $backendHeaders;

    public function __construct(array $headerRules, array $backendHeaders)
    {
        $this->headerRules = $headerRules;
        $this->backendHeaders = $backendHeaders;
    }

    /**
     * @return array
     * [
     *     'name' => 'name',
     *     'inventory_status.id' => 'inventory_status:id',
     *     //...
     * ]
     */
    public function getHeaderRules(): array
    {
        return $this->headerRules;
    }

    /**
     * @param array $headerRules
     * [
     *     'name' => 'name',
     *     'inventory_status.id' => 'inventory_status:id',
     *     //...
     * ]
     */
    public function setHeaderRules(array $headerRules): void
    {
        $this->headerRules = $headerRules;
    }

    /**
     * @return string[]
     * [
     *     'sku',
     *     'inventory_status:id',
     *     // ...
     * ]
     */
    public function getBackendHeaders(): array
    {
        return $this->backendHeaders;
    }

    /**
     * @param array $backendHeaders
     * [
     *     'sku',
     *     'inventory_status:id',
     *     // ...
     * ]
     */
    public function setBackendHeaders(array $backendHeaders): void
    {
        $this->backendHeaders = $backendHeaders;
    }
}
