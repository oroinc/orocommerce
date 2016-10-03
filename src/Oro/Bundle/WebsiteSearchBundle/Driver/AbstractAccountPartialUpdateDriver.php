<?php

namespace Oro\Bundle\WebsiteSearchBundle\Driver;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Indexer\ProductVisibilityIndexer;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\AccountIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\VisitorReplacePlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\WebsiteIdPlaceholder;

abstract class AbstractAccountPartialUpdateDriver implements AccountPartialUpdateDriverInterface
{
    /**
     * @var VisitorReplacePlaceholder
     */
    private $visitorReplacePlaceholder;

    /**
     * @var AbstractSearchMappingProvider
     */
    private $mappingProvider;

    /**
     * @param VisitorReplacePlaceholder $visitorReplacePlaceholder
     * @param AbstractSearchMappingProvider $mappingProvider
     */
    public function __construct(
        VisitorReplacePlaceholder $visitorReplacePlaceholder,
        AbstractSearchMappingProvider $mappingProvider
    ) {
        $this->visitorReplacePlaceholder = $visitorReplacePlaceholder;
        $this->mappingProvider = $mappingProvider;
    }

    /**
     * @param Website $website
     * @return string
     */
    protected function getProductAliasByWebsite(Website $website)
    {
        $entityAlias = $this->mappingProvider->getEntityAlias(Product::class);
        $entityAlias = $this->visitorReplacePlaceholder->replace($entityAlias, [
            WebsiteIdPlaceholder::NAME => $website->getId()
        ]);

        return $entityAlias;
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
        $fields = $this->mappingProvider->getEntityMapParameter(Product::class, 'fields');
        $alias = $fields[ProductVisibilityIndexer::FIELD_VISIBILITY_ACCOUNT]['name'];

        return $this->visitorReplacePlaceholder->replace($alias, [
            AccountIdPlaceholder::NAME => $account->getId()
        ]);
    }
}
