<?php

namespace OroB2B\Bundle\CMSBundle\Twig;

use OroB2B\Bundle\CMSBundle\JsTree\PageTreeHandler;

class PageExtension extends \Twig_Extension
{
    const NAME = 'orob2b_cms_page_extension';

    /**
     * @var PageTreeHandler
     */
    protected $pageTreeHandler;

    /**
     * @param PageTreeHandler $pageTreeHandler
     */
    public function __construct(PageTreeHandler $pageTreeHandler)
    {
        $this->pageTreeHandler = $pageTreeHandler;
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
            new \Twig_SimpleFunction('orob2b_page_list', [$this, 'getPageList']),
        ];
    }

    /**
     * @return array
     */
    public function getPageList()
    {
        return $this->pageTreeHandler->createTree();
    }
}
