<?php

namespace Oro\Bundle\ProductBundle\Form\Extension;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
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
    protected ManagerRegistry $doctrine;
    protected LoggerInterface $logger;

    public function __construct(
        RequestStack $requestStack,
        ProductDataStorage $storage,
        PropertyAccessorInterface $propertyAccessor,
        ManagerRegistry $doctrine,
        LoggerInterface $logger
    ) {
        $this->requestStack = $requestStack;
        $this->storage = $storage;
        $this->propertyAccessor = $propertyAccessor;
        $this->doctrine = $doctrine;
        $this->logger = $logger;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($this->isStorageFull()) {
            $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                $entity = $event->getData();
                if (is_a($entity, $this->getEntityClass()) && $this->isNewEntity($entity, $this->getEntityClass())) {
                    $this->fillData($entity);
                }
            });
        }
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        if ($this->isStorageFull()) {
            $resolver->setNormalizer('data', function (Options $options, $value) {
                if (is_a($value, $this->getEntityClass()) && $this->isNewEntity($value, $this->getEntityClass())) {
                    $this->fillData($value);
                }

                return $value;
            });
        }
    }

    abstract protected function getEntityClass(): string;

    protected function isStorageFull(): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        return
            null !== $request
            && $request->get(ProductDataStorage::STORAGE_KEY)
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
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass($this->getEntityClass());
        foreach ($itemsData as $dataRow) {
            $productId = $dataRow[ProductDataStorage::PRODUCT_ID_KEY] ?? null;
            if (null === $productId) {
                continue;
            }
            $product = $em->find(Product::class, $productId);
            if (null === $product) {
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

        $entityClass = ClassUtils::getClass($entity);
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass($this->getEntityClass());
        $metadata = $em->getClassMetadata($entityClass);
        foreach ($data as $property => $value) {
            try {
                if ($metadata->hasAssociation($property)) {
                    $associationTargetClass = $metadata->getAssociationTargetClass($property);
                    // For collections (ManyToMany, OneToMany) associations support
                    if (\is_array($value)) {
                        $elements = [];
                        foreach ($value as $val) {
                            $elements[] = $em->getReference($associationTargetClass, $val);
                        }
                        $value = $elements;
                    } elseif ($value) {
                        $value = $em->getReference($associationTargetClass, $value);
                    }
                }

                $this->propertyAccessor->setValue($entity, $property, $value);
            } catch (NoSuchPropertyException $e) {
                $this->logger->notice(
                    'No such property {property} in the entity {entity}',
                    ['property' => $property, 'entity' => $entityClass, 'exception' => $e]
                );
            }
        }
    }

    abstract protected function addItem(Product $product, object $entity, array $itemData): void;

    protected function getDefaultProductUnit(Product $product): ?ProductUnit
    {
        /** @var ProductUnitPrecision $unitPrecision */
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

    private function isNewEntity(object $entity, string $entityClass): bool
    {
        $identifierValues = $this->doctrine->getManagerForClass($entityClass)
            ->getClassMetadata($entityClass)
            ->getIdentifierValues($entity);

        return \count($identifierValues) === 0;
    }
}
