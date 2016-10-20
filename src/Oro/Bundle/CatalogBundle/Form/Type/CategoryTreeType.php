<?php

namespace Oro\Bundle\CatalogBundle\Form\Type;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\FormBundle\Form\Type\EntityTreeSelectType;
use Oro\Component\Tree\Handler\AbstractTreeHandler;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryTreeType extends AbstractType
{
    const NAME = 'oro_catalog_category_tree';

    /**
     * @var AbstractTreeHandler
     */
    private $treeHandler;

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
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'class' => Category::class,
            'tree_key' => 'commerce-category',
            'tree_data' => [$this->treeHandler, 'createTree']
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return EntityTreeSelectType::class;
    }
}
