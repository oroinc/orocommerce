<?php

namespace Oro\Bundle\OrderBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderComposite;
use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface;
use Oro\Bundle\FormBundle\Provider\SaveAndReturnActionFormTemplateDataProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;
use Oro\Bundle\OrderBundle\Provider\OrderDuplicator;
use Oro\Bundle\OrderBundle\Provider\TotalProvider;
use Oro\Bundle\OrderBundle\RequestHandler\OrderRequestHandler;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\UserBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Backend(admin) controller which handles CRUD operation for Order entity
 */
class OrderController extends AbstractController
{
    #[Route(path: '/view/{id}', name: 'oro_order_view', requirements: ['id' => '\d+'])]
    #[Template]
    #[Acl(id: 'oro_order_view', type: 'entity', class: Order::class, permission: 'VIEW', category: 'orders')]
    public function viewAction(Order $order): array
    {
        return [
            'entity' => $order,
            'totals' => $this->container->get(TotalProvider::class)
                ->getTotalFromOrderWithSubtotalsWithBaseCurrencyValues($order),
        ];
    }

    #[Route(path: '/info/{id}', name: 'oro_order_info', requirements: ['id' => '\d+'])]
    #[Template]
    #[AclAncestor('oro_order_view')]
    public function infoAction(Order $order): array
    {
        if ($order->getSourceEntityClass() && $order->getSourceEntityId()) {
            $sourceEntity = $this->container->get('doctrine')
                ->getManagerForClass($order->getSourceEntityClass())
                ->find($order->getSourceEntityClass(), $order->getSourceEntityId());
        }

        return [
            'order' => $order,
            'sourceEntity' => $sourceEntity ?? null,
        ];
    }

    #[Route(path: '/', name: 'oro_order_index')]
    #[Template]
    #[AclAncestor('oro_order_view')]
    public function indexAction(): array
    {
        return [
            'entity_class' => Order::class,
        ];
    }

    /**
     * Create order form
     */
    #[Route(path: '/create', name: 'oro_order_create')]
    #[Template('@OroOrder/Order/update.html.twig')]
    #[Acl(id: 'oro_order_create', type: 'entity', class: Order::class, permission: 'CREATE')]
    public function createAction(Request $request): array|RedirectResponse
    {
        $order = new Order();

        return $this->update($order, $request);
    }

    /**
     * Create order form for customer
     */
    #[Route(
        path: '/create/customer/{customer}',
        name: 'oro_order_create_for_customer',
        requirements: ['customer' => '\d+']
    )]
    #[Template('@OroOrder/Order/update.html.twig')]
    #[AclAncestor('oro_order_create')]
    public function createOrderForCustomerAction(
        Request $request,
        Customer $customer
    ): array|RedirectResponse {
        if (!$this->isGranted('VIEW', $customer)) {
            throw $this->createAccessDeniedException();
        }

        $order = new Order();
        $order->setCustomer($customer);

        $saveAndReturnActionFormTemplateDataProvider = $this->container
            ->get(SaveAndReturnActionFormTemplateDataProvider::class);
        $saveAndReturnActionFormTemplateDataProvider
            ->setSaveFormActionRoute(
                'oro_order_create_for_customer',
                [
                    'customer' => $customer->getId(),
                ]
            )
            ->setReturnActionRoute(
                'oro_customer_customer_view',
                [
                    'id' => $customer->getId(),
                ],
                'oro_customer_customer_view'
            );

        return $this->update($order, $request, $saveAndReturnActionFormTemplateDataProvider);
    }

    /**
     * Create order form with defined customer user
     */
    #[Route(
        path: '/create/customer-user/{customerUser}',
        name: 'oro_order_create_for_customer_user',
        requirements: ['customerUser' => '\d+']
    )]
    #[Template('@OroOrder/Order/update.html.twig')]
    #[AclAncestor('oro_order_create')]
    public function createOrderForCustomerUserAction(
        Request $request,
        CustomerUser $customerUser
    ): array|RedirectResponse {
        if (!$this->isGranted('VIEW', $customerUser)) {
            throw $this->createAccessDeniedException();
        }

        $order = new Order();
        $order->setCustomerUser($customerUser);
        $order->setCustomer($customerUser->getCustomer());

        $saveAndReturnActionFormTemplateDataProvider = $this->container
            ->get(SaveAndReturnActionFormTemplateDataProvider::class);
        $saveAndReturnActionFormTemplateDataProvider
            ->setSaveFormActionRoute(
                'oro_order_create_for_customer_user',
                [
                    'customerUser' => $customerUser->getId(),
                ]
            )
            ->setReturnActionRoute(
                'oro_customer_customer_user_view',
                [
                    'id' => $customerUser->getId(),
                ],
                'oro_customer_customer_user_view'
            );

        return $this->update($order, $request, $saveAndReturnActionFormTemplateDataProvider);
    }

    /**
     * Edit order form
     */
    #[Route(path: '/update/{id}', name: 'oro_order_update', requirements: ['id' => '\d+'])]
    #[ParamConverter('order', options: ['repository_method' => 'getOrderWithRelations'])]
    #[Template]
    #[Acl(id: 'oro_order_update', type: 'entity', class: Order::class, permission: 'EDIT')]
    public function updateAction(Order $order, Request $request): array|RedirectResponse
    {
        return $this->update($order, $request);
    }

    #[Route(path: '/reorder/{id}', name: 'oro_order_reorder', requirements: ['id' => '\d+'])]
    #[ParamConverter('oldOrder', options: ['repository_method' => 'getOrderWithRelations'])]
    #[Template]
    #[AclAncestor('oro_order_view')]
    public function reorderAction(Order $oldOrder, Request $request): array|RedirectResponse
    {
        if (!$this->isGranted('oro_order_create') || !$oldOrder->getSubOrders()->isEmpty()) {
            throw $this->createAccessDeniedException();
        }

        return $this->update(
            $this->container->get('oro_order.duplicator.order_duplicator')->duplicate($oldOrder),
            $request,
            function (Order $order, FormInterface $form, Request $request) use ($oldOrder) {
                return [
                    'entity' => $order,
                    'form' => $form->createView(),
                    'returnAction' => [
                        'route' => 'oro_order_view',
                        'parameters' => ['id' => $order->getId()],
                        'aclRole' => 'oro_order_view'
                    ],
                    'oldOrder' => $oldOrder
                ];
            }
        );
    }

    protected function update(
        Order $order,
        Request $request,
        callable|FormTemplateDataProviderInterface|null $resultProvider = null
    ): array|RedirectResponse {
        if (\in_array($request->getMethod(), ['POST', 'PUT'], true)) {
            $orderRequestHandler = $this->container->get(OrderRequestHandler::class);
            $order->setCustomer($orderRequestHandler->getCustomer());
            $order->setCustomerUser($orderRequestHandler->getCustomerUser());

            if (null === $order->getId()) {
                $user = $this->getUser();

                if ($user instanceof User) {
                    $order->setCreatedBy($user);
                }
            }
        }

        $form = $this->createForm(
            OrderType::class,
            $order,
            ['validation_groups' => $this->getValidationGroups($order)]
        );

        $formTemplateDataProviderComposite = $this->container->get(FormTemplateDataProviderComposite::class)
            ->addFormTemplateDataProviders($resultProvider)
            ->addFormTemplateDataProviders(
                function (Order $order, FormInterface $form, Request $request) {
                    $submittedData = $request->get($form->getName());
                    $event = new OrderEvent($form, $form->getData(), $submittedData);
                    $this->container->get(EventDispatcherInterface::class)->dispatch($event, OrderEvent::NAME);
                    $orderData = $event->getData()->getArrayCopy();
                    $orderAddressSecurityProvider = $this->container->get(OrderAddressSecurityProvider::class);

                    return [
                        'form' => $form->createView(),
                        'entity' => $order,
                        'isWidgetContext' => (bool)$request->get('_wid', false),
                        'isShippingAddressGranted' => $orderAddressSecurityProvider
                            ->isAddressGranted($order, AddressType::TYPE_SHIPPING),
                        'isBillingAddressGranted' => $orderAddressSecurityProvider
                            ->isAddressGranted($order, AddressType::TYPE_BILLING),
                        'orderData' => $orderData,
                    ];
                }
            );

        return $this->container->get(UpdateHandlerFacade::class)->update(
            $order,
            $form,
            $this->container->get(TranslatorInterface::class)->trans('oro.order.controller.order.saved.message'),
            $request,
            null,
            $formTemplateDataProviderComposite
        );
    }

    protected function getValidationGroups(Order $order): GroupSequence|array|string
    {
        return new GroupSequence(
            [Constraint::DEFAULT_GROUP, $order->getId() ? 'order_update' : 'order_create']
        );
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            OrderRequestHandler::class,
            TotalProvider::class,
            OrderAddressSecurityProvider::class,
            TranslatorInterface::class,
            EventDispatcherInterface::class,
            UpdateHandlerFacade::class,
            SaveAndReturnActionFormTemplateDataProvider::class,
            FormTemplateDataProviderComposite::class,
            'doctrine' => ManagerRegistry::class,
            'oro_order.duplicator.order_duplicator' => OrderDuplicator::class,
        ]);
    }
}
