<?php

namespace Oro\Bundle\WebCatalogBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\JsTree\ContentNodeTreeHandler;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Updates a Context with information about WebCatalog tree
 */
class WebCatalogTree implements ProcessorInterface
{
    /** @var ContentNodeTreeHandler  */
    private $treeHandler;

    /** @var DoctrineHelper  */
    private $doctrineHelper;

    /**
     * @param ContentNodeTreeHandler $treeHandler
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(ContentNodeTreeHandler $treeHandler, DoctrineHelper $doctrineHelper)
    {
        $this->treeHandler = $treeHandler;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param ContextInterface $context
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeLoadedDataContext $context */
        $data = $context->getResult();
        $config = $context->getConfig();

        $treeFieldName = $config->findFieldNameByPropertyPath('tree');
        if (!$treeFieldName
            || $config->getField($treeFieldName)->isExcluded()
            || array_key_exists($treeFieldName, $data)
        ) {
            // the tree field is undefined, excluded or already added
            return;
        }

        $webCatalogIdFieldName = $config->findFieldNameByPropertyPath('id');
        if (!$webCatalogIdFieldName || empty($data[$webCatalogIdFieldName])) {
            // the web catalog id field is undefined or its value is unknown
            return;
        }

        $webCatalogId = $data[$webCatalogIdFieldName];
        $webCatalog = $this->doctrineHelper->getEntityRepository(WebCatalog::class)->find(['id' => $webCatalogId]);
        $root = $this->treeHandler->getTreeRootByWebCatalog($webCatalog);
        $data[$treeFieldName] = $this->treeHandler->createTree($root, true);

        $context->setResult($data);
    }
}
