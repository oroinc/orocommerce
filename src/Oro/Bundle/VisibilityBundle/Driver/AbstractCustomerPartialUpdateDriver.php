<?php

namespace Oro\Bundle\VisibilityBundle\Driver;

use Doctrine\ORM\Query;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIteratorInterface;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Placeholder\CustomerIdPlaceholder;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\VisibilityBundle\Indexer\ProductVisibilityIndexer;
use Oro\Bundle\VisibilityBundle\Visibility\Provider\ProductVisibilityProvider;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\WebsiteIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Provider\PlaceholderProvider;

/**
 * Abstract driver for the partial update of the customer visibility in the website search index
 */
abstract class AbstractCustomerPartialUpdateDriver implements CustomerPartialUpdateDriverInterface
{
    const PRODUCT_BATCH_SIZE = 1000;

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
    public function updateCustomerVisibility(Customer $customer)
    {
        $this->deleteCustomerVisibility($customer);

        $customerVisibilityFieldName = $this->getCustomerVisibilityFieldName($customer);
        foreach ($this->getAllWebsites($customer) as $website) {
            $iterator = $this->getCustomerVisibilityIterator($customer, $website);

            $productAlias = $this->getProductAliasByWebsite($website);
            $productIds = [];
            $rows = 0;
            foreach ($iterator as $productData) {
                ++$rows;
                $productIds[] = $productData['id'];

                if ($rows % static::PRODUCT_BATCH_SIZE === 0) {
                    $this->addCustomerVisibility(
                        $productIds,
                        $productAlias,
                        $customerVisibilityFieldName
                    );
                    $productIds = [];
                }
            }

            if ($productIds) {
                $this->addCustomerVisibility(
                    $productIds,
                    $productAlias,
                    $customerVisibilityFieldName
                );
            }
        }
    }

    /**
     * Adds $customerVisibilityFieldName field for products in $productAlias which
     * ids are in $productsIds.
     *
     * @param array $productIds
     * @param string $productAlias
     * @param string $customerVisibilityFieldName
     */
    abstract protected function addCustomerVisibility(
        array $productIds,
        $productAlias,
        $customerVisibilityFieldName
    );

    /**
     * @param Customer $customer
     *
     * @return Website[]
     */
    protected function getAllWebsites(Customer $customer)
    {
        /** @var WebsiteRepository $websiteRepository */
        $websiteRepository = $this->doctrineHelper->getEntityRepository(Website::class);

        return $websiteRepository->getAllWebsites($customer->getOrganization());
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
     * @param Customer $customer
     * @return string
     */
    protected function getCustomerVisibilityFieldName(Customer $customer)
    {
        return $this->placeholderProvider->getPlaceholderFieldName(
            Product::class,
            ProductVisibilityIndexer::FIELD_VISIBILITY_ACCOUNT,
            [
                CustomerIdPlaceholder::NAME => $customer->getId(),
            ]
        );
    }

    /**
     * @param Customer $customer
     * @param Website $website
     * @param int $batchSize
     * @return BufferedQueryResultIteratorInterface
     */
    protected function getCustomerVisibilityIterator(
        Customer $customer,
        Website $website,
        $batchSize = self::PRODUCT_BATCH_SIZE
    ) {
        $queryBuilder = $this
            ->productVisibilityProvider
            ->getCustomerProductsVisibilitiesByWebsiteQueryBuilder(
                $customer,
                $website
            );

        $queryBuilder->select('product.id');

        $iterator = new BufferedIdentityQueryResultIterator($queryBuilder);
        $iterator->setHydrationMode(Query::HYDRATE_ARRAY);
        $iterator->setBufferSize($batchSize);

        return $iterator;
    }
}
