<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Form\Type\DataChangesetType;
use Oro\Bundle\ValidationBundle\Validator\Constraints\Decimal;
use Oro\Bundle\ValidationBundle\Validator\Constraints\GreaterThanZero;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Form type for editing product sort order in :
 * * MasterCatalog Category edition
 * * WebCatalog ProductCollection ContentVariant edition
 */
class SortOrderGridType extends AbstractType
{
    const NAME = 'oro_sort_order_grid';

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
        return DataChangesetType::class;
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
                    new GreaterThanZero()
                ]
            ]
        )->createView();

        return json_decode($view->vars['attr']['data-validation'], true);
    }
}
