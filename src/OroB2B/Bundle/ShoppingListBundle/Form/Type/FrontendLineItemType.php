<?php

namespace OroB2B\Bundle\ShoppingListBundle\Form\Type;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\FrontendBundle\EventListener\Form\Type\LineItemSubscriber;

class FrontendLineItemType extends AbstractType
{
    const NAME = 'orob2b_shopping_list_frontend_line_item';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @var ShoppingListManager
     */
    protected $shoppingListManager;

    /**
     * @var LineItemSubscriber
     */
    protected $lineItemSubscriber;

    /**
     * @param ShoppingListManager $shoppingListManager
     * @param TokenStorage $tokenStorage
     */
    public function __construct(
        ShoppingListManager $shoppingListManager,
        TokenStorage $tokenStorage
    ) {
        $this->shoppingListManager = $shoppingListManager;
        $this->accountUser = $tokenStorage->getToken()->getUser();
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var LineItem $formData */
        $formData = $builder->getForm()->getData();
        $product = $formData->getProduct();

        $builder
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
                    'query_builder' => function (ProductUnitRepository $repository) use ($product) {
                        return $repository->getProductUnitsQueryBuilder($product);
                    }
                ]
            )
            ->add(
                'shoppingListLabel',
                'text',
                [
                    'mapped' => false,
                    'required' => false,
                    'label' => 'orob2b.shoppinglist.lineitem.new_shopping_list_label'
                ]
            );

        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmitData']);
        $builder->addEventSubscriber($this->lineItemSubscriber);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => $this->dataClass,
                'constraints' => [
                    new Callback([
                        'groups' => ['add_product'],
                        'methods' => [[$this, 'checkShoppingListLabel']]
                    ])
                ],
                'validation_groups' => ['add_product'],
                'create_shopping_list_handler' => [$this, 'createNewShoppingListHandler']
            ]
        );
    }

    /**
     * @param LineItem $data
     * @param ExecutionContextInterface $context
     */
    public function checkShoppingListLabel($data, ExecutionContextInterface $context)
    {
        if (!$data->getShoppingList()) {
            $context->buildViolation('Shopping List label must not be empty')
                ->atPath('shoppingListLabel')
                ->addViolation();
        }
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
     *
     * @return $this
     */
    public function setDataClass($productClass)
    {
        $this->dataClass = $productClass;

        return $this;
    }

    /**
     * @param LineItemSubscriber $lineItemSubscriber
     */
    public function setLineItemSubscriber(LineItemSubscriber $lineItemSubscriber)
    {
        $this->lineItemSubscriber = $lineItemSubscriber;
    }

    /**
     * @param FormEvent $event
     */
    public function preSubmitData(FormEvent $event)
    {
        $createShoppingListHandler = $event->getForm()->getConfig()->getOption('create_shopping_list_handler');

        if (is_callable($createShoppingListHandler)) {
            call_user_func($createShoppingListHandler, $event);
        }
    }

    /**
     * @param FormEvent $event
     */
    public function createNewShoppingListHandler(FormEvent $event)
    {
        /** @var LineItem $lineItem */
        $lineItem = $event->getForm()->getData();
        $data = $event->getData();

        if (!$lineItem->getShoppingList() && !empty($data['shoppingListLabel'])) {
            $shoppingList = $this->shoppingListManager->createCurrent($data['shoppingListLabel']);
            $lineItem->setShoppingList($shoppingList);
        }
    }
}
