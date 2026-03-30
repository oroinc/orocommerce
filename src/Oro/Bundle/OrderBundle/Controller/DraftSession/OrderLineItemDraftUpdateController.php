<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Controller\DraftSession;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrderBundle\DraftSession\Manager\OrderDraftManager;
use Oro\Bundle\OrderBundle\DraftSession\Provider\OrderLineItemTaxesAndDiscountsProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Form\Type\OrderLineItemDraftType;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
    #[AclAncestor('oro_order_update')]
    #[Template('@OroOrder/Order/orderLineItemDraftUpdate.html.twig')]
    public function __invoke(
        Request $request,
        #[MapEntity(expr: 'repository.getOrderWithRelations(orderId)')]
        ?Order $order,
        #[MapEntity(expr: 'repository.findOrderLineItemWithRelations(orderLineItemId)')]
        OrderLineItem $orderLineItem
    ): Response|array {
        $orderDraft = $this->getOrderDraft();
        $order = $this->syncFromOrderDraft($orderDraft, $order);
        $orderLineItem = $this->getOrderLineItemDraftSource($orderLineItem);

        $form = $this->createForm(OrderLineItemDraftType::class, $orderLineItem);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $responseData = [];
            $isDrySubmit = !empty($form->get('drySubmitTrigger')->getData());
            $isValid = $form->isValid();
            if ($isDrySubmit) {
                $form = $this->createForm(
                    OrderLineItemDraftType::class,
                    $orderLineItem,
                    ['initial_validation' => false]
                );
            } elseif ($isValid) {
                $this->syncToOrderLineItemDraft($orderDraft, $orderLineItem);

                $responseData = [
                    // Processed in _onJsonContentResponse. See in the 'oroui/js/widget/abstract-widget' for more.
                    'widget' => [
                        'trigger' => [
                            [
                                'eventBroker' => 'mediator',
                                'name' => 'datagrid:doRefresh:orderDraftGrid:order-line-items-edit-grid',
                                'args' => [
                                    'updatedIds' => [$this->getOrderLineItemOrDraftId($orderLineItem)],
                                ]
                            ],
                        ],
                    ],
                ];
            }

            $viewVars = $this->getViewVars($form, $order, $orderLineItem, $isDrySubmit);

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

    private function syncToOrderLineItemDraft(Order $orderDraft, OrderLineItem $orderLineItem): void
    {
        /** @var ManagerRegistry $doctrine */
        $doctrine = $this->container->get(ManagerRegistry::class);
        /** @var EntityManager $entityManager */
        $entityManager = $doctrine->getManagerForClass(OrderLineItem::class);

        // Clears the entity manager to avoid unintentional changes persistence.
        $entityManager->clear();

        // Retrieves the order draft reference from DB because the entity manager is cleared.
        /** @var Order $orderDraft */
        $orderDraft = $entityManager->getReference(Order::class, $orderDraft->getId());

        /** @var OrderDraftManager $orderDraftManager */
        $orderDraftManager = $this->container->get(OrderDraftManager::class);
        $orderLineItemDraft = $this->findOrCreateOrderLineItemDraft($orderDraft, $orderLineItem);

        if ($orderLineItemDraft->getId()) {
            // Synchronizes to the existing draft.
            $orderDraftManager->synchronizeEntityToDraft($orderLineItem, $orderLineItemDraft);
        }

        $entityManager->persist($orderLineItemDraft);
        $entityManager->flush();
    }

    private function getViewVars(
        FormInterface $form,
        Order $order,
        OrderLineItem $orderLineItem,
        bool $isDrySubmit
    ): array {
        $orderId = (int) $order->getId();
        $orderLineItemId = $this->getOrderLineItemOrDraftId($orderLineItem);

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
            ManagerRegistry::class,
            OrderDraftManager::class,
            OrderLineItemTaxesAndDiscountsProvider::class,
        ];
    }
}
