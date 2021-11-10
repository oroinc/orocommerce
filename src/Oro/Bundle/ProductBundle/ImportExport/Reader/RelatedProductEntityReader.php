<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Reader;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Reader\IteratorBasedReader;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\RelatedItem\RelatedProduct;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\RelatedItem\RelatedItemConfigProviderInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Reads data from the database and prepares it for export.
 */
class RelatedProductEntityReader extends IteratorBasedReader
{
    /** @var ManagerRegistry */
    private $registry;

    /** @var AclHelper */
    private $aclHelper;

    /** @var RelatedItemConfigProviderInterface */
    private $configProvider;

    public function __construct(
        ContextRegistry $contextRegistry,
        ManagerRegistry $registry,
        AclHelper $aclHelper,
        RelatedItemConfigProviderInterface $configProvider
    ) {
        parent::__construct($contextRegistry);

        $this->registry = $registry;
        $this->aclHelper = $aclHelper;
        $this->configProvider = $configProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeFromContext(ContextInterface $context): void
    {
        parent::initializeFromContext($context);

        $iterator = $this->getSourceIterator();
        if ($iterator) {
            return;
        }

        $qb = $this->getRepository(RelatedProduct::class)
            ->getUniqueProductDataQueryBuilder($this->configProvider->isBidirectional());

        $this->setSourceIterator(new BufferedQueryResultIterator($this->aclHelper->apply($qb)));
    }

    /**
     * {@inheritdoc}
     */
    public function read(): ?array
    {
        if (!$this->configProvider->isEnabled()) {
            return null;
        }

        $result = parent::read();
        if (!isset($result['id'], $result['sku'])) {
            return null;
        }

        $relatedProductIds = $this->getRepository(RelatedProduct::class)
            ->findRelatedIds($result['id'], $this->configProvider->isBidirectional());

        /** @var ProductRepository $productRepository */
        $productRepository = $this->getRepository(Product::class);
        $qb = $productRepository
            ->getProductsQueryBuilder($relatedProductIds)
            ->resetDQLPart('select')
            ->select('p.sku as relatedSku');

        $query = $this->aclHelper->apply($qb);

        return [
            'sku' => $result['sku'],
            'relatedItem' => array_unique(array_column($query->getScalarResult(), 'relatedSku')),
        ];
    }

    private function getRepository(string $className): ObjectRepository
    {
        return $this->registry->getManagerForClass($className)->getRepository($className);
    }
}
