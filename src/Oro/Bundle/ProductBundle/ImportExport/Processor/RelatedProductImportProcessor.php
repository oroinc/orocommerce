<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Processor;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\ImportExportBundle\Processor\ImportProcessor;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\RelatedItem\RelatedProduct;
use Oro\Bundle\ProductBundle\RelatedItem\AbstractRelatedItemConfigProvider;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Processor for the RelatedProduct entity. Validates that count of product relations does not exceed the limit.
 */
class RelatedProductImportProcessor extends ImportProcessor
{
    /** @var ManagerRegistry */
    private $registry;

    /** @var TranslatorInterface */
    private $translator;

    /** @var AbstractRelatedItemConfigProvider */
    private $configProvider;

    /** @var ImportStrategyHelper */
    private $importStrategyHelper;

    /** @var AclHelper */
    private $aclHelper;

    /**
     * @param ManagerRegistry $registry
     * @param TranslatorInterface $translator
     * @param AbstractRelatedItemConfigProvider $configProvider
     * @param ImportStrategyHelper $importStrategyHelper
     * @param AclHelper $aclHelper
     */
    public function __construct(
        ManagerRegistry $registry,
        TranslatorInterface $translator,
        AbstractRelatedItemConfigProvider $configProvider,
        ImportStrategyHelper $importStrategyHelper,
        AclHelper $aclHelper
    ) {
        $this->registry = $registry;
        $this->translator = $translator;
        $this->configProvider = $configProvider;
        $this->importStrategyHelper = $importStrategyHelper;
        $this->aclHelper = $aclHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process($item)
    {
        if (!$this->configProvider->isEnabled()) {
            return null;
        }

        $sku = $item['SKU'];
        $productId = $this->getProductId($sku);
        if (!$productId) {
            return null;
        }

        $relatedSkus = array_unique(explode(',', $item['Related SKUs'] ?? ''));

        $processed = [];
        foreach ($relatedSkus as $relatedSku) {
            if (mb_strtoupper($sku) === mb_strtoupper($relatedSku)) {
                $this->addError('It is not possible to create relations from product to itself.');

                continue;
            }

            $object = parent::process(['SKU' => $sku, 'Related SKUs' => $relatedSku]);

            if ($object instanceof RelatedProduct && $object->getProduct() && $object->getRelatedItem()) {
                $processed[] = $object;
            }
        }

        return $this->validateRelations($productId, $processed) ? $processed : [];
    }

    /**
     * @param string $sku
     * @return int|null
     */
    private function getProductId(string $sku): ?int
    {
        $qb = $this->getRepository(Product::class)->getProductIdBySkuQueryBuilder($sku);
        $product = $this->aclHelper->apply($qb)
            ->getOneOrNullResult();

        if (!isset($product['id'])) {
            $this->addError('oro.product.product_by_sku.not_found');

            return null;
        }

        return $product['id'];
    }

    /**
     * @param int $productId
     * @param array $processed
     * @return bool
     */
    private function validateRelations(int $productId, array $processed): bool
    {
        $relatedProductIds = $this->getRepository(RelatedProduct::class)
            ->findRelatedIds($productId, $this->configProvider->isBidirectional());

        $numberOfRelations = count($relatedProductIds) + count($processed);
        if ($numberOfRelations > $this->configProvider->getLimit()) {
            $this->addError('It is not possible to add more items, because of the limit of relations.');

            return false;
        }

        $this->context->incrementAddCount(count($processed));

        return true;
    }

    /**
     * @param string $className
     * @return ObjectRepository
     */
    private function getRepository(string $className): ObjectRepository
    {
        return $this->registry->getManagerForClass($className)->getRepository($className);
    }

    /**
     * @param string $error
     * @throws \InvalidArgumentException
     */
    private function addError(string $error): void
    {
        $this->context->incrementErrorEntriesCount();

        $this->importStrategyHelper->addValidationErrors(
            [$this->translator->trans($error, [], 'validators')],
            $this->context
        );
    }
}
