<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatter;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductUnitSelectType extends AbstractProductAwareType
{
    const NAME = 'oro_product_unit_select';

    /**
     * @var string
     */
    private $entityClass;

    /**
     * @var UnitLabelFormatter
     */
    private $productUnitFormatter;

    /**
     * @param UnitLabelFormatter $productUnitLabelFormatter
     */
    public function __construct(UnitLabelFormatter $productUnitLabelFormatter)
    {
        $this->productUnitFormatter = $productUnitLabelFormatter;
    }

    /**
     * @param string $entityClass
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(
            [
                'class' => $this->entityClass,
                'choice_label' => 'code',
                'compact' => false,
                'choices_updated' => false,
                'required' => true,
                'empty_label' => 'oro.product.productunit.removed',
                'sell' => null,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        /** @var ChoiceView $choiceView */
        foreach ($view->vars['choices'] as $choiceView) {
            $choiceView->label = $this->productUnitFormatter->format($choiceView->value, $options['compact']);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return EntityType::class;
    }

    /**
     * {@inheritDoc}
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
        return static::NAME;
    }
}
