<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductPageRequestProvider;
use Oro\Bundle\ProductBundle\Provider\ProductTypeProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProductPageRequestProviderTest extends TestCase
{
    private UrlGeneratorInterface&MockObject $urlGenerator;
    private ConfigManager&MockObject $configManager;
    private LoggerInterface&MockObject $logger;
    private EntityRepository&MockObject $repository;
    private ProductTypeProvider&MockObject $productTypeProvider;
    private ProductPageRequestProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->repository = $this->createMock(EntityRepository::class);
        $this->productTypeProvider = $this->createMock(ProductTypeProvider::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($this->repository);

        $this->provider = new ProductPageRequestProvider(
            $this->urlGenerator,
            $this->configManager,
            $this->logger,
            $doctrine,
            $this->productTypeProvider
        );
    }

    /**
     * @param int|\Throwable|null $result a product id, null for no product, or an exception to throw
     */
    private function createQueryBuilder(string $expectedType, int|\Throwable|null $result): QueryBuilder&MockObject
    {
        $query = $this->createMock(Query::class);
        if ($result instanceof \Throwable) {
            $query->expects(self::once())
                ->method('getOneOrNullResult')
                ->with(AbstractQuery::HYDRATE_SINGLE_SCALAR)
                ->willThrowException($result);
        } else {
            $query->expects(self::once())
                ->method('getOneOrNullResult')
                ->with(AbstractQuery::HYDRATE_SINGLE_SCALAR)
                ->willReturn($result);
        }

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::once())
            ->method('select')
            ->with('p.id')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('where')
            ->with('p.type = :type')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('andWhere')
            ->with('p.status = :status')
            ->willReturnSelf();
        $queryBuilder->expects(self::exactly(2))
            ->method('setParameter')
            ->willReturnCallback(function (string $name, mixed $value) use ($queryBuilder, $expectedType) {
                self::assertSame(
                    'type' === $name ? $expectedType : Product::STATUS_ENABLED,
                    $value,
                    sprintf('Unexpected value of the "%s" parameter', $name)
                );

                return $queryBuilder;
            });
        $queryBuilder->expects(self::once())
            ->method('orderBy')
            ->with('p.id', 'ASC')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('setMaxResults')
            ->with(1)
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        return $queryBuilder;
    }

    public function testGetRequestsReturnsEmptyArrayWhenNoProductTypesAvailable(): void
    {
        $this->productTypeProvider->expects(self::once())
            ->method('getAvailableProductTypes')
            ->willReturn([]);

        $this->repository->expects(self::never())
            ->method('createQueryBuilder');
        $this->urlGenerator->expects(self::never())
            ->method('generate');

        self::assertSame([], $this->provider->getRequests());
    }

    public function testGetRequestsReturnsOneRequestPerProductType(): void
    {
        $this->productTypeProvider->expects(self::once())
            ->method('getAvailableProductTypes')
            ->willReturn([
                'oro.product.type.simple' => Product::TYPE_SIMPLE,
                'oro.product.type.kit' => Product::TYPE_KIT,
            ]);

        $this->repository->expects(self::exactly(2))
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturnOnConsecutiveCalls(
                $this->createQueryBuilder(Product::TYPE_SIMPLE, 1),
                $this->createQueryBuilder(Product::TYPE_KIT, 64)
            );

        $this->urlGenerator->expects(self::exactly(2))
            ->method('generate')
            ->willReturnCallback(static function (string $route, array $parameters) {
                self::assertSame('oro_product_frontend_product_view', $route);

                return '/product/view/' . $parameters['id'];
            });
        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->with('oro_ui.application_url')
            ->willReturn('http://example.com');

        $requests = $this->provider->getRequests();

        self::assertCount(2, $requests);
        self::assertInstanceOf(Request::class, $requests[0]);
        self::assertSame('http://example.com/product/view/1', $requests[0]->getUri());
        self::assertSame('GET', $requests[0]->getMethod());
        self::assertSame('html', $requests[0]->getRequestFormat());
        self::assertSame('http://example.com/product/view/64', $requests[1]->getUri());
    }

    public function testGetRequestsSkipsProductTypesWithoutEnabledProduct(): void
    {
        $this->productTypeProvider->expects(self::once())
            ->method('getAvailableProductTypes')
            ->willReturn([
                'oro.product.type.simple' => Product::TYPE_SIMPLE,
                'oro.product.type.configurable' => Product::TYPE_CONFIGURABLE,
            ]);

        $this->repository->expects(self::exactly(2))
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturnOnConsecutiveCalls(
                $this->createQueryBuilder(Product::TYPE_SIMPLE, 1),
                $this->createQueryBuilder(Product::TYPE_CONFIGURABLE, null)
            );

        $this->urlGenerator->expects(self::once())
            ->method('generate')
            ->with('oro_product_frontend_product_view', ['id' => 1])
            ->willReturn('/product/view/1');
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_ui.application_url')
            ->willReturn('http://example.com');
        $this->logger->expects(self::never())
            ->method('warning');

        $requests = $this->provider->getRequests();

        self::assertCount(1, $requests);
        self::assertSame('http://example.com/product/view/1', $requests[0]->getUri());
    }

    public function testGetRequestsSkipsProductTypeAndLogsWarningWhenQueryFails(): void
    {
        $this->productTypeProvider->expects(self::once())
            ->method('getAvailableProductTypes')
            ->willReturn([
                'oro.product.type.simple' => Product::TYPE_SIMPLE,
                'oro.product.type.kit' => Product::TYPE_KIT,
            ]);

        $this->repository->expects(self::exactly(2))
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturnOnConsecutiveCalls(
                $this->createQueryBuilder(Product::TYPE_SIMPLE, new \RuntimeException('Connection refused')),
                $this->createQueryBuilder(Product::TYPE_KIT, 64)
            );

        $this->logger->expects(self::once())
            ->method('warning')
            ->with(
                'Failed to find a product of type "{type}" for page cache warmup: {message}',
                self::callback(static fn (array $context) => Product::TYPE_SIMPLE === $context['type']
                    && 'Connection refused' === $context['message']
                    && $context['exception'] instanceof \RuntimeException)
            );

        $this->urlGenerator->expects(self::once())
            ->method('generate')
            ->with('oro_product_frontend_product_view', ['id' => 64])
            ->willReturn('/product/view/64');
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_ui.application_url')
            ->willReturn('http://example.com');

        $requests = $this->provider->getRequests();

        self::assertCount(1, $requests);
        self::assertSame('http://example.com/product/view/64', $requests[0]->getUri());
    }
}
