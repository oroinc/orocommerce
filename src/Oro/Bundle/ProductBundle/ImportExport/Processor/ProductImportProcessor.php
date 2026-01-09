<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Processor;

use Oro\Bundle\BatchBundle\Item\Support\ClosableInterface;
use Oro\Bundle\ImportExportBundle\Processor\ImportProcessor;

/**
 * Processes product import operations with support for resource cleanup.
 *
 * This processor extends the standard import processor to implement the closable interface, ensuring that
 * any resources held by the import strategy are properly released after the import batch is complete.
 */
class ProductImportProcessor extends ImportProcessor implements ClosableInterface
{
    #[\Override]
    public function close()
    {
        if ($this->strategy instanceof ClosableInterface) {
            $this->strategy->close();
        }
    }
}
