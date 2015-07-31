<?php

namespace OroB2B\Bundle\ShoppingListBundle\Form\Type;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\ShoppingListBundle\Manager\LineItemManager;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductSelectType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\FrontendBundle\EventListener\Form\Type\LineItemSubscriber;

class LineItemType extends AbstractType
{
    const NAME = 'orob2b_shopping_list_line_item';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $productClass;

    /**
     * @var LineItemManager
     */
    protected $lineItemManager;

    /**
     * @var LineItemSubscriber
     */
    protected $lineItemSubscriber;

    /**
     * @param ManagerRegistry $registry
     * @param LineItemManager $lineItemManager
     */
    public function __construct(ManagerRegistry $registry, LineItemManager $lineItemManager)
    {
        $this->registry = $registry;
        $this->lineItemManager = $lineItemManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var LineItem $data */
        $data = $builder->getData();
        $isExisting = $data && $data->getId();

        $builder
            ->add(
                'product',
                ProductSelectType::NAME,
                [
                    'required' => true,
                    'label' => 'orob2b.pricing.productprice.product.label',
                    'create_enabled' => false,
                    'disabled' => $isExisting
                ]
            )
            ->add(
                'quantity',
                'text',
                [
                    'required' => true,
                    'label' => 'orob2b.pricing.productprice.quantity.label'
                ]
            )
            ->add(
                'unit',
                ProductUnitSelectionType::NAME,
                [
                    'required' => true,
                    'label' => 'orob2b.pricing.productprice.unit.label',
                    'empty_data' => null,
                    'empty_value' => 'orob2b.pricing.productprice.unit.choose'
                ]
            )
            ->add(
                'notes',
                'textarea',
                [
                    'required' => false,
                    'label' => 'orob2b.shoppinglist.lineitem.notes.label',
                    'empty_data' => null,
                ]
            );

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
        $builder->addEventSubscriber($this->lineItemSubscriber);
    }

    /**
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $entity = $event->getData();

        if (!($entity instanceof LineItem) || !$entity->getId()) {
            return;
        }

        $form = $event->getForm();

        $form->add(
            'unit',
            ProductUnitSelectionType::NAME,
            [
                'required' => true,
                'label' => 'orob2b.pricing.productprice.unit.label',
                'empty_data' => null,
                'empty_value' => 'orob2b.pricing.productprice.unit.choose',
                'query_builder' => function (ProductUnitRepository $er) use ($entity) {
                    return $er->getProductUnitsQueryBuilder($entity->getProduct());
                }
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param string $productClass
     */
    public function setProductClass($productClass)
    {
        $this->productClass = $productClass;
    }

    /**
     * @param LineItemSubscriber $lineItemSubscriber
     */
    public function setLineItemSubscriber(LineItemSubscriber $lineItemSubscriber)
    {
        $this->lineItemSubscriber = $lineItemSubscriber;
    }

    /**
     * @param string $dataClass
     *
     * @return $this
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => $this->dataClass,
                'validation_groups' => function (FormInterface $form) {
                    return $form->getData()->getId() ? ['update'] : ['create'];
                }
            ]
        );
    }
}
