<?php

namespace Oro\Bundle\AccountBundle\EventListener;

use Oro\Bundle\AccountBundle\Visibility\Provider\AccountProductVisibilityProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;

class WebsiteSearchProductVisibilityIndexerListener
{
    const VISIBLE = 1;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var AccountProductVisibilityProvider
     */
    private $visibilityProvider;

    public function __construct(DoctrineHelper $doctrineHelper, AccountProductVisibilityProvider $visibilityProvider)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->visibilityProvider = $visibilityProvider;
    }

    /**
     * @param IndexEntityEvent $event
     * @throws \InvalidArgumentException
     */
    public function onWebsiteSearchIndex(IndexEntityEvent $event)
    {
        $entityClass = $event->getEntityClass();
        if (!is_a($entityClass, Product::class, true)) {
            return;
        }

        $context = $event->getContext();

        if (!isset($context[AbstractIndexer::CONTEXT_WEBSITE_ID_KEY])) {
            throw new \InvalidArgumentException('Website id is absent in context');
        }

        $websiteId = $context[AbstractIndexer::CONTEXT_WEBSITE_ID_KEY];

        $accountVisibilities = $this->visibilityProvider->getAccountVisibilitiesForProducts(
            $event->getEntityIds(),
            $websiteId
        );

        foreach ($accountVisibilities as $accountVisibility) {
            $event->addField(
                $accountVisibility['productId'],
                Query::TYPE_INTEGER,
                'visibility_account_' . $accountVisibility['accountId'],
                self::VISIBLE
            );
        }

        $newAndAnonymousVisibilities = $this->visibilityProvider->getNewUserAndAnonymousVisibilitiesForProducts(
            $event->getEntityIds(),
            $websiteId
        );

        foreach ($newAndAnonymousVisibilities as $visibility) {
            $event->addField(
                $visibility['productId'],
                Query::TYPE_INTEGER,
                'visibility_anonymous',
                $visibility['visibility_anonymous']
            );

            $event->addField(
                $visibility['productId'],
                Query::TYPE_INTEGER,
                'visibility_new',
                $visibility['visibility_new']
            );
        }
    }
}
