<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PlatformBundle\Provider\AbstractPageRequestProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Provides a request to the product search page, so the search result grid's shared layout
 * and expression caches are warmed during cache:warmup instead of on the first customer search.
 */
class ProductSearchPageRequestProvider extends AbstractPageRequestProvider
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
        $searchTerm = $this->findSearchTerm();
        if (null === $searchTerm) {
            return [];
        }

        $request = $this->createRequest('GET', 'oro_product_frontend_product_search', ['search' => $searchTerm]);

        return $request ? [$request] : [];
    }

    /**
     * Finds the default name of the first enabled product, so the search page renders with results.
     */
    private function findSearchTerm(): ?string
    {
        try {
            $name = $this->doctrine->getRepository(Product::class)
                ->createQueryBuilder('p')
                ->select('n.string')
                ->innerJoin('p.names', 'n')
                ->where('p.status = :status')
                ->andWhere('n.localization IS NULL')
                ->andWhere('n.string IS NOT NULL')
                ->setParameter('status', Product::STATUS_ENABLED)
                ->orderBy('p.id', 'ASC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR);
        } catch (\Throwable $exception) {
            // the page is skipped, cache warmup must not fail because of it
            $this->logger->warning(
                'Failed to find a product name for search page cache warmup: {message}',
                ['message' => $exception->getMessage(), 'exception' => $exception]
            );

            return null;
        }

        return trim((string)$name) ?: null;
    }
}
