<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\PricingBundle\Cache\RuleCache;
use Oro\Bundle\PricingBundle\Command\PriceListScheduleRecalculateCommand;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Clears price rule caches and  add notification about prices recalculation
 * on Price Calculation Precision in Price Lists update.
 */
class PriceCalculationPrecisionSystemConfigListener
{
    private const NOTICE_TEXT_TRANS_KEY = 'oro.pricing.system_configuration.fields.price_calculation_precision.notice';
    private const MESSAGE_TYPE = 'warning';

    private ManagerRegistry $registry;
    private RuleCache $cache;
    private Session $session;
    private TranslatorInterface $translator;

    public function __construct(
        ManagerRegistry $registry,
        RuleCache $cache,
        Session $session,
        TranslatorInterface $translator
    ) {
        $this->registry = $registry;
        $this->cache = $cache;
        $this->session = $session;
        $this->translator = $translator;
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
            PriceListScheduleRecalculateCommand::getDefaultName()
        );
        $this->session->getFlashBag()->add(self::MESSAGE_TYPE, $message);
    }
}
