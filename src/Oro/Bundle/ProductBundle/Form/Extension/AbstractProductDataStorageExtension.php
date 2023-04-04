<?php

namespace Oro\Bundle\ProductBundle\Form\Extension;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * The base class form type extensions that pre-fill an entity
 * with requested products taken from the product data storage.
 */
abstract class AbstractProductDataStorageExtension extends AbstractTypeExtension
{
    protected RequestStack $requestStack;
    protected ProductDataStorage $storage;
    protected PropertyAccessorInterface $propertyAccessor;
    protected DoctrineHelper $doctrineHelper;
    protected AclHelper $aclHelper;
    protected LoggerInterface $logger;
    protected ?ProductRepository $productRepository = null;

    public function __construct(
        RequestStack $requestStack,
        ProductDataStorage $storage,
        PropertyAccessorInterface $propertyAccessor,
        DoctrineHelper $doctrineHelper,
        AclHelper $aclHelper,
        LoggerInterface $logger
    ) {
        $this->requestStack = $requestStack;
        $this->storage = $storage;
        $this->propertyAccessor = $propertyAccessor;
        $this->doctrineHelper = $doctrineHelper;
        $this->aclHelper = $aclHelper;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($this->isStorageFull()) {
            $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                $entity = $event->getData();
                if (is_a($entity, $this->getEntityClass())
                    && !$this->doctrineHelper->getSingleEntityIdentifier($entity)
                ) {
                    $this->fillData($entity);
                }
            });
        }
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        if ($this->isStorageFull()) {
            $resolver->setNormalizer('data', function (Options $options, $value) {
                if (is_a($value, $this->getEntityClass())
                    && !$this->doctrineHelper->getSingleEntityIdentifier($value)
                ) {
                    $this->fillData($value);
                }

                return $value;
            });
        }
    }

    abstract protected function getEntityClass(): string;

    protected function isStorageFull(): bool
    {
        return
            $this->requestStack->getCurrentRequest()->get(ProductDataStorage::STORAGE_KEY)
            && $this->storage->get();
    }

    protected function fillData(object $entity): void
    {
        $data = $this->storage->get();
        $this->storage->remove();

        if (!$data) {
            return;
        }

        $entityData = $data[ProductDataStorage::ENTITY_DATA_KEY] ?? null;
        if (\is_array($entityData) && $entityData) {
            $this->fillEntityData($entity, $entityData);
        }

        $entityItemsData = $data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY] ?? null;
        if (\is_array($entityItemsData) && $entityItemsData) {
            $this->fillItemsData($entity, $entityItemsData);
        }
    }

    protected function fillItemsData(object $entity, array $itemsData): void
    {
        $repository = $this->getProductRepository();
        foreach ($itemsData as $dataRow) {
            if (!\array_key_exists(ProductDataStorage::PRODUCT_SKU_KEY, $dataRow)) {
                continue;
            }

            $qb = $repository->getBySkuQueryBuilder($dataRow[ProductDataStorage::PRODUCT_SKU_KEY]);
            $product = $this->aclHelper->apply($qb)->getOneOrNullResult();
            if (!$product) {
                continue;
            }

            $this->addItem($product, $entity, $dataRow);
        }
    }

    protected function fillEntityData(object $entity, array $data): void
    {
        if (!$data) {
            return;
        }

        $metadata = $this->doctrineHelper->getEntityMetadata($entity);

        foreach ($data as $property => $value) {
            try {
                if ($metadata->hasAssociation($property)) {
                    $associationTargetClass = $metadata->getAssociationTargetClass($property);
                    // For collections (ManyToMany, OneToMany) associations support
                    if (\is_array($value)) {
                        $value = array_map(
                            function ($value) use ($associationTargetClass) {
                                return $this->doctrineHelper->getEntityReference(
                                    $associationTargetClass,
                                    $value
                                );
                            },
                            $value
                        );
                    } elseif ($value) {
                        $value = $this->doctrineHelper->getEntityReference(
                            $associationTargetClass,
                            $value
                        );
                    }
                }

                $this->propertyAccessor->setValue($entity, $property, $value);
            } catch (NoSuchPropertyException $e) {
                $this->logger->notice(
                    'No such property {property} in the entity {entity}',
                    [
                        'property' => $property,
                        'entity' => ClassUtils::getClass($entity),
                        'exception' => $e,
                    ]
                );
            }
        }
    }

    abstract protected function addItem(Product $product, object $entity, array $itemData): void;

    protected function getDefaultProductUnit(Product $product): ?ProductUnit
    {
        /* @var ProductUnitPrecision $unitPrecision */
        $unitPrecision = $product->getUnitPrecisions()->first();
        if (!$unitPrecision) {
            return null;
        }

        $unit = $unitPrecision->getUnit();
        if (!$unit) {
            return null;
        }

        return $unit;
    }

    protected function getProductRepository(): ProductRepository
    {
        if (!$this->productRepository) {
            $this->productRepository = $this->doctrineHelper->getEntityRepository(Product::class);
        }

        return $this->productRepository;
    }
}
