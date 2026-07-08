<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Controller\DraftSession;

use Oro\Bundle\OrderBundle\DraftSession\Manager\OrderDraftManager;
use Oro\Bundle\OrderBundle\DraftSession\Provider\OrderLineItemTaxesAndDiscountsProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Form\Type\OrderLineItemDraftType;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Component\DraftSession\Util\EntityDraftUtils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Controller to update a draft order line item.
 */
final class OrderLineItemDraftUpdateController extends AbstractController
{
    use DraftSessionControllerTrait;

    #[Route(
        path: '/{orderId}/line-item/update/{orderLineItemId}/{orderDraftSessionUuid}',
        name: 'oro_order_line_item_draft_update',
        requirements: [
            'orderId' => '\d+',
            'orderLineItemId' => '\d+',
            'orderDraftSessionUuid' => '%oro_order.draft_session.uuid_regex%',
        ]
    )]
    #[ParamConverter(
        'order',
        class: Order::class,
        options: ['id' => 'orderId', 'repository_method' => 'getOrderWithRelations']
    )]
    #[ParamConverter(
        'orderLineItem',
        class: OrderLineItem::class,
        options: ['id' => 'orderLineItemId', 'repository_method' => 'findOrderLineItemWithRelations']
    )]
    #[AclAncestor('oro_order_update')]
    #[Template('@OroOrder/Order/orderLineItemDraftUpdate.html.twig')]
    public function __invoke(
        Request $request,
        Order $order,
        OrderLineItem $orderLineItem
    ): Response|array {
        $this->assertOrderDraftExists($order);

        $order = $this->getOrderDraftManager()->loadFromEntityDraft($order);
        assert($order instanceof Order);

        $orderLineItem = EntityDraftUtils::getEntityFromDraft($orderLineItem);
        assert($orderLineItem instanceof OrderLineItem);

        $form = $this->createForm(OrderLineItemDraftType::class, $orderLineItem);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $responseData = [];
            $isDrySubmit = !empty($form->get('drySubmitTrigger')->getData());
            $isValid = $form->isValid();
            if ($isDrySubmit) {
                if ($isValid) {
                    $form = $this->createForm(
                        OrderLineItemDraftType::class,
                        $orderLineItem,
                        ['initial_validation' => false]
                    );
                }

                $viewVars = $this->getViewVars($form, $order, $orderLineItem, true);
            } elseif ($isValid) {
                $viewVars = $this->getViewVars($form, $order, $orderLineItem, false);

                $this->getOrderDraftManager()->saveToEntityDraft($orderLineItem);

                $orderLineItemId = EntityDraftUtils::getEntityOrDraftId($orderLineItem);

                $responseData = [
                    // Processed in _onJsonContentResponse. See in the 'oroui/js/widget/abstract-widget' for more.
                    'widget' => [
                        'trigger' => [
                            [
                                'eventBroker' => 'mediator',
                                'name' => 'datagrid:doRefresh:orderDraftGrid:order-line-items-edit-grid',
                                'args' => [
                                    'updatedIds' => [$orderLineItemId],
                                ]
                            ],
                        ],
                    ],
                ];
            } else {
                $viewVars = $this->getViewVars($form, $order, $orderLineItem, false);
            }

            $responseData += [
                'success' => $isValid,
                'html' => $this->renderView('@OroOrder/Order/orderLineItemDraftUpdate.html.twig', $viewVars),
            ];

            return new JsonResponse($responseData);
        }

        $viewVars = $this->getViewVars($form, $order, $orderLineItem, false);
        $this->addTaxesAndDiscounts($viewVars, $orderLineItem);

        return $viewVars;
    }

    private function getViewVars(
        FormInterface $form,
        Order $order,
        OrderLineItem $orderLineItem,
        bool $isDrySubmit
    ): array {
        $orderId = EntityDraftUtils::getEntityOrDraftId($order);
        $orderLineItemId = EntityDraftUtils::getEntityOrDraftId($orderLineItem);

        return [
            'form' => $form->createView(),
            'orderLineItem' => $orderLineItem,
            'orderId' => $orderId,
            'orderLineItemId' => $orderLineItemId,
            'isDrySubmit' => $isDrySubmit,
        ];
    }

    private function addTaxesAndDiscounts(array &$data, OrderLineItem $orderLineItem): void
    {
        /** @var OrderLineItemTaxesAndDiscountsProvider $taxesAndDiscountsProvider */
        $taxesAndDiscountsProvider = $this->container->get(OrderLineItemTaxesAndDiscountsProvider::class);

        $data['lineItemTaxes'] = $taxesAndDiscountsProvider->getLineItemTaxes($orderLineItem);
        $data['lineItemDiscounts'] = $taxesAndDiscountsProvider->getLineItemDiscounts($orderLineItem);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            ...parent::getSubscribedServices(),
            AuthorizationCheckerInterface::class,
            OrderDraftManager::class,
            OrderLineItemTaxesAndDiscountsProvider::class,
        ];
    }
}
