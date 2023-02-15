<?php

namespace Oro\Bundle\WebCatalogBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\JsTree\ContentNodeTreeHandler;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes a value of "tree" field for WebCatalog entity.
 */
class ComputeWebCatalogTree implements ProcessorInterface
{
    private const FIELD_NAME = 'tree';

    private ContentNodeTreeHandler $treeHandler;
    private DoctrineHelper $doctrineHelper;

    public function __construct(ContentNodeTreeHandler $treeHandler, DoctrineHelper $doctrineHelper)
    {
        $this->treeHandler = $treeHandler;
        $this->doctrineHelper = $doctrineHelper;
    }

    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();

        if ($context->isFieldRequested(self::FIELD_NAME, $data)) {
            $webCatalogId = $context->getResultFieldValue('id', $data);
            $webCatalog = $this->doctrineHelper->getEntityReference(WebCatalog::class, $webCatalogId);
            $root = $this->treeHandler->getTreeRootByWebCatalog($webCatalog);
            $data[self::FIELD_NAME] = $this->treeHandler->createTree($root);
            $context->setData($data);
        }
    }
}
