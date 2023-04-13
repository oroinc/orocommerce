<?php

namespace Oro\Bundle\RFPBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Provides a set of methods to check whether products can be added to RFP.
 */
class ProductAvailabilityProvider
{
    private ConfigManager $configManager;
    private ManagerRegistry $doctrine;
    protected AclHelper $aclHelper;
    protected ?ProductRepository $productRepository = null;
    private ?array $allowedInventoryStatuses = null;
    private array $notAllowedProductTypes = [];

    public function __construct(ConfigManager $configManager, ManagerRegistry $doctrine, AclHelper $aclHelper)
    {
        $this->configManager = $configManager;
        $this->doctrine = $doctrine;
        $this->aclHelper = $aclHelper;
    }

    public function setNotAllowedProductTypes(array $notAllowedProductTypes): void
    {
        $this->notAllowedProductTypes = $notAllowedProductTypes;
    }

    public function isProductAllowedForRFP(Product $product): bool
    {
        if (!$product->isEnabled()) {
            return false;
        }

        if (\in_array($product->getType(), $this->notAllowedProductTypes, true)) {
            return false;
        }

        $inventoryStatus = $product->getInventoryStatus()?->getId();
        if (!$inventoryStatus) {
            return false;
        }

        return \in_array($inventoryStatus, $this->getAllowedInventoryStatuses(), true);
    }

    public function hasProductsAllowedForRFPByProductData(array $products): bool
    {
        $repository = $this->getProductRepository();
        foreach ($products as $product) {
            if (!empty($product['productSku'])) {
                $qb = $repository->getBySkuQueryBuilder($product['productSku']);
                $product = $this->aclHelper->apply($qb)->getOneOrNullResult();
                if (null !== $product && $this->isProductAllowedForRFP($product)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function hasProductsAllowedForRFP(array $productsIds): bool
    {
        $repository = $this->getProductRepository();
        foreach ($productsIds as $productId) {
            $qb = $repository->getProductsQueryBuilder([$productId]);
            $product = $this->aclHelper->apply($qb)->getOneOrNullResult();
            if (null !== $product && $this->isProductAllowedForRFP($product)) {
                return true;
            }
        }

        return false;
    }

    private function getProductRepository(): ProductRepository
    {
        if (!$this->productRepository) {
            $this->productRepository = $this->doctrine->getRepository(Product::class);
        }

        return $this->productRepository;
    }

    private function getAllowedInventoryStatuses(): array
    {
        if (null === $this->allowedInventoryStatuses) {
            $this->allowedInventoryStatuses = (array)$this->configManager->get('oro_rfp.frontend_product_visibility');
        }

        return $this->allowedInventoryStatuses;
    }
}
