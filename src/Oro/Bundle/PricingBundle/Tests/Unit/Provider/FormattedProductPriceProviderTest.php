<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Formatter\ProductPriceFormatter;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaRequestHandler;
use Oro\Bundle\PricingBundle\Provider\FormattedProductPriceProvider;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Provider\FrontendProductUnitsProvider;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FormattedProductPriceProviderTest extends TestCase
{
    private ManagerRegistry|MockObject $doctrine;
    private AclHelper|MockObject $aclHelper;
    private ProductPriceProviderInterface|MockObject $productPriceProvider;
    private ProductPriceFormatter|MockObject $productPriceFormatter;
    private ProductPriceScopeCriteriaRequestHandler|MockObject $scopeCriteriaRequestHandler;
    private UserCurrencyManager|MockObject $userCurrencyManager;
    private FrontendProductUnitsProvider|MockObject $frontendProductUnitsProvider;
    private FormattedProductPriceProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->productPriceProvider = $this->createMock(ProductPriceProviderInterface::class);
        $this->productPriceFormatter = $this->createMock(ProductPriceFormatter::class);
        $this->scopeCriteriaRequestHandler = $this->createMock(ProductPriceScopeCriteriaRequestHandler::class);
        $this->userCurrencyManager = $this->createMock(UserCurrencyManager::class);
        $this->frontendProductUnitsProvider = $this->createMock(FrontendProductUnitsProvider::class);

        $this->provider = new FormattedProductPriceProvider(
            $this->doctrine,
            $this->aclHelper,
            $this->productPriceProvider,
            $this->productPriceFormatter,
            $this->scopeCriteriaRequestHandler,
            $this->userCurrencyManager,
            $this->frontendProductUnitsProvider
        );

        $scopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $this->scopeCriteriaRequestHandler->method('getPriceScopeCriteria')->willReturn($scopeCriteria);
        $this->userCurrencyManager->method('getUserCurrency')->willReturn('USD');
        $this->productPriceProvider->method('getPricesByScopeCriteriaAndProducts')->willReturn([]);
    }

    public function testGetFormattedProductPricesReturnsEmptyWhenNoProductsFound(): void
    {
        $this->mockDoctrineQueryReturning([]);

        self::assertSame([], $this->provider->getFormattedProductPrices([9999]));
    }

    public function testGetFormattedProductPricesIncludesUnitsFromProvider(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn(1);

        $expectedUnits = ['liter' => 3];
        $this->frontendProductUnitsProvider->expects(self::once())
            ->method('getUnitsForProduct')
            ->with($product)
            ->willReturn($expectedUnits);

        $this->mockDoctrineQueryReturning([$product]);

        $result = $this->provider->getFormattedProductPrices([1]);

        self::assertSame($expectedUnits, $result[1]['units']);
    }

    private function mockDoctrineQueryReturning(array $products): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('orderBy')->willReturnSelf();

        $repository = $this->createMock(ProductRepository::class);
        $repository->method('getProductsQueryBuilder')->willReturn($queryBuilder);

        $this->doctrine->method('getRepository')->with(Product::class)->willReturn($repository);

        $query = $this->createMock(AbstractQuery::class);
        $query->method('getResult')->willReturn($products);

        $this->aclHelper->method('apply')->with($queryBuilder)->willReturn($query);
    }
}
