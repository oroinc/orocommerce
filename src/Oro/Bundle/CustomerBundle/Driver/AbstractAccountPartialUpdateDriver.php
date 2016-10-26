<?php

namespace Oro\Bundle\CustomerBundle\Driver;

use Doctrine\ORM\Query;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Indexer\ProductVisibilityIndexer;
use Oro\Bundle\CustomerBundle\Visibility\Provider\ProductVisibilityProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\AccountIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\WebsiteIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Provider\PlaceholderProvider;

abstract class AbstractAccountPartialUpdateDriver implements AccountPartialUpdateDriverInterface
{
    const PRODUCT_BATCH_SIZE = 100000;

    /**
     * @var PlaceholderProvider
     */
    private $placeholderProvider;

    /**
     * @var ProductVisibilityProvider
     */
    private $productVisibilityProvider;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param PlaceholderProvider $placeholderProvider
     * @param ProductVisibilityProvider $productVisibilityProvider
     */
    public function __construct(
        PlaceholderProvider $placeholderProvider,
        ProductVisibilityProvider $productVisibilityProvider,
        DoctrineHelper $doctrineHelper
    ) {
        $this->placeholderProvider = $placeholderProvider;
        $this->productVisibilityProvider = $productVisibilityProvider;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function updateAccountVisibility(Account $account)
    {
        $this->deleteAccountVisibility($account);

        $accountVisibilityFieldName = $this->getAccountVisibilityFieldName($account);
        foreach ($this->getAllWebsites() as $website) {
            $iterator = $this->getAccountVisibilityIterator($account, $website);

            $productAlias = $this->getProductAliasByWebsite($website);
            $productIds = [];
            $rows = 0;
            foreach ($iterator as $productData) {
                ++$rows;
                $productIds[] = $productData['id'];

                if ($rows % static::PRODUCT_BATCH_SIZE === 0) {
                    $this->addAccountVisibility(
                        $productIds,
                        $productAlias,
                        $accountVisibilityFieldName,
                        ProductVisibilityIndexer::ACCOUNT_VISIBILITY_VALUE
                    );
                    $productIds = [];
                }
            }

            if ($productIds) {
                $this->addAccountVisibility(
                    $productIds,
                    $productAlias,
                    $accountVisibilityFieldName,
                    ProductVisibilityIndexer::ACCOUNT_VISIBILITY_VALUE
                );
            }
        }
    }

    /**
     * Adds $accountVisibilityFieldName field with $fieldValue value for products in $productAlias which
     * ids are in $productsIds.
     *
     * @param array $productIds
     * @param string $productAlias
     * @param string $accountVisibilityFieldName
     * @param string $fieldValue
     */
    abstract protected function addAccountVisibility(
        array $productIds,
        $productAlias,
        $accountVisibilityFieldName,
        $fieldValue
    );

    /**
     * @return Website[]
     */
    protected function getAllWebsites()
    {
        /** @var WebsiteRepository $websiteRepository */
        $websiteRepository = $this->doctrineHelper->getEntityRepository(Website::class);
        return $websiteRepository->getAllWebsites();
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

    /**
     * @param Account $account
     * @param Website $website
     * @param int $batchSize
     * @return BufferedQueryResultIterator
     */
    protected function getAccountVisibilityIterator(
        Account $account,
        Website $website,
        $batchSize = self::PRODUCT_BATCH_SIZE
    ) {
        $queryBuilder = $this
            ->productVisibilityProvider
            ->getAccountProductsVisibilitiesByWebsiteQueryBuilder(
                $account,
                $website
            );

        $queryBuilder->select('product.id');

        $iterator = new BufferedQueryResultIterator($queryBuilder);
        $iterator->setHydrationMode(Query::HYDRATE_ARRAY);
        $iterator->setBufferSize($batchSize);

        return $iterator;
    }
}
