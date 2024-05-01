<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\EventListener\WebsiteSearch;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerAwareInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Suggestion;

/**
 * Listener adds product suggestions data to the website search index
 */
class ProductSuggestionIndexerListener implements FeatureCheckerAwareInterface
{
    use FeatureCheckerHolderTrait;

    public function __construct(
        private WebsiteContextManager $websiteContextManager,
        private ManagerRegistry $doctrine
    ) {
    }

    public function onWebsiteSearchIndex(IndexEntityEvent $event): void
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $website = $this->getWebsite($event);
        if (!$website) {
            $event->stopPropagation();

            return;
        }

        /**
         * @var Suggestion $suggestion
         */
        foreach ($event->getEntities() as $suggestion) {
            $event->addField(
                $suggestion->getId(),
                'localization_id',
                $suggestion->getLocalization()->getId()
            );

            $event->addField(
                $suggestion->getId(),
                'words_count',
                $suggestion->getWordsCount()
            );

            $event->addField(
                $suggestion->getId(),
                'phrase',
                $suggestion->getPhrase()
            );
        }
    }

    private function getWebsite(IndexEntityEvent $event): ?Website
    {
        $websiteId = $this->websiteContextManager->getWebsiteId($event->getContext());
        if ($websiteId) {
            return $this->doctrine->getManagerForClass(Website::class)->find(Website::class, $websiteId);
        }

        return null;
    }
}
