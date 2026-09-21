<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductSearchPageRequestProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProductSearchPageRequestProviderTest extends TestCase
{
    private UrlGeneratorInterface&MockObject $urlGenerator;
    private ConfigManager&MockObject $configManager;
    private LoggerInterface&MockObject $logger;
    private ManagerRegistry&MockObject $doctrine;
    private ProductSearchPageRequestProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->provider = new ProductSearchPageRequestProvider(
            $this->urlGenerator,
            $this->configManager,
            $this->logger,
            $this->doctrine
        );
    }

    private function expectProductNameQuery(?string $result, ?\Throwable $exception = null): void
    {
        $query = $this->createMock(Query::class);
        if ($exception) {
            $query->expects(self::once())
                ->method('getOneOrNullResult')
                ->with(AbstractQuery::HYDRATE_SINGLE_SCALAR)
                ->willThrowException($exception);
        } else {
            $query->expects(self::once())
                ->method('getOneOrNullResult')
                ->with(AbstractQuery::HYDRATE_SINGLE_SCALAR)
                ->willReturn($result);
        }

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::once())
            ->method('select')
            ->with('n.string')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('innerJoin')
            ->with('p.names', 'n')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('where')
            ->with('p.status = :status')
            ->willReturnSelf();
        $queryBuilder->expects(self::exactly(2))
            ->method('andWhere')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('setParameter')
            ->with('status', Product::STATUS_ENABLED)
            ->willReturnSelf();
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

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($queryBuilder);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($repository);
    }

    public function testGetRequestsReturnsEmptyArrayWhenNoProductNameFound(): void
    {
        $this->expectProductNameQuery(null);

        $this->urlGenerator->expects(self::never())
            ->method('generate');
        $this->logger->expects(self::never())
            ->method('warning');

        self::assertSame([], $this->provider->getRequests());
    }

    public function testGetRequestsReturnsEmptyArrayWhenProductNameIsBlank(): void
    {
        $this->expectProductNameQuery('   ');

        $this->urlGenerator->expects(self::never())
            ->method('generate');

        self::assertSame([], $this->provider->getRequests());
    }

    public function testGetRequestsReturnsSearchRequestUsingFullProductName(): void
    {
        $this->expectProductNameQuery('Sample Configurable Product With Color');

        $this->urlGenerator->expects(self::once())
            ->method('generate')
            ->with('oro_product_frontend_product_search', ['search' => 'Sample Configurable Product With Color'])
            ->willReturn('/product/search?search=Sample%20Configurable%20Product%20With%20Color');
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_ui.application_url')
            ->willReturn('http://example.com');

        $requests = $this->provider->getRequests();

        self::assertCount(1, $requests);
        self::assertInstanceOf(Request::class, $requests[0]);
        self::assertSame(
            'http://example.com/product/search?search=Sample%20Configurable%20Product%20With%20Color',
            $requests[0]->getUri()
        );
        self::assertSame('GET', $requests[0]->getMethod());
        self::assertSame('html', $requests[0]->getRequestFormat());
    }

    public function testGetRequestsSkipsPageAndLogsWarningWhenQueryFails(): void
    {
        $this->expectProductNameQuery(null, new \RuntimeException('Connection refused'));

        $this->logger->expects(self::once())
            ->method('warning')
            ->with(
                'Failed to find a product name for search page cache warmup: {message}',
                self::callback(static fn (array $context) => 'Connection refused' === $context['message']
                    && $context['exception'] instanceof \RuntimeException)
            );
        $this->urlGenerator->expects(self::never())
            ->method('generate');

        self::assertSame([], $this->provider->getRequests());
    }

    public function testGetRequestsReturnsEmptyArrayWhenSearchRouteCannotBeGenerated(): void
    {
        $this->expectProductNameQuery('Sample Product');

        $this->urlGenerator->expects(self::once())
            ->method('generate')
            ->willThrowException(new \Exception('Route not found'));
        $this->logger->expects(self::once())
            ->method('warning');

        self::assertSame([], $this->provider->getRequests());
    }
}
