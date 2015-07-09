<?php
namespace OroB2B\Bundle\ShoppingListBundle\Form\Type;

use OroB2B\Bundle\ShoppingListBundle\Manager\LineItemManager;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductSelectType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use OroB2B\Bundle\ProductBundle\Rounding\RoundingService;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;

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
    private $lineItemManager;

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
     * @param string $productClass
     */
    public function setProductClass($productClass)
    {
        $this->productClass = $productClass;
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

        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmitData']);
    }

    /**
     * @param FormEvent $event
     */
    public function preSubmitData(FormEvent $event)
    {
        $data = $event->getData();

        if (!isset($data['product'], $data['unit'], $data['quantity'])) {
            return;
        }

        /** @var Product $product */
        $product = $this->registry
            ->getRepository($this->productClass)
            ->find($data['product']);

        if ($product) {
            $data['quantity'] = $this->lineItemManager
                ->roundProductQuantity($product, $data['unit'], $data['quantity']);

            $event->setData($data);
        }
    }

    /**
     * @param string $productClass
     *
     * @return $this
     */
    public function setDataClass($productClass)
    {
        $this->dataClass = $productClass;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => $this->dataClass
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
}
