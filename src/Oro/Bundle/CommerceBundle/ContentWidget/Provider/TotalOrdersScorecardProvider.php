<?php

namespace Oro\Bundle\CommerceBundle\ContentWidget\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * The scorecard that provides the sum of all non-Cancelled orders that the current customer user has View access to
 */
class TotalOrdersScorecardProvider implements ScorecardInterface
{
    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly WebsiteManager $websiteManager,
        private readonly UserCurrencyManager $userCurrencyManager,
        private readonly NumberFormatter $numberFormatter,
        private readonly RoundingServiceInterface $priceRoundingService,
        private array $excludedInternalStatuses = []
    ) {
    }

    public function setExcludedInternalStatuses(array $excludedInternalStatuses): void
    {
        $this->excludedInternalStatuses = $excludedInternalStatuses;
    }

    #[\Override]
    public function getName(): string
    {
        return 'total_orders';
    }

    #[\Override]
    public function getLabel(): string
    {
        return 'oro.commerce.content_widget_type.scorecard.total_orders';
    }

    #[\Override]
    public function isVisible(): bool
    {
        return $this->authorizationChecker->isGranted(BasicPermission::VIEW, new Order());
    }

    #[\Override]
    public function getData(): ?string
    {
        $websiteId = $this->getWebsiteId();
        if ($websiteId === null) {
            return null;
        }

        $currency = $this->userCurrencyManager->getUserCurrency();
        if ($currency === null) {
            return null;
        }

        $sumTotal = $this->registry->getRepository(Order::class)
            ->getSumTotalOrders($websiteId, $currency, $this->excludedInternalStatuses);

        if ($sumTotal === null) {
            return null;
        }

        return $this->formatSumTotal($sumTotal, $currency);
    }

    private function formatSumTotal(string $sumTotal, string $currency): string
    {
        return $this->numberFormatter->formatCurrency($this->priceRoundingService->round($sumTotal), $currency);
    }

    private function getWebsiteId(): ?int
    {
        return $this->websiteManager->getCurrentWebsite()?->getId();
    }
}
