<?php

namespace Oro\Bundle\WebsiteSearchBundle\Driver;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Indexer\ProductVisibilityIndexer;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\AccountIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\WebsiteIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Provider\PlaceholderProvider;

abstract class AbstractAccountPartialUpdateDriver implements AccountPartialUpdateDriverInterface
{
    /**
     * @var PlaceholderProvider
     */
    private $placeholderProvider;

    /**
     * @param PlaceholderProvider $placeholderProvider
     */
    public function __construct(PlaceholderProvider $placeholderProvider)
    {
        $this->placeholderProvider = $placeholderProvider;
    }

    /**
     * @param Website $website
     * @return string
     */
    protected function getProductAliasByWebsite(Website $website)
    {
        return $this->placeholderProvider->getPlaceholderEntityAlias(
            Product::class,
            [
                WebsiteIdPlaceholder::NAME => $website->getId(),
            ]
        );
    }

    /**
     * @return string
     */
    protected function getVisibilityNewFieldName()
    {
        return ProductVisibilityIndexer::FIELD_VISIBILITY_NEW;
    }

    /**
     * @return string
     */
    protected function getIsVisibleByDefaultFieldName()
    {
        return ProductVisibilityIndexer::FIELD_IS_VISIBLE_BY_DEFAULT;
    }

    /**
     * @param Account $account
     * @return string
     */
    protected function getAccountVisibilityFieldName(Account $account)
    {
        return $this->placeholderProvider->getPlaceholderFieldName(
            Product::class,
            ProductVisibilityIndexer::FIELD_VISIBILITY_ACCOUNT,
            [
                AccountIdPlaceholder::NAME => $account->getId(),
            ]
        );
    }
}
