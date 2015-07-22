<?php
namespace OroB2B\Bundle\ShoppingListBundle\Form\Type;

use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\ExecutionContextInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\ShoppingListBundle\Manager\LineItemManager;
use OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager;

class AddProductType extends AbstractType
{
    const NAME = 'orob2b_shopping_list_add_product';

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
     * @var ShoppingListManager
     */
    protected $shoppingListManager;

    /**
     * @var AccountUser
     */
    protected $accountUser;

    /**
     * @var string
     */
    protected $shoppingListClass;

    /**
     * @param ManagerRegistry $registry
     * @param LineItemManager $lineItemManager
     * @param ShoppingListManager $shoppingListManager
     * @param SecurityContext $securityContext
     */
    public function __construct(
        ManagerRegistry $registry,
        LineItemManager $lineItemManager,
        ShoppingListManager $shoppingListManager,
        SecurityContext $securityContext
    ) {
        $this->registry = $registry;
        $this->lineItemManager = $lineItemManager;
        $this->shoppingListManager = $shoppingListManager;

        $this->accountUser = $securityContext->getToken()->getUser();
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $accountUser = $this->accountUser;

        $builder
            ->add(
                'shoppingList',
                'entity',
                [
                    'required' => false,
                    'label' => 'orob2b.shoppinglist.lineitem.shopping_list.label',
                    'class' => $this->shoppingListClass,
                    'query_builder' => function (ShoppingListRepository $repository) use ($accountUser) {
                        return $repository->createFindForAccountUserQueryBuilder($accountUser);
                    },
                    'empty_value' => 'orob2b.shoppinglist.lineitem.create_new_shopping_list',
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
                'shoppingListLabel',
                'text',
                [
                    'mapped' => false,
                    'required' => false,
                    'label' => 'orob2b.shoppinglist.lineitem.new_shopping_list_label'
                ]
            );

        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmitData']);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $currentShoppingList = $this->registry
            ->getRepository($this->shoppingListClass)
            ->findCurrentForAccountUser($this->accountUser);

        $view->children['shoppingList']->vars['currentShoppingList'] = $currentShoppingList;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
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
                'validation_groups' => ['add_product']
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
            $context->addViolationAt('shoppingListLabel', 'Shopping List label must not be empty');
        }
    }

    /**
     * @param FormEvent $event
     */
    public function preSubmitData(FormEvent $event)
    {
        $data = $event->getData();

        /** @var LineItem $formData */
        $formData = $event->getForm()->getData();

        // Create new current shopping list
        if (!$data['shoppingList'] && $data['shoppingListLabel']) {
            $shoppingList = $this->shoppingListManager
                ->createCurrent($this->accountUser, $data['shoppingListLabel'], false);

            $data['shoppingList'] = $shoppingList->getId();
            $event->setData($data);
        }

        // Round quantity
        if (!isset($data['unit'], $data['quantity'])) {
            return;
        }

        /** @var Product $product */
        $product = $this->registry
            ->getRepository($this->productClass)
        ->find($formData->getProduct());

        if ($product) {
            $data['quantity'] = $this->lineItemManager
                ->roundProductQuantity($product, $data['unit'], $data['quantity']);

            $event->setData($data);
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
     * @param string $productClass
     */
    public function setProductClass($productClass)
    {
        $this->productClass = $productClass;
    }

    /**
     * @param string $shoppingListClass
     */
    public function setShoppingListClass($shoppingListClass)
    {
        $this->shoppingListClass = $shoppingListClass;
    }
}
