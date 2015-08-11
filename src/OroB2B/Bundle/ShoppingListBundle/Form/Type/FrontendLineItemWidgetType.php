<?php

namespace OroB2B\Bundle\ShoppingListBundle\Form\Type;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager;

class FrontendLineItemWidgetType extends AbstractType
{
    const NAME = 'orob2b_shopping_list_frontend_line_item_widget';

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
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param ManagerRegistry     $registry
     * @param ShoppingListManager $shoppingListManager
     * @param TokenStorage        $tokenStorage
     * @param TranslatorInterface $translator
     */
    public function __construct(
        ManagerRegistry $registry,
        ShoppingListManager $shoppingListManager,
        TokenStorage $tokenStorage,
        TranslatorInterface $translator
    ) {
        $this->registry = $registry;
        $this->shoppingListManager = $shoppingListManager;
        $this->accountUser = $tokenStorage->getToken()->getUser();
        $this->translator = $translator;
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
                'shoppingListLabel',
                'text',
                [
                    'mapped' => false,
                    'required' => false,
                    'label' => 'orob2b.shoppinglist.lineitem.new_shopping_list_label'
                ]
            );

        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmit']);
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
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
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
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'constraints' => [
                    new Callback([
                        'groups' => ['add_product'],
                        'methods' => [[$this, 'checkShoppingListLabel']]
                    ])
                ]
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
            $context->buildViolation(
                $this->translator->trans('orob2b.shoppinglist.lineitem.new_shopping_list_label.empty')
            )
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
}
