<?php

namespace Oro\Bundle\WebCatalogBundle\Twig;

use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\JsTree\ContentNodeTreeHandler;

class WebCatalogExtension extends \Twig_Extension
{
    const NAME = 'oro_web_catalog_extension';

    /**
     * @var ContentNodeTreeHandler
     */
    protected $treeHandler;

    /**
     * @param ContentNodeTreeHandler $treeHandler
     */
    public function __construct(ContentNodeTreeHandler $treeHandler)
    {
        $this->treeHandler = $treeHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('oro_web_catalog_tree', [$this, 'getNodesTree']),
        ];
    }

    /**
     * @param WebCatalog $webCatalog
     * @return array
     */
    public function getNodesTree(WebCatalog $webCatalog)
    {
        $root = $this->treeHandler->getTreeRootByWebCatalog($webCatalog);

        return $this->treeHandler->createTree($root, true);
    }
}
