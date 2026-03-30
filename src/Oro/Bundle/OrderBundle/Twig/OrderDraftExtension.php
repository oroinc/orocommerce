<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Twig;

use Oro\Bundle\OrderBundle\DraftSession\Provider\OrderDraftSessionUuidProvider;
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
        ];
    }

    public function getDraftSessionUuid(): ?string
    {
        return $this->container
            ->get('oro_order.draft_session.provider.order_draft_session_uuid')
            ->getDraftSessionUuid();
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            'oro_order.draft_session.provider.order_draft_session_uuid' => OrderDraftSessionUuidProvider::class,
        ];
    }
}
