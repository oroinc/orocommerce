<?php

namespace Oro\Bundle\InventoryBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

use Oro\Bundle\FormBundle\Form\Type\DataChangesetType;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ValidationBundle\Validator\Constraints\Decimal;
use Oro\Bundle\ValidationBundle\Validator\Constraints\Integer;
use Oro\Bundle\InventoryBundle\Form\DataTransformer\InventoryLevelGridDataTransformer;

class InventoryLevelGridType extends AbstractType
{
    const NAME = 'oro_inventory_level_grid';

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param FormFactoryInterface $formFactory
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(FormFactoryInterface $formFactory, DoctrineHelper $doctrineHelper)
    {
        $this->formFactory = $formFactory;
        $this->doctrineHelper = $doctrineHelper;
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
        return DataChangesetType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addViewTransformer(
            new InventoryLevelGridDataTransformer($this->doctrineHelper, $options['product']),
            true
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('product');
        $resolver->setAllowedTypes('product', 'Oro\Bundle\ProductBundle\Entity\Product');
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        /** @var Product $product */
        $product = $options['product'];

        $view->vars['product'] = $product;
        $view->vars['unitPrecisions'] = $this->getUnitPrecisions($product);
        $view->vars['quantityConstraints'] = $this->getQuantityConstraints();
    }

    /**
     * Returns list of units with precisions
     * [ "<unitCode>" => <unitPrecision>, ... ]
     *
     * @param Product $product
     * @return array
     */
    protected function getUnitPrecisions(Product $product)
    {
        $data = [];
        foreach ($product->getUnitPrecisions() as $precision) {
            $data[$precision->getProductUnitCode()] = $precision->getPrecision();
        }

        return $data;
    }

    /**
     * @return array
     */
    protected function getQuantityConstraints()
    {
        // build fake field to get correct definitions of JS constraints
        $view = $this->formFactory->create(
            'number',
            null,
            [
                'constraints' => [
                    new Range(['min' => 0]),
                    new Decimal(),
                    new Integer(),
                ]
            ]
        )->createView();

        return json_decode($view->vars['attr']['data-validation'], true);
    }
}
