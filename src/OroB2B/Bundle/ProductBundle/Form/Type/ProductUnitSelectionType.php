<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ProductUnitSelectionType extends AbstractType
{
    const NAME = 'orob2b_product_unit_selection';

    /**
     * @var string
     */
    protected $entityClass;

    /**
     * @param string $entityClass
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'class' => $this->entityClass,
            'property' => 'code',
            'compact' => false
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $labelSuffix = $options['compact'] ? '.short' : '.full';

        foreach ($view->vars['choices'] as &$choice) {
            $choice->label = 'orob2b.product_unit.'. $choice->label .'.label'. $labelSuffix;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return 'entity';
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
