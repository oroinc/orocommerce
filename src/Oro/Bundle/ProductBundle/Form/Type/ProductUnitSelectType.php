<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Extends AbstractProductAwareType behavior by formatting
 * view choices values using UnitLabelFormatterInterface
 */
class ProductUnitSelectType extends AbstractProductAwareType
{
    const NAME = 'oro_product_unit_select';

    /**
     * @var string
     */
    private $entityClass;

    /**
     * @var UnitLabelFormatterInterface
     */
    private $productUnitFormatter;

    /**
     * @param UnitLabelFormatterInterface $productUnitLabelFormatter
     */
    public function __construct(UnitLabelFormatterInterface $productUnitLabelFormatter)
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
