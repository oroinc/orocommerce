<?php

namespace Oro\Bundle\ProductBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;

abstract class AbstractProductDataStorageExtension extends AbstractTypeExtension
{
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

    /**
     * @param RequestStack $requestStack
     * @param ProductDataStorage $storage
     * @param DoctrineHelper $doctrineHelper
     * @param string $productClass
     */
    public function __construct(
        RequestStack $requestStack,
        ProductDataStorage $storage,
        DoctrineHelper $doctrineHelper,
        $productClass
    ) {
        $this->requestStack = $requestStack;
        $this->storage = $storage;
        $this->doctrineHelper = $doctrineHelper;
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
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($this->requestStack->getCurrentRequest()->get(ProductDataStorage::STORAGE_KEY)) {
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
     * @param object $entity
     */
    protected function fillData($entity)
    {
        $data = $this->storage->get();
        $this->storage->remove();

        if (!$data) {
            return;
        }

        if (!empty($data[ProductDataStorage::ENTITY_DATA_KEY]) &&
            is_array($data[ProductDataStorage::ENTITY_DATA_KEY])
        ) {
            $this->fillEntityData($entity, $data[ProductDataStorage::ENTITY_DATA_KEY]);
        }

        if (!empty($data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY]) &&
            is_array($data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY])
        ) {
            $itemsData = $data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY];
            $this->fillItemsData($entity, $itemsData);
        }
    }

    /**
     * @param $entity
     * @param array $itemsData
     */
    protected function fillItemsData($entity, array $itemsData = [])
    {
        $repository = $this->getProductRepository();
        foreach ($itemsData as $dataRow) {
            if (!array_key_exists(ProductDataStorage::PRODUCT_SKU_KEY, $dataRow)) {
                continue;
            }

            $product = $repository->findOneBySku($dataRow[ProductDataStorage::PRODUCT_SKU_KEY]);
            if (!$product) {
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
                    $value = $this->doctrineHelper->getEntityReference(
                        $metadata->getAssociationTargetClass($property),
                        $value
                    );
                }

                $this->propertyAccessor->setValue($entity, $property, $value);
            } catch (NoSuchPropertyException $e) {
            }
        }
    }

    /**
     * @param Product $product
     * @param object $entity
     * @param array $itemData
     */
    abstract protected function addItem(Product $product, $entity, array $itemData = []);

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

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return $this->extendedType;
    }

    /**
     * @param string $extendedType
     */
    public function setExtendedType($extendedType)
    {
        $this->extendedType = $extendedType;
    }
}
