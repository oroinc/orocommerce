<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Form\Type\EntityChangesetType;
use Oro\Bundle\ProductBundle\Form\DataTransformer\CollectionSortOrderTransformer;
use Oro\Bundle\ValidationBundle\Validator\Constraints\Decimal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

/**
 * Form type for editing product sort order in WebCatalog ProductCollection ContentVariant edition
 */
class CollectionSortOrderGridType extends AbstractType
{
    const NAME = 'oro_collection_sort_order_grid';

    public function __construct(
        protected FormFactoryInterface $formFactory,
        protected DoctrineHelper $doctrineHelper
    ) {
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
        return EntityChangesetType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addViewTransformer(
            new CollectionSortOrderTransformer($this->doctrineHelper, $options['segment']),
            true
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('segment', null);
        $resolver->setDefault('class', 'Oro\Bundle\ProductBundle\Entity\CollectionSortOrder');
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['sortOrderConstraints'] = $this->getSortOrderConstraints();
    }

    /**
     * @return array
     */
    protected function getSortOrderConstraints(): array
    {
        // build fake field to get correct definitions of JS constraints
        $view = $this->formFactory->create(
            NumberType::class,
            null,
            [
                'constraints' => [
                    new Decimal(),
                    new Range(['min' => 0])
                ]
            ]
        )->createView();

        return json_decode($view->vars['attr']['data-validation'], true);
    }
}
