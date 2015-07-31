<?php

namespace OroB2B\Bundle\ShoppingListBundle\Form\Type;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;

class FrontendLineItemWidgetType extends AbstractType
{
    const NAME = 'orob2b_shopping_list_frontend_line_item_widget';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

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
     * @param ShoppingListManager $shoppingListManager
     * @param TokenStorage $tokenStorage
     */
    public function __construct(
        ManagerRegistry $registry,
        ShoppingListManager $shoppingListManager,
        TokenStorage $tokenStorage
    ) {
        $this->registry = $registry;
        $this->shoppingListManager = $shoppingListManager;
        $this->accountUser = $tokenStorage->getToken()->getUser();
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
            );
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        /** @var ShoppingListRepository $shoppingListRepository */
        $shoppingListRepository = $currentShoppingList = $this->registry
            ->getManagerForClass($this->shoppingListClass)
            ->getRepository($this->shoppingListClass);
        $currentShoppingList = $shoppingListRepository->findCurrentForAccountUser($this->accountUser);

        $view->children['shoppingList']->vars['currentShoppingList'] = $currentShoppingList;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'create_shopping_list_handler' => [$this, 'createNewShoppingListHandler']
            ]
        );
    }

    /**
     * @param FormEvent $event
     */
    public function createNewShoppingListHandler(FormEvent $event)
    {
        $data = $event->getData();

        if (empty($data['shoppingList']) && !empty($data['shoppingListLabel'])) {
            $shoppingList = $this->shoppingListManager->createCurrent($data['shoppingListLabel']);
            $data['shoppingList'] = $shoppingList->getId();
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
     * {@inheritdoc}
     */
    public function getParent()
    {
        return FrontendLineItemType::NAME;
    }

    /**
     * @param string $shoppingListClass
     */
    public function setShoppingListClass($shoppingListClass)
    {
        $this->shoppingListClass = $shoppingListClass;
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
}
