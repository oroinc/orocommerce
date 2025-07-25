<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\PricingBundle\Cache\RuleCache;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Clears price rule caches and  add notification about prices recalculation
 * on Price Calculation Precision in Price Lists update.
 */
class PriceCalculationPrecisionSystemConfigListener
{
    private const NOTICE_TEXT_TRANS_KEY = 'oro.pricing.system_configuration.fields.price_calculation_precision.notice';
    private const MESSAGE_TYPE = 'warning';

    public function __construct(
        private ManagerRegistry $registry,
        private RuleCache $cache,
        private RequestStack $requestStack,
        private TranslatorInterface $translator
    ) {
    }

    public function updateAfter(ConfigUpdateEvent $event): void
    {
        if (!$event->isChanged('oro_pricing.price_calculation_precision')) {
            return;
        }

        if (!$this->clearRulesCache()) {
            return;
        }

        $this->addNotificationMessage();
    }

    private function clearRulesCache(): bool
    {
        $repo = $this->registry->getRepository(PriceRule::class);
        $ruleIds = $repo->getRuleIds();
        foreach ($ruleIds as $id) {
            $cacheKey = 'pr_' . $id;
            $this->cache->delete($cacheKey);
        }

        return count($ruleIds) > 0;
    }

    private function addNotificationMessage(): void
    {
        $message = sprintf(
            '%s <code>php bin/console %s --all</code>',
            $this->translator->trans(self::NOTICE_TEXT_TRANS_KEY),
            'oro:price-lists:schedule-recalculate'
        );
        $request = $this->requestStack->getCurrentRequest();
        if (null !== $request && $request->hasSession()) {
            $request->getSession()->getFlashBag()->add(self::MESSAGE_TYPE, $message);
        }
    }
}
