<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Controller\Frontend;

use Oro\Bundle\CheckoutBundle\Resolver\ShoppingListToCheckoutValidationGroupResolver;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\StartShoppingListCheckout;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerAwareInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\LayoutBundle\Attribute\Layout;
use Oro\Bundle\RFPBundle\Resolver\ShoppingListToRequestQuoteValidationGroupResolver;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;
use Oro\Bundle\ShoppingListBundle\Storage\ProductDataStorage;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Controller that manages errors on starting checkout or rfq from shopping list via AJAX requests.
 */
final class AjaxShoppingListErrorsModalController extends AbstractLineItemController implements
    FeatureCheckerAwareInterface
{
    use FeatureCheckerHolderTrait;

    private const string ACTION_SAVE_FOR_LATER = 'save_for_later';
    private const string ACTION_DELETE = 'delete';

    #[Route(
        path: '/{id}',
        name: 'oro_shopping_list_frontend_errors_modal',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST'],
    )]
    #[Layout]
    #[AclAncestor('oro_shopping_list_frontend_update')]
    public function __invoke(ShoppingList $shoppingList, Request $request): array|JsonResponse
    {
        $triggeredBy = $request->get('triggered_by');
        $action = $this->resolveAction($request);

        if (!$request->isXmlHttpRequest() || $request->get('render') === 'true') {
            return [
                'data' => [
                    'entity'       => $shoppingList,
                    'triggered_by' => $triggeredBy,
                    'action' => $action
                ],
            ];
        }

        return $this->handleAjax($shoppingList, $request, $triggeredBy, $action);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            ShoppingListManager::class,
            ShoppingListTotalManager::class,
            StartShoppingListCheckout::class,
            ProductDataStorage::class,
            LoggerInterface::class
        ]);
    }

    private function handleAjax(
        ShoppingList $shoppingList,
        Request $request,
        ?string $triggeredBy,
        string $action
    ): JsonResponse {
        $invalidLineItems = $this->getInvalidLineItems($shoppingList, $request);
        if (!empty($invalidLineItems)) {
            if ($action === self::ACTION_SAVE_FOR_LATER) {
                $this->saveForLaterInvalidLineItems($shoppingList, $invalidLineItems);
            } else {
                $this->deleteInvalidLineItems($shoppingList, $invalidLineItems);
            }
        }

        if ($shoppingList->getLineItems()->isEmpty()) {
            return $this->jsonError(
                'oro.frontend.shoppinglist.messages.cannot_process_shopping_list_no_items'
            );
        }

        return match ($triggeredBy) {
            ShoppingListToCheckoutValidationGroupResolver::TYPE => $this->startCheckout($shoppingList),
            ShoppingListToRequestQuoteValidationGroupResolver::TYPE => $this->startRfq($shoppingList),
            default    => $this->unsupportedAction($triggeredBy, $shoppingList),
        };
    }

    private function startCheckout(ShoppingList $shoppingList): JsonResponse
    {
        $result = $this->container->get(StartShoppingListCheckout::class)->execute($shoppingList);

        return new JsonResponse([
            'success'     => true,
            'redirectUrl' => $result['redirectUrl'] ?? null,
        ]);
    }

    private function startRfq(ShoppingList $shoppingList): JsonResponse
    {
        $this->container->get(ProductDataStorage::class)->saveToStorage($shoppingList);

        return new JsonResponse([
            'success'     => true,
            'redirectUrl' => $this->generateUrl('oro_rfp_frontend_request_create', ['storage' => true]),
        ]);
    }

    private function unsupportedAction(string $triggeredBy, ShoppingList $shoppingList): JsonResponse
    {
        $this->container->get(LoggerInterface::class)->warning('Unsupported triggered_by value', [
            'triggered_by'   => $triggeredBy,
            'shoppingListId' => $shoppingList->getId(),
        ]);

        return $this->jsonError('oro.frontend.shoppinglist.messages.cannot_process_checkout_or_rfq');
    }

    private function jsonError(string $translationKey): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'message' => $this->container->get(TranslatorInterface::class)->trans($translationKey),
        ]);
    }

    private function saveForLaterInvalidLineItems(ShoppingList $shoppingList, array $invalidLineItems): void
    {
        foreach ($invalidLineItems as $lineItem) {
            $lineItem->setSavedForLaterList($shoppingList);
            $shoppingList->removeLineItem($lineItem);

            $this->container->get(ShoppingListManager::class)->addLineItem($lineItem, $shoppingList, false);
        }
        $this->container->get(ShoppingListTotalManager::class)->recalculateTotals($shoppingList, true);
    }

    private function getInvalidLineItems(ShoppingList $shoppingList, Request $request): array
    {
        $invalidIds = $request->get('invalidIds');
        if (empty($invalidIds)) {
            return [];
        }

        $invalidLineItems = [];
        foreach ($shoppingList->getLineItems() as $lineItem) {
            if (\in_array($lineItem->getId(), $invalidIds)) {
                $invalidLineItems[] = $lineItem;
            }
        }

        return $invalidLineItems;
    }

    private function resolveAction(Request $request): string
    {
        $action = (string) $request->get('action');

        if ($action === '') {
            throw $this->createNotFoundException('Action parameter is required.');
        }

        if (!\in_array($action, [self::ACTION_SAVE_FOR_LATER, self::ACTION_DELETE], true)) {
            throw $this->createNotFoundException(sprintf('Unsupported action "%s".', $action));
        }

        if ($action === self::ACTION_SAVE_FOR_LATER && !$this->isFeaturesEnabled()) {
            throw $this->createNotFoundException('Save for later action is disabled.');
        }

        return $action;
    }

    private function deleteInvalidLineItems(ShoppingList $shoppingList, array $invalidLineItems): void
    {
        foreach ($invalidLineItems as $lineItem) {
            $shoppingList->removeLineItem($lineItem);
            $this->container->get(ShoppingListManager::class)->removeLineItem($lineItem, true);
        }

        $this->container->get(ShoppingListTotalManager::class)->recalculateTotals($shoppingList, true);
    }
}
