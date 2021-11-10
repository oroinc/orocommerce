<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Processor;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\ImportExportBundle\Processor\ImportProcessor;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\RelatedItem\RelatedProduct;
use Oro\Bundle\ProductBundle\RelatedItem\RelatedItemConfigProviderInterface;
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

    /** @var RelatedItemConfigProviderInterface */
    private $configProvider;

    /** @var ImportStrategyHelper */
    private $importStrategyHelper;

    /** @var AclHelper */
    private $aclHelper;

    public function __construct(
        ManagerRegistry $registry,
        TranslatorInterface $translator,
        RelatedItemConfigProviderInterface $configProvider,
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
        if (!$this->canBeProcessed($item)) {
            return null;
        }

        $sku = $item['SKU'];
        $productId = $this->getProductId($sku);
        if (!$productId) {
            return null;
        }

        $relatedSkus = array_unique(explode(',', $item['Related SKUs'] ?? ''));

        if (!$this->isValidRow($sku, $relatedSkus, $item)) {
            return null;
        }

        $processed = [];
        foreach ($relatedSkus as $relatedSku) {
            $object = parent::process(['SKU' => $sku, 'Related SKUs' => $relatedSku]);

            if ($object instanceof RelatedProduct && $object->getProduct() && $object->getRelatedItem()) {
                $processed[] = $object;
            }
        }

        return $this->validateRelations($productId, $processed) ? $processed : [];
    }

    private function isValidRow(string $sku, array $relatedSkus, array $item): bool
    {
        $result = true;

        if (in_array(mb_strtoupper($sku), array_map('mb_strtoupper', $relatedSkus), false)) {
            $this->addError('oro.product.import.related_sku.self_relation');
            $result = false;
        }

        if (in_array('', $relatedSkus, true)) {
            $this->addError('oro.product.import.related_sku.empty_sku', ['%data%' => json_encode($item)]);
            $result = false;
        }

        return $result;
    }

    private function canBeProcessed(array $item): bool
    {
        $result = true;

        if (!$this->configProvider->isEnabled()) {
            $result = false;
        }

        if (!isset($item['SKU'])) {
            $this->addError('oro.product.import.sku.column_missing');
            $result = false;
        }

        if (!isset($item['Related SKUs'])) {
            $this->addError('oro.product.import.related_sku.column_missing');
            $result = false;
        }

        return $result;
    }

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

    private function validateRelations(int $productId, array $processed): bool
    {
        $relatedProductIds = $this->getRepository(RelatedProduct::class)
            ->findRelatedIds($productId, $this->configProvider->isBidirectional());

        $numberOfRelations = count($relatedProductIds) + count($processed);
        if ($numberOfRelations > $this->configProvider->getLimit()) {
            $this->addError('oro.product.import.related_sku.max_relations');

            return false;
        }

        $this->context->incrementAddCount(count($processed));

        return true;
    }

    private function getRepository(string $className): ObjectRepository
    {
        return $this->registry->getManagerForClass($className)->getRepository($className);
    }

    private function addError(string $error, array $parameters = []): void
    {
        $this->context->incrementErrorEntriesCount();

        $this->importStrategyHelper->addValidationErrors(
            [$this->translator->trans($error, $parameters, 'validators')],
            $this->context
        );
    }
}
