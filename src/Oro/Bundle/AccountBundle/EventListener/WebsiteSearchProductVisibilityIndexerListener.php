<?php

namespace Oro\Bundle\AccountBundle\EventListener;

use Oro\Bundle\AccountBundle\Visibility\Provider\AccountProductVisibilityProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;

class WebsiteSearchProductVisibilityIndexerListener
{
    const ACCOUNT_VISIBILITY_VALUE = 1;

    const FIELD_VISIBILITY_ANONYMOUS = 'visibility_anonymous';

    const FIELD_VISIBILITY_NEW = 'visibility_new';

    const FIELD_VISIBILITY_ACCOUNT = 'visibility_account';

    const FIELD_IS_VISIBLE_BY_DEFAULT = 'is_visible_by_default';

    const PLACEHOLDER_ACCOUNT_ID = 'ACCOUNT_ID';

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var AccountProductVisibilityProvider
     */
    private $visibilityProvider;

    /**
     *
     * @param DoctrineHelper $doctrineHelper
     * @param AccountProductVisibilityProvider $visibilityProvider
     */
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
            $event->getEntities(),
            $websiteId
        );

        foreach ($accountVisibilities as $accountVisibility) {
            $event->addPlaceholderField(
                $accountVisibility['productId'],
                self::FIELD_VISIBILITY_ACCOUNT,
                self::ACCOUNT_VISIBILITY_VALUE,
                [
                    self::PLACEHOLDER_ACCOUNT_ID => $accountVisibility['accountId']
                ]
            );
        }

        $newAndAnonymousVisibilities = $this->visibilityProvider->getNewUserAndAnonymousVisibilitiesForProducts(
            $event->getEntities(),
            $websiteId
        );

        foreach ($newAndAnonymousVisibilities as $visibility) {
            $event->addField(
                $visibility['productId'],
                self::FIELD_VISIBILITY_ANONYMOUS,
                $visibility['visibility_anonymous']
            );

            $event->addField(
                $visibility['productId'],
                self::FIELD_VISIBILITY_NEW,
                $visibility['visibility_new']
            );

            $event->addField(
                $visibility['productId'],
                self::FIELD_IS_VISIBLE_BY_DEFAULT,
                $visibility['is_visible_by_default']
            );
        }
    }
}
