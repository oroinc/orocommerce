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
 * Provides a request to one representative product view page per available product type
 * (simple, configurable, kit), since each type renders a different set of layout blocks
 * and would otherwise build its shared caches on the first storefront visit of that type.
 */
class ProductPageRequestProvider extends AbstractPageRequestProvider
{
    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        ConfigManager $configManager,
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine,
        private ProductTypeProvider $productTypeProvider
    ) {
        parent::__construct($urlGenerator, $configManager, $logger);
    }

    #[\Override]
    public function getRequests(): array
    {
        $requests = [];
        foreach ($this->productTypeProvider->getAvailableProductTypes() as $type) {
            $productId = $this->findProductId($type);
            if (null === $productId) {
                continue;
            }

            $request = $this->createRequest('GET', 'oro_product_frontend_product_view', ['id' => $productId]);
            if ($request) {
                $requests[] = $request;
            }
        }

        return $requests;
    }

    private function findProductId(string $type): ?int
    {
        try {
            $productId = $this->doctrine->getRepository(Product::class)
                ->createQueryBuilder('p')
                ->select('p.id')
                ->where('p.type = :type')
                ->andWhere('p.status = :status')
                ->setParameter('type', $type)
                ->setParameter('status', Product::STATUS_ENABLED)
                ->orderBy('p.id', 'ASC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR);
        } catch (\Throwable $exception) {
            // the page is skipped, cache warmup must not fail because of it
            $this->logger->warning(
                'Failed to find a product of type "{type}" for page cache warmup: {message}',
                ['type' => $type, 'message' => $exception->getMessage(), 'exception' => $exception]
            );

            return null;
        }

        return null !== $productId ? (int)$productId : null;
    }
}
