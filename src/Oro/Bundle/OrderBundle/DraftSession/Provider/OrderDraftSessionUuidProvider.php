<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\DraftSession\Provider;

use Symfony\Component\Routing\RequestContextAwareInterface;

/**
 * Provides the order draft session UUID from the request context.
 *
 * @bc-layer This class is retained for BC reasons. Use {@see DraftSessionUuidProvider} instead.
 */
class OrderDraftSessionUuidProvider
{
    public function __construct(
        private readonly RequestContextAwareInterface $router,
        private readonly string $parameterName,
    ) {
    }

    public function getDraftSessionUuid(): ?string
    {
        return $this->router->getContext()->getParameter($this->parameterName);
    }
}
