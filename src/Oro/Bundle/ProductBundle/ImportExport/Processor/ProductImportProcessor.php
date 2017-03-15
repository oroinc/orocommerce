<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Processor;

use Oro\Bundle\BatchBundle\Item\Support\ClosableInterface;
use Oro\Bundle\ImportExportBundle\Processor\ImportProcessor;

class ProductImportProcessor extends ImportProcessor implements ClosableInterface
{
    /**
     * {@inheritdoc}
     */
    public function close()
    {
        if ($this->strategy instanceof ClosableInterface) {
            $this->strategy->close();
        }
    }
}
