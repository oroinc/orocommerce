<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Twig;

use Oro\Bundle\OrderBundle\DraftSession\Manager\OrderDraftManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Component\DraftSession\Provider\DraftSessionUuidProvider;
use Oro\Component\DraftSession\Util\EntityDraftUtils;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to get the order draft session UUID.
 */
class OrderDraftExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    public function __construct(private readonly ContainerInterface $container)
    {
    }

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'oro_order_draft_session_uuid',
                [$this, 'getDraftSessionUuid']
            ),
            new TwigFunction(
                'oro_order_get_order_or_draft_id',
                [$this, 'getOrderOrDraftId']
            ),
            new TwigFunction(
                'oro_order_get_order_draft_id',
                [$this, 'getOrderDraftId']
            ),
        ];
    }

    public function getDraftSessionUuid(): ?string
    {
        return $this->container
            ->get('oro_order.draft_session.provider.draft_session_uuid')
            ->getDraftSessionUuid();
    }

    public function getOrderOrDraftId(Order $order): ?int
    {
        if ($this->getDraftSessionUuid() === null) {
            return $order->getId();
        }

        return EntityDraftUtils::getEntityOrDraftId($order);
    }

    public function getOrderDraftId(Order $order): ?int
    {
        if ($this->getDraftSessionUuid() === null) {
            return null;
        }

        $orderDraftManager = $this->container->get('oro_order.draft_session.manager.order_draft');
        assert($orderDraftManager instanceof OrderDraftManager);

        return $orderDraftManager->findEntityDraft($order)?->getId();
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            'oro_order.draft_session.provider.draft_session_uuid' => DraftSessionUuidProvider::class,
            'oro_order.draft_session.manager.order_draft' => OrderDraftManager::class,
        ];
    }
}
