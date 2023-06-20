<?php

namespace Oro\Bundle\ProductBundle\Form\Extension;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * The base class form type extensions that pre-fill an entity
 * with requested products taken from the product data storage.
 */
abstract class AbstractProductDataStorageExtension extends AbstractTypeExtension implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var RequestStack */
    protected $requestStack;

    /** @var ProductDataStorage */
    protected $storage;

    /** @var string */
    protected $productClass;

    /** @var string */
    protected $dataClass;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /** @var string */
    protected $extendedType;

    /** @var ProductRepository */
    protected $productRepository;

    /** @var AclHelper */
    protected $aclHelper;

    /**
     * @param RequestStack $requestStack
     * @param ProductDataStorage $storage
     * @param DoctrineHelper $doctrineHelper
     * @param AclHelper $aclHelper
     * @param string $productClass
     */
    public function __construct(
        RequestStack $requestStack,
        ProductDataStorage $storage,
        DoctrineHelper $doctrineHelper,
        AclHelper $aclHelper,
        $productClass
    ) {
        $this->requestStack = $requestStack;
        $this->storage = $storage;
        $this->doctrineHelper = $doctrineHelper;
        $this->aclHelper = $aclHelper;
        $this->productClass = $productClass;
    }

    /**
     * @param string $dataClass
     * @return $this
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($this->isStorageFull()) {
            $builder->addEventListener(
                FormEvents::PRE_SET_DATA,
                function (FormEvent $event) {
                    $entity = $event->getData();
                    if ($entity instanceof $this->dataClass
                        && !$this->doctrineHelper->getSingleEntityIdentifier($entity)
                    ) {
                        $this->fillData($entity);
                    }
                }
            );
        }
    }

    /**
     * @return bool
     */
    protected function isStorageFull()
    {
        $request = $this->requestStack->getCurrentRequest();

        return
            null !== $request
            && $request->get(ProductDataStorage::STORAGE_KEY)
            && $this->storage->get();
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        if ($this->isStorageFull()) {
            $resolver->setNormalizer('data', function (Options $options, $value) {
                if ($value instanceof $this->dataClass
                    && !$this->doctrineHelper->getSingleEntityIdentifier($value)
                ) {
                    $this->fillData($value);
                }

                return $value;
            });
        }
    }

    /**
     * @param object $entity
     */
    protected function fillData($entity)
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

    protected function fillItemsData($entity, array $itemsData = [])
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrineHelper->getEntityManagerForClass($this->dataClass);
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

    /**
     * @param object $entity
     * @param array $data
     */
    protected function fillEntityData($entity, array $data = [])
    {
        if (!$data) {
            return;
        }

        if (!$this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        $metadata = $this->doctrineHelper->getEntityMetadata($entity);
        foreach ($data as $property => $value) {
            try {
                if ($metadata->hasAssociation($property)) {
                    $associationTargetClass = $metadata->getAssociationTargetClass($property);
                    // For collections (ManyToMany, OneToMany) associations support
                    if (\is_array($value)) {
                        $elements = [];
                        foreach ($value as $val) {
                            $elements[] = $this->doctrineHelper->getEntityReference($associationTargetClass, $val);
                        }
                        $value = $elements;
                    } elseif ($value) {
                        $value = $this->doctrineHelper->getEntityReference($associationTargetClass, $value);
                    }
                }

                $this->propertyAccessor->setValue($entity, $property, $value);
            } catch (NoSuchPropertyException $e) {
                $this->logger->notice(
                    'No such property {property} in the entity {entity}',
                    ['property' => $property, 'entity' => ClassUtils::getClass($entity), 'exception' => $e]
                );
            }
        }
    }

    /**
     * @param Product $product
     * @param object $entity
     * @param array $itemData
     */
    abstract protected function addItem(Product $product, $entity, array $itemData = []);

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

    /**
     * @return ProductRepository
     */
    protected function getProductRepository()
    {
        if (!$this->productRepository) {
            $this->productRepository = $this->doctrineHelper->getEntityRepository($this->productClass);
        }

        return $this->productRepository;
    }
}
