<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\RFPBundle\Provider\ProductAvailabilityProvider;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\Testing\Unit\EntityTrait;

class ProductAvailabilityProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @return array
     */
    public function isProductApplicableForRFPDataProvider()
    {
        return [
            'simple product' => [
                'type' => Product::TYPE_SIMPLE,
                'expected' => true,
            ],
            'configurable product' => [
                'type' => Product::TYPE_CONFIGURABLE,
                'expected' => false,
            ],
        ];
    }

    /**
     * @param string $type
     * @param bool $expected
     * @dataProvider isProductApplicableForRFPDataProvider
     */
    public function testIsProductApplicableForRFP($type, $expected)
    {
        $provider = new ProductAvailabilityProvider();
        $product = $this->getEntity(Product::class, ['id' => 1, 'type' => $type]);

        $this->assertEquals($expected, $provider->isProductApplicableForRFP($product));
    }

    public function testGetAllowedProductIds()
    {
        $productIds = [10, 20, 30];

        $productManager = $this->createMock(ProductManager::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $aclHelper = $this->createMock(AclHelper::class);

        $provider = new ProductAvailabilityProvider();
        $provider->setDoctrine($doctrine);
        $provider->setProductManager($productManager);
        $provider->setAclHelper($aclHelper);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('select')
            ->with('p.id')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('p.type != :notAllowedProductType')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('notAllowedProductType', Product::TYPE_CONFIGURABLE)
            ->willReturnSelf();

        $repository = $this->createMock(ProductRepository::class);
        $repository->expects($this->once())
            ->method('getProductsQueryBuilder')
            ->with($productIds)
            ->willReturn($queryBuilder);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($repository);

        $doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(Product::class)
            ->willReturn($entityManager);

        $productManager->expects($this->once())
            ->method('restrictQueryBuilder')
            ->with($queryBuilder, ['scope' => 'rfp']);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getArrayResult')
            ->willReturn([
                ['id' => 10],
                ['id' => 30],
            ]);

        $aclHelper->expects($this->once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $this->assertSame(
            [10, 30],
            $provider->getAllowedProductIds($productIds)
        );
    }
}
