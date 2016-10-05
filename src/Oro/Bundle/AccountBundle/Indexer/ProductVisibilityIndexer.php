<?php

namespace Oro\Bundle\AccountBundle\Indexer;

use Oro\Bundle\AccountBundle\Visibility\Provider\ProductVisibilityProvider;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;

class ProductVisibilityIndexer
{
    const ACCOUNT_VISIBILITY_VALUE = 1;

    const FIELD_VISIBILITY_ANONYMOUS = 'visibility_anonymous';

    const FIELD_VISIBILITY_NEW = 'visibility_new';

    const FIELD_VISIBILITY_ACCOUNT = 'visibility_account';

    const FIELD_IS_VISIBLE_BY_DEFAULT = 'is_visible_by_default';

    const PLACEHOLDER_ACCOUNT_ID = 'ACCOUNT_ID';

    /**
     * @var ProductVisibilityProvider
     */
    private $visibilityProvider;

    /**
     * @param ProductVisibilityProvider $visibilityProvider
     */
    public function __construct(ProductVisibilityProvider $visibilityProvider)
    {
        $this->visibilityProvider = $visibilityProvider;
    }

    /**
     * @param IndexEntityEvent $event
     * @param int $websiteId
     */
    public function addIndexInfo(IndexEntityEvent $event, $websiteId)
    {
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
