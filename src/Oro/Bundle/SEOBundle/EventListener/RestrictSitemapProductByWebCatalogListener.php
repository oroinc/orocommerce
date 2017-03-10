<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\SEOBundle\Entity\WebCatalogProductLimitation;
use Oro\Bundle\SEOBundle\Event\RestrictSitemapEntitiesEvent;

class RestrictSitemapProductByWebCatalogListener
{
    use FeatureCheckerHolderTrait;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param RestrictSitemapEntitiesEvent $event
     */
    public function restrictQueryBuilder(RestrictSitemapEntitiesEvent $event)
    {
        if ($this->isEnabled()) {
            $this->restrict($event);
        }
    }

    /**
     * @param RestrictSitemapEntitiesEvent $event
     */
    private function restrict(RestrictSitemapEntitiesEvent $event)
    {
        $qb = $event->getQueryBuilder();
        $rootAliases = $qb->getRootAliases();
        $qb->innerJoin(
            WebCatalogProductLimitation::class,
            'productLimitation',
            Join::WITH,
            $qb->expr()->andX(
                $qb->expr()->eq(reset($rootAliases), 'productLimitation.productId'),
                $qb->expr()->eq($event->getVersion(), 'productLimitation.version')
            )
        );
    }

    /**
     * @return bool
     */
    private function isEnabled()
    {
        return !$this->isFeaturesEnabled();
    }
}
