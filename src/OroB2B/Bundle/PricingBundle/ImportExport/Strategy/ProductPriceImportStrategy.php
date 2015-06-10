<?php

namespace OroB2B\Bundle\PricingBundle\ImportExport\Strategy;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy;

use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\Validator\Constraints\UniqueProductPrices;
use OroB2B\Bundle\ProductBundle\Entity\Product;

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

        return parent::afterProcessEntity($entity);
    }

    /**
     * @param ProductPrice $entity
     */
    protected function refreshPrice(ProductPrice $entity)
    {
        $value = $this->fieldHelper->getObjectValue($entity, 'value');
        $currency = $this->fieldHelper->getObjectValue($entity, 'currency');
        if ($value && $currency) {
            $entity->loadPrice();
        } else {
            $this->fieldHelper->setObjectValue($entity, 'price', null);
        }
    }

    /**
     * @param ProductPrice $entity
     */
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
     * @return ProductPrice|null
     *
     * {@inheritdoc}
     */
    protected function validateAndUpdateContext($entity)
    {
        $validatedEntity = parent::validateAndUpdateContext($entity);

        if (null !== $validatedEntity) {
            $processedEntities = (array)$this->context->getValue(self::PROCESSED_ENTITIES_HASH);
            $hash = $this->getEntityHashByUniqueFields($entity);

            if (!empty($processedEntities[$hash])) {
                $this->addEntityUniquenessViolation($entity);

                $validatedEntity = null;
            } else {
                $processedEntities[$hash] = true;
                $this->context->setValue(self::PROCESSED_ENTITIES_HASH, $processedEntities);
            }
        }

        return $validatedEntity;
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

    /**
     * @param ProductPrice $entity
     */
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
}
