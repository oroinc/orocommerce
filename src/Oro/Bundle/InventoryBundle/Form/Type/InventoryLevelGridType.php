<?php

namespace Oro\Bundle\InventoryBundle\Form\Type;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Form\Type\DataChangesetType;
use Oro\Bundle\InventoryBundle\Form\DataTransformer\InventoryLevelGridDataTransformer;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ValidationBundle\Validator\Constraints\Decimal;
use Oro\Bundle\ValidationBundle\Validator\Constraints\Integer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for editing product inventory levels
 */
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

    public function __construct(FormFactoryInterface $formFactory, DoctrineHelper $doctrineHelper)
    {
        $this->formFactory = $formFactory;
        $this->doctrineHelper = $doctrineHelper;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return DataChangesetType::class;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addViewTransformer(
            new InventoryLevelGridDataTransformer($this->doctrineHelper, $options['product']),
            true
        );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('product');
        $resolver->setAllowedTypes('product', 'Oro\Bundle\ProductBundle\Entity\Product');
    }

    #[\Override]
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
            NumberType::class,
            null,
            [
                'constraints' => [
                    new Decimal(),
                    new Integer(),
                ]
            ]
        )->createView();

        return json_decode($view->vars['attr']['data-validation'], true);
    }
}
