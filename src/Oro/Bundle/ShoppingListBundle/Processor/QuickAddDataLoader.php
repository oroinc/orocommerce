<?php

namespace Oro\Bundle\ShoppingListBundle\Processor;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\Mapping\ProductMapperDataLoaderInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Loads information about products for the service that maps a product identifier for each data item
 * that is received during submitting of Quick Add Form.
 */
class QuickAddDataLoader implements ProductMapperDataLoaderInterface
{
    private ManagerRegistry $doctrine;
    private ProductManager $productManager;
    private AclHelper $aclHelper;

    public function __construct(ManagerRegistry $doctrine, ProductManager $productManager, AclHelper $aclHelper)
    {
        $this->doctrine = $doctrine;
        $this->productManager = $productManager;
        $this->aclHelper = $aclHelper;
    }

    /**
     *
     * @param string[] $skusUppercase
     *
     * @return array [['id' => product id, 'sku' => product sku, 'orgId' => product organization id], ...]
     */
    #[\Override]
    public function loadProducts(array $skusUppercase): array
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(Product::class);
        $qb = $em->createQueryBuilder()
            ->select('product.id, product.sku, IDENTITY(product.organization) AS orgId')
            ->from(Product::class, 'product')
            ->where('product.skuUppercase IN (:product_skus)')
            ->setParameter('product_skus', $skusUppercase)
            ->orderBy('product.organization, product.id');
        $qb = $this->productManager->restrictQueryBuilder($qb, []);

        return $this->aclHelper->apply($qb)->getArrayResult();
    }
}
