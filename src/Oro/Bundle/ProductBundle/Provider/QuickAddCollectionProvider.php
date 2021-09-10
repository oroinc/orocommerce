<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\ProductBundle\Form\Handler\QuickAddHandler;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddCopyPasteType;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddImportFromFileType;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Symfony\Component\HttpFoundation\RequestStack;

class QuickAddCollectionProvider
{
    /**
     * @var QuickAddHandler
     */
    protected $quickAddHandler;

    /**
     * @var array
     */
    protected $copyPasteCollection;

    public function __construct(QuickAddHandler $quickAddHandler, RequestStack $requestStack)
    {
        $this->quickAddHandler = $quickAddHandler;
        $this->request = $requestStack->getCurrentRequest();
        $this->copyPasteCollection = [];
    }

    /**
     * @return QuickAddRowCollection|null
     */
    public function processCopyPaste()
    {
        $cacheKey = $this->getCacheKey('quick_add.copy_paste', $this->request->get(QuickAddCopyPasteType::NAME));
        if (!array_key_exists($cacheKey, $this->copyPasteCollection)) {
            $this->copyPasteCollection[$cacheKey] = $this->quickAddHandler->processCopyPaste($this->request);
        }

        return $this->copyPasteCollection[$cacheKey];
    }

    /**
     * @return mixed
     */
    public function processImport()
    {
        $cacheKey = $this->getCacheKey('quick_add.import', $this->request->get(QuickAddImportFromFileType::NAME));
        if (!array_key_exists($cacheKey, $this->copyPasteCollection)) {
            $this->copyPasteCollection[$cacheKey] = $this->quickAddHandler->processImport($this->request);
        }

        return $this->copyPasteCollection[$cacheKey];
    }

    /**
     * @param $prefix
     * @param $options
     * @return string
     */
    protected function getCacheKey($prefix, $options)
    {
        return sprintf('%s:%s', $prefix, md5(serialize($options)));
    }
}
