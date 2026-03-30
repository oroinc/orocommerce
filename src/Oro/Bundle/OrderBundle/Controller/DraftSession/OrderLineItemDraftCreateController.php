<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Controller\DraftSession;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrderBundle\DraftSession\Manager\OrderDraftManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Form\Type\OrderLineItemDraftType;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller to create a draft order line items.
 */
final class OrderLineItemDraftCreateController extends AbstractController
{
    use DraftSessionControllerTrait;

    #[Route(
        path: '/{orderId}/line-item/create/{orderDraftSessionUuid}',
        name: 'oro_order_line_item_draft_create',
        requirements: [
            'orderId' => '\d+',
            'orderDraftSessionUuid' => '%oro_order.draft_session.uuid_regex%',
        ]
    )]
    #[AclAncestor('oro_order_update')]
    #[Template('@OroOrder/Order/orderLineItemDraftCreate.html.twig')]
    public function __invoke(
        Request $request,
        #[MapEntity(expr: 'repository.getOrderWithRelations(orderId)')]
        ?Order $order
    ): Response|array {
        $orderDraft = $this->getOrderDraft();

        /** @var OrderDraftManager $orderDraftManager */
        $orderDraftManager = $this->container->get(OrderDraftManager::class);
        $orderLineItemDraft = $orderDraftManager->createOrderLineItemDraft($orderDraft);

        $form = $this->createForm(OrderLineItemDraftType::class, $orderLineItemDraft, ['initial_validation' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $responseData = ['success' => true];

            if ($form->get('drySubmitTrigger')->getData()) {
                $form = $this->createForm(
                    OrderLineItemDraftType::class,
                    $orderLineItemDraft,
                    ['initial_validation' => false]
                );
            } elseif ($form->isValid()) {
                $doctrine = $this->container->get(ManagerRegistry::class);
                $entityManager = $doctrine->getManagerForClass(OrderLineItem::class);
                $entityManager->persist($orderLineItemDraft);
                $entityManager->flush();

                $createdOrderLineItemDraft = $orderLineItemDraft;

                $orderLineItemDraft = $orderDraftManager->createOrderLineItemDraft($orderDraft);

                $form = $this->createForm(
                    OrderLineItemDraftType::class,
                    $orderLineItemDraft,
                    ['initial_validation' => false]
                );

                $responseData += [
                    // Processed in _onJsonContentResponse. See in the 'oroui/js/widget/abstract-widget' for more.
                    'widget' => [
                        'trigger' => [
                            [
                                'eventBroker' => 'mediator',
                                'name' => 'datagrid:doRefresh:orderDraftGrid:order-line-items-edit-grid',
                                'args' => [
                                    'updatedIds' => [$createdOrderLineItemDraft->getId()],
                                ],
                            ],
                        ],
                    ],
                ];
            }

            $responseData += [
                'html' => $this->renderView(
                    '@OroOrder/Order/orderLineItemDraftCreate.html.twig',
                    [
                        'form' => $form->createView(),
                        'entity' => $orderLineItemDraft,
                        'createdEntity' => $createdOrderLineItemDraft ?? null,
                    ]
                ),
            ];

            return new JsonResponse($responseData);
        }

        return [
            'form' => $form->createView(),
            'entity' => $orderLineItemDraft,
        ];
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            ...parent::getSubscribedServices(),
            ManagerRegistry::class,
            OrderDraftManager::class,
        ];
    }
}
