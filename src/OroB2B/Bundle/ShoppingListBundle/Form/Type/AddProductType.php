<?php
namespace OroB2B\Bundle\ShoppingListBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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
                    'label' => 'orob2b.pricing.productprice.product.label',
                    'class' => $this->shoppingListClass,
                    'query_builder' => function (EntityRepository $repository) use ($accountUser) {
                        $qb = $repository->createQueryBuilder('sl');

                        return $qb->where(
                            $qb->expr()->orX(
                                'sl.accountUser = :accountUser',
                                'sl.account = :account'
                            )
                        )
                        ->setParameter('accountUser', $accountUser)
                        ->setParameter('account', $accountUser->getCustomer());
                    }
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
     * @param FormEvent $event
     */
    public function preSubmitData(FormEvent $event)
    {
        $data = $event->getData();

        // Create new current shopping list
        if (!$event->getForm()->get('shoppingList') && $data['shoppingListLabel']) {
            $shoppingList = $this->shoppingListManager->createCurrent($this->accountUser, $data['shoppingListLabel']);
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
            ->find($data['product']);

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
