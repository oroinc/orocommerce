<?php

namespace Oro\Bundle\VisibilityBundle\Indexer;

use Oro\Bundle\VisibilityBundle\Visibility\Provider\ProductVisibilityProvider;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseVisibilityResolved;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\CustomerBundle\Placeholder\CustomerIdPlaceholder;

class ProductVisibilityIndexer
{
    const FIELD_VISIBILITY_ANONYMOUS = 'visibility_anonymous';

    const FIELD_VISIBILITY_NEW = 'visibility_new';

    const FIELD_VISIBILITY_ACCOUNT = 'visibility_customer_CUSTOMER_ID';

    const FIELD_IS_VISIBLE_BY_DEFAULT = 'is_visible_by_default';

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
        $customerVisibilities = $this->visibilityProvider->getCustomerVisibilitiesForProducts(
            $event->getEntities(),
            $websiteId
        );

        foreach ($customerVisibilities as $customerVisibility) {
            $event->addPlaceholderField(
                $customerVisibility['productId'],
                self::FIELD_VISIBILITY_ACCOUNT,
                BaseVisibilityResolved::VISIBILITY_VISIBLE,
                [
                    CustomerIdPlaceholder::NAME => $customerVisibility['customerId']
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
