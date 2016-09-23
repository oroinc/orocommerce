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
    const ACCOUNT_VISIBILITY_VALUE = 1;

    const FIELD_VISIBILITY_ANONYMOUS = 'visibility_anonymous';

    const FIELD_VISIBILITY_NEW = 'visibility_new';

    const FIELD_VISIBILITY_ACCOUNT = 'visibility_account_%s';

    const FIELD_IS_VISIBLE_BY_DEFAULT = 'is_visible_by_default';

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
                self::FIELD_IS_VISIBLE_BY_DEFAULT,
                $accountVisibility['is_visible_by_default']
            );

            $event->addField(
                $accountVisibility['productId'],
                Query::TYPE_INTEGER,
                sprintf(self::FIELD_VISIBILITY_ACCOUNT, $accountVisibility['accountId']),
                self::ACCOUNT_VISIBILITY_VALUE
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
                self::FIELD_VISIBILITY_ANONYMOUS,
                $visibility['visibility_anonymous']
            );

            $event->addField(
                $visibility['productId'],
                Query::TYPE_INTEGER,
                self::FIELD_VISIBILITY_NEW,
                $visibility['visibility_new']
            );
        }
    }
}
