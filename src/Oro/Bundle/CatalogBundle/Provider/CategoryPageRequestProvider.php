<?php

namespace Oro\Bundle\CatalogBundle\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PlatformBundle\Provider\AbstractPageRequestProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Provides a request to one representative category listing page, so the layout, expression
 * and property accessor caches shared by all category pages are warmed during cache:warmup
 * instead of on the first storefront visit to any category.
 */
class CategoryPageRequestProvider extends AbstractPageRequestProvider
{
    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        ConfigManager $configManager,
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine
    ) {
        parent::__construct($urlGenerator, $configManager, $logger);
    }

    #[\Override]
    public function getRequests(): array
    {
        $categoryId = $this->findCategoryId();
        if (null === $categoryId) {
            return [];
        }

        $request = $this->createRequest('GET', 'oro_product_frontend_product_index', [
            'categoryId' => $categoryId,
            'includeSubcategories' => true,
        ]);

        return $request ? [$request] : [];
    }

    private function findCategoryId(): ?int
    {
        try {
            $categoryId = $this->doctrine->getRepository(Category::class)
                ->createQueryBuilder('c')
                ->select('c.id')
                ->where('c.level > 0')
                ->orderBy('c.id', 'ASC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR);
        } catch (\Throwable $exception) {
            // the page is skipped, cache warmup must not fail because of it
            $this->logger->warning(
                'Failed to find a category for page cache warmup: {message}',
                ['message' => $exception->getMessage(), 'exception' => $exception]
            );

            return null;
        }

        return null !== $categoryId ? (int)$categoryId : null;
    }
}
