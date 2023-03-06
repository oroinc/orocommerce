<?php

namespace Oro\Bundle\OrderBundle\Controller;

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
use Oro\Bundle\OrderBundle\Provider\TotalProvider;
use Oro\Bundle\OrderBundle\RequestHandler\OrderRequestHandler;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Backend(admin) controller which handles CRUD operation for Order entity
 */
class OrderController extends AbstractController
{
    /**
     * @Route("/view/{id}", name="oro_order_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_order_view",
     *      type="entity",
     *      class="OroOrderBundle:Order",
     *      permission="VIEW",
     *      category="orders"
     * )
     */
    public function viewAction(Order $order): array
    {
        return [
            'entity' => $order,
            'totals' => $this->get(TotalProvider::class)->getTotalWithSubtotalsWithBaseCurrencyValues($order),
        ];
    }

    /**
     * @Route("/info/{id}", name="oro_order_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("oro_order_view")
     */
    public function infoAction(Order $order): array
    {
        if ($order->getSourceEntityClass() && $order->getSourceEntityId()) {
            $sourceEntity = $this->getDoctrine()
                ->getManagerForClass($order->getSourceEntityClass())
                ->find($order->getSourceEntityClass(), $order->getSourceEntityId());
        }

        return [
            'order' => $order,
            'sourceEntity' => $sourceEntity ?? null,
        ];
    }

    /**
     * @Route("/", name="oro_order_index")
     * @Template
     * @AclAncestor("oro_order_view")
     */
    public function indexAction(): array
    {
        return [
            'entity_class' => Order::class,
        ];
    }

    /**
     * Create order form
     *
     * @Route("/create", name="oro_order_create")
     * @Template("@OroOrder/Order/update.html.twig")
     * @Acl(
     *      id="oro_order_create",
     *      type="entity",
     *      class="OroOrderBundle:Order",
     *      permission="CREATE"
     * )
     */
    public function createAction(Request $request): array|RedirectResponse
    {
        $order = new Order();

        return $this->update($order, $request);
    }

    /**
     * Create order form for customer
     *
     * @Route(
     *     "/create/customer/{customer}",
     *     name="oro_order_create_for_customer",
     *     requirements={"customer"="\d+"}
     * )
     * @Template("@OroOrder/Order/update.html.twig")
     * @AclAncestor("oro_order_create")
     */
    public function createOrderForCustomerAction(
        Request $request,
        Customer $customer
    ): array|RedirectResponse {
        if (!$this->isGranted('VIEW', $customer)) {
            throw $this->createAccessDeniedException();
        }

        $order = new Order();
        $order->setCustomer($customer);

        $saveAndReturnActionFormTemplateDataProvider = $this->get(SaveAndReturnActionFormTemplateDataProvider::class);
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
     *
     * @Route(
     *     "/create/customer-user/{customerUser}",
     *     name="oro_order_create_for_customer_user",
     *     requirements={"customerUser"="\d+"}
     * )
     * @Template("@OroOrder/Order/update.html.twig")
     * @AclAncestor("oro_order_create")
     */
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

        $saveAndReturnActionFormTemplateDataProvider = $this->get(SaveAndReturnActionFormTemplateDataProvider::class);
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
     *
     * @Route("/update/{id}", name="oro_order_update", requirements={"id"="\d+"})
     * @ParamConverter("order", options={"repository_method" = "getOrderWithRelations"})
     * @Template
     * @Acl(
     *      id="oro_order_update",
     *      type="entity",
     *      class="OroOrderBundle:Order",
     *      permission="EDIT"
     * )
     */
    public function updateAction(Order $order, Request $request): array|RedirectResponse
    {
        return $this->update($order, $request);
    }

    protected function update(
        Order $order,
        Request $request,
        FormTemplateDataProviderInterface|null $resultProvider = null
    ): array|RedirectResponse {
        if (\in_array($request->getMethod(), ['POST', 'PUT'], true)) {
            $orderRequestHandler = $this->get(OrderRequestHandler::class);
            $order->setCustomer($orderRequestHandler->getCustomer());
            $order->setCustomerUser($orderRequestHandler->getCustomerUser());
        }

        $form = $this->createForm(OrderType::class, $order);

        $formTemplateDataProviderComposite = $this->get(FormTemplateDataProviderComposite::class)
            ->addFormTemplateDataProviders($resultProvider)
            ->addFormTemplateDataProviders(
                function (Order $order, FormInterface $form, Request $request) {
                    $submittedData = $request->get($form->getName());
                    $event = new OrderEvent($form, $form->getData(), $submittedData);
                    $this->get(EventDispatcherInterface::class)->dispatch($event, OrderEvent::NAME);
                    $orderData = $event->getData()->getArrayCopy();
                    $orderAddressSecurityProvider = $this->get(OrderAddressSecurityProvider::class);

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

        return $this->get(UpdateHandlerFacade::class)->update(
            $order,
            $form,
            $this->get(TranslatorInterface::class)->trans('oro.order.controller.order.saved.message'),
            $request,
            null,
            $formTemplateDataProviderComposite
        );
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
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
        ]);
    }
}
