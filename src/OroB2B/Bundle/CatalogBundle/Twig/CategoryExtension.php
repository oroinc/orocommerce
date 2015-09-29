<?php

namespace OroB2B\Bundle\CatalogBundle\Twig;

use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\CatalogBundle\JsTree\CategoryTreeHandler;

class CategoryExtension extends \Twig_Extension
{
    const NAME = 'orob2b_catalog_category_extension';

    /**
     * @var CategoryTreeHandler
     */
    protected $categoryTreeHandler;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param CategoryTreeHandler $categoryTreeHandler
     * @param TranslatorInterface $translator
     */
    public function __construct(CategoryTreeHandler $categoryTreeHandler, TranslatorInterface $translator)
    {
        $this->categoryTreeHandler = $categoryTreeHandler;
        $this->translator = $translator;
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
     * @param string|null $rootLabel
     * @return array
     */
    public function getCategoryList($rootLabel = null)
    {
        $tree = $this->categoryTreeHandler->createTree();
        if ($rootLabel && array_key_exists(0, $tree)) {
            $tree[0]['text'] = $this->translator->trans($rootLabel);
        }

        return $tree;
    }
}
