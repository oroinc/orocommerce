<?php

namespace Oro\Bundle\WebCatalogBundle\Twig;

use Oro\Component\Tree\Handler\AbstractTreeHandler;

class WebCatalogExtension extends \Twig_Extension
{
    const NAME = 'oro_web_catalog_extension';

    /**
     * @var AbstractTreeHandler
     */
    protected $treeHandler;

    /**
     * @param AbstractTreeHandler $treeHandler
     */
    public function __construct(AbstractTreeHandler $treeHandler)
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
            new \Twig_SimpleFunction('oro_web_catalog_nodes_tree', [$this, 'getNodesTree']),
        ];
    }

    /**
     * @return array
     */
    public function getNodesTree()
    {
        // TODO: allow to get empty tree (several roots?)
        return $this->treeHandler->createTree();
    }
}
