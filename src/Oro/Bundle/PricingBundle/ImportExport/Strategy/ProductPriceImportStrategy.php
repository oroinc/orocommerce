<?php

namespace Oro\Bundle\PricingBundle\ImportExport\Strategy;

use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Validator\Constraints\UniqueProductPrices;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Product price add or replace import strategy.
 * Control price uniqueness
 * Ensure that price is loaded correctly
 * Load product relation
 */
class ProductPriceImportStrategy extends ConfigurableAddOrReplaceStrategy
{
    const PROCESSED_ENTITIES_HASH = 'processedEntitiesHash';

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     * @return ProductPriceImportStrategy
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;

        return $this;
    }

    /**
     * @param ProductPrice $entity
     * @return ProductPrice
     */
    protected function beforeProcessEntity($entity)
    {
        $this->refreshPrice($entity);

        $this->loadProduct($entity);

        return parent::beforeProcessEntity($entity);
    }

    /**
     * @param ProductPrice $entity
     * @return ProductPrice
     */
    protected function afterProcessEntity($entity)
    {
        $this->refreshPrice($entity);

        // Set version to track prices changed within import
        $version = $this->context->getOption('importVersion');
        if ($version) {
            $entity->setVersion($version);
        }

        $entity->setPriceRule(null);

        return parent::afterProcessEntity($entity);
    }

    protected function refreshPrice(ProductPrice $entity)
    {
        $entity->loadPrice();
    }

    protected function loadProduct(ProductPrice $entity)
    {
        if ($entity->getProduct()) {
            /** @var Product $product */
            $product = $this->findExistingEntity($entity->getProduct());
            if ($product) {
                $entity->setProduct($product);
            } else {
                $this->fieldHelper->setObjectValue($entity, 'product', null);
                $this->fieldHelper->setObjectValue($entity, 'productSku', null);
            }
        }
    }

    /**
     * @param ProductPrice $entity
     *
     * @return ProductPrice|null
     */
    protected function validateAndUpdateContext($entity): ?ProductPrice
    {
        $entity = parent::validateAndUpdateContext($entity);

        return $entity ? $this->validateEntityUniqueness($entity) : $entity;
    }

    protected function validateEntityUniqueness(ProductPrice $entity): ?ProductPrice
    {
        $processedEntities = $this->getProcessedEntities();
        $hash = $this->getEntityHashByUniqueFields($entity);

        if (!empty($processedEntities[$hash])) {
            $this->addEntityUniquenessViolation($entity);
            $entity = null;
        } else {
            $processedEntities[$hash] = true;
            $this->context->setValue(self::PROCESSED_ENTITIES_HASH, $processedEntities);
        }

        return $entity;
    }

    /**
     * @param ProductPrice $entity
     * @return string
     */
    protected function getEntityHashByUniqueFields(ProductPrice $entity)
    {
        return md5(
            implode(
                ':',
                [
                    $entity->getProduct()->getId(),
                    $entity->getPriceList()->getId(),
                    $entity->getQuantity(),
                    $entity->getUnit()->getCode(),
                    $entity->getPrice()->getCurrency()
                ]
            )
        );
    }

    protected function addEntityUniquenessViolation(ProductPrice $entity)
    {
        $uniqueConstraint = new UniqueProductPrices();

        $this->context->incrementErrorEntriesCount();
        $this->strategyHelper->addValidationErrors(
            [
                $this->translator->trans(
                    $uniqueConstraint->message,
                    [],
                    'validators'
                )
            ],
            $this->context
        );

        $identifier = $this->databaseHelper->getIdentifier($entity);
        if ($identifier) {
            $this->context->incrementReplaceCount(-1);
        } else {
            $this->context->incrementAddCount(-1);
        }
    }

    private function getProcessedEntities(): ?array
    {
        return $this->context->getValue(self::PROCESSED_ENTITIES_HASH);
    }
}
