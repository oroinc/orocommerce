<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Provider\CategoryPageRequestProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CategoryPageRequestProviderTest extends TestCase
{
    private UrlGeneratorInterface&MockObject $urlGenerator;
    private ConfigManager&MockObject $configManager;
    private LoggerInterface&MockObject $logger;
    private ManagerRegistry&MockObject $doctrine;
    private CategoryPageRequestProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->provider = new CategoryPageRequestProvider(
            $this->urlGenerator,
            $this->configManager,
            $this->logger,
            $this->doctrine
        );
    }

    private function expectCategoryQuery(?int $result, ?\Throwable $exception = null): void
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
            ->with('c.id')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('where')
            ->with('c.level > 0')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('orderBy')
            ->with('c.id', 'ASC')
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
            ->with('c')
            ->willReturn($queryBuilder);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(Category::class)
            ->willReturn($repository);
    }

    public function testGetRequestsReturnsEmptyArrayWhenNoCategoryFound(): void
    {
        $this->expectCategoryQuery(null);

        $this->urlGenerator->expects(self::never())
            ->method('generate');
        $this->logger->expects(self::never())
            ->method('warning');

        self::assertSame([], $this->provider->getRequests());
    }

    public function testGetRequestsReturnsRequestForFoundCategory(): void
    {
        $this->expectCategoryQuery(3);

        $this->urlGenerator->expects(self::once())
            ->method('generate')
            ->with('oro_product_frontend_product_index', ['categoryId' => 3, 'includeSubcategories' => true])
            ->willReturn('/lighting-products');
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_ui.application_url')
            ->willReturn('http://example.com');

        $requests = $this->provider->getRequests();

        self::assertCount(1, $requests);
        self::assertInstanceOf(Request::class, $requests[0]);
        self::assertSame('http://example.com/lighting-products', $requests[0]->getUri());
        self::assertSame('GET', $requests[0]->getMethod());
        self::assertSame('html', $requests[0]->getRequestFormat());
    }

    public function testGetRequestsSkipsPageAndLogsWarningWhenQueryFails(): void
    {
        $this->expectCategoryQuery(null, new \RuntimeException('Connection refused'));

        $this->logger->expects(self::once())
            ->method('warning')
            ->with(
                'Failed to find a category for page cache warmup: {message}',
                self::callback(static fn (array $context) => 'Connection refused' === $context['message']
                    && $context['exception'] instanceof \RuntimeException)
            );
        $this->urlGenerator->expects(self::never())
            ->method('generate');

        self::assertSame([], $this->provider->getRequests());
    }

    public function testGetRequestsReturnsEmptyArrayWhenRouteCannotBeGenerated(): void
    {
        $this->expectCategoryQuery(3);

        $this->urlGenerator->expects(self::once())
            ->method('generate')
            ->willThrowException(new \Exception('Route not found'));
        $this->logger->expects(self::once())
            ->method('warning');

        self::assertSame([], $this->provider->getRequests());
    }
}
