<?php

namespace OroB2B\Bundle\CatalogBundle\Twig;

use OroB2B\Bundle\CatalogBundle\JsTree\CategoryTreeHandler;

class CategoryExtension extends \Twig_Extension
{
    const NAME = 'orob2b_catalog_category_extension';

    /**
     * @var CategoryTreeHandler
     */
    protected $categoryTreeHandler;

    /**
     * @param CategoryTreeHandler $categoryTreeHandler
     */
    public function __construct(CategoryTreeHandler $categoryTreeHandler)
    {
        $this->categoryTreeHandler = $categoryTreeHandler;
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
            new \Twig_SimpleFunction('orob2b_category_list', [$this, 'getCategoryList']),
        ];
    }

    /**
     * @return array
     */
    public function getCategoryList()
    {
        return $this->categoryTreeHandler->createTree();
    }
}
