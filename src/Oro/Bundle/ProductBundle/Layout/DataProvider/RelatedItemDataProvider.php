<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Model\ProductView;
use Oro\Bundle\ProductBundle\Provider\ProductListBlockConfigInterface;
use Oro\Bundle\ProductBundle\Provider\ProductListBuilder;
use Oro\Bundle\ProductBundle\RelatedItem\FinderStrategyInterface;
use Oro\Bundle\ProductBundle\RelatedItem\RelatedItemConfigProviderInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UIBundle\Provider\UserAgentProviderInterface;

/**
 * Provides products related to a specific product.
 */
class RelatedItemDataProvider
{
    private FinderStrategyInterface $finderStrategy;
    private RelatedItemConfigProviderInterface $configProvider;
    private UserAgentProviderInterface $userAgentProvider;
    private ManagerRegistry $doctrine;
    private ProductManager $productManager;
    private AclHelper $aclHelper;
    private ProductListBuilder $productListBuilder;
    private string $productListType;

    /** @var array [product id => [related product view, ...], ...] */
    private array $relatedItems = [];

    private ProductListBlockConfigInterface $productListBlockConfig;

    public function __construct(
        FinderStrategyInterface $finderStrategy,
        RelatedItemConfigProviderInterface $configProvider,
        UserAgentProviderInterface $userAgentProvider,
        ManagerRegistry $doctrine,
        ProductManager $productManager,
        AclHelper $aclHelper,
        ProductListBuilder $productListBuilder,
        string $productListType
    ) {
        $this->finderStrategy = $finderStrategy;
        $this->configProvider = $configProvider;
        $this->userAgentProvider = $userAgentProvider;
        $this->doctrine = $doctrine;
        $this->productManager = $productManager;
        $this->aclHelper = $aclHelper;
        $this->productListBuilder = $productListBuilder;
        $this->productListType = $productListType;
    }

    public function setProductListBlockConfig(ProductListBlockConfigInterface $productListBlockConfig): void
    {
        $this->productListBlockConfig = $productListBlockConfig;
    }

    /**
     * @param Product $product
     *
     * @return ProductView[]
     */
    public function getRelatedItems(Product $product): array
    {
        $productId = $product->getId();

        if (!isset($this->relatedItems[$productId])) {
            $this->relatedItems[$productId] = $this->loadRelatedItems($product);
        }

        return $this->relatedItems[$productId];
    }

    public function isSliderEnabled(): bool
    {
        return !$this->isMobile() || $this->isSliderEnabledOnMobile();
    }

    public function isAddButtonVisible(): bool
    {
        return $this->productListBlockConfig->isAddButtonVisible();
    }

    private function loadRelatedItems(Product $product): array
    {
        $relatedProductIds = $this->finderStrategy->findIds($product);

        if (!$this->hasMoreThanRequiredMinimum($relatedProductIds)) {
            return [];
        }

        $qb = $this->getProductRepository()
            ->getProductsQueryBuilder($relatedProductIds)
            ->select('p.id')
            ->orderBy('p.id');
        $limit = $this->productListBlockConfig->getMaximumItems();
        if ($limit) {
            $qb->setMaxResults($limit);
        }
        $this->productManager->restrictQueryBuilder($qb, []);
        $rows = $this->aclHelper->apply($qb)->getArrayResult();
        if (!$rows || !$this->hasMoreThanRequiredMinimum($rows)) {
            return [];
        }

        return $this->productListBuilder->getProductsByIds(
            $this->getProductListType(),
            array_column($rows, 'id')
        );
    }

    private function hasMoreThanRequiredMinimum(array $rows): bool
    {
        return count($rows) !== 0 && count($rows) >= (int)$this->productListBlockConfig->getMinimumItems();
    }

    private function isMobile(): bool
    {
        return $this->userAgentProvider->getUserAgent()->isMobile();
    }

    private function isSliderEnabledOnMobile(): bool
    {
        return $this->productListBlockConfig->isSliderEnabledOnMobile();
    }

    private function getProductRepository(): ProductRepository
    {
        return $this->doctrine->getRepository(Product::class);
    }

    private function getProductListType(): string
    {
        return $this->productListBlockConfig->getProductListType();
    }
}
