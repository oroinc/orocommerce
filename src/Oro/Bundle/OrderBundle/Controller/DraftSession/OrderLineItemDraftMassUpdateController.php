<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Controller\DraftSession;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrderBundle\DraftSession\Manager\OrderDraftManager;
use Oro\Bundle\OrderBundle\DraftSession\Provider\OrderLineItemTaxesAndDiscountsProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Form\Type\OrderLineItemDraftType;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller to get update forms for multiple draft order line items.
 */
final class OrderLineItemDraftMassUpdateController extends AbstractController
{
    use DraftSessionControllerTrait;

    #[Route(
        path: '/{orderId}/line-item/mass-update/{orderLineItemIds}/{orderDraftSessionUuid}',
        name: 'oro_order_line_item_draft_mass_update',
        requirements: [
            'orderId' => '\d+',
            'orderLineItemIds' => '^(?:\d+,)*\d+$',
            'orderDraftSessionUuid' => '%oro_order.draft_session.uuid_regex%',
        ]
    )]
    #[AclAncestor('oro_order_update')]
    public function __invoke(
        Request $request,
        #[MapEntity(expr: 'repository.getOrderWithRelations(orderId)')]
        ?Order $order,
        string $orderLineItemIds
    ): Response {
        $orderDraft = $this->getOrderDraft();
        $order = $this->syncFromOrderDraft($orderDraft, $order);

        $orderLineItemIdsArray = array_map('intval', explode(',', $orderLineItemIds));
        $responseData = ['success' => true, 'lineItems' => []];

        foreach ($order->getLineItems() as $orderLineItem) {
            $orderLineItemId = $this->getOrderLineItemOrDraftId($orderLineItem);
            if (!in_array($orderLineItemId, $orderLineItemIdsArray, true)) {
                continue;
            }

            $form = $this->createForm(OrderLineItemDraftType::class, $orderLineItem);

            $viewVars = $this->getViewVars($form, $order, $orderLineItem);
            $this->addTaxesAndDiscounts($viewVars, $orderLineItem);

            $responseData['lineItems'][] = [
                'lineItemId' => $orderLineItemId,
                'html' => $this->renderView('@OroOrder/Order/orderLineItemDraftUpdate.html.twig', $viewVars),
            ];
        }

        if (count($responseData['lineItems']) !== count($orderLineItemIdsArray)) {
            $notFoundIds = array_diff($orderLineItemIdsArray, array_column($responseData['lineItems'], 'lineItemId'));

            throw $this->createNotFoundException(
                sprintf('Order line items #%s not found.', implode(', #', $notFoundIds))
            );
        }

        return new JsonResponse($responseData);
    }

    private function getViewVars(FormInterface $form, Order $order, OrderLineItem $orderLineItem): array
    {
        $orderId = (int) $order->getId();
        $orderLineItemId = $this->getOrderLineItemOrDraftId($orderLineItem);

        return [
            'form' => $form->createView(),
            'orderLineItem' => $orderLineItem,
            'orderId' => $orderId,
            'orderLineItemId' => $orderLineItemId,
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
