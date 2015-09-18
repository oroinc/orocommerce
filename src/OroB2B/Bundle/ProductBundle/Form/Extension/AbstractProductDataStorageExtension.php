<?php

namespace OroB2B\Bundle\ProductBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;

abstract class AbstractProductDataStorageExtension extends AbstractTypeExtension
{
    /** @var Request */
    protected $request;

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
     * @param ProductDataStorage $storage
     * @param DoctrineHelper $doctrineHelper
     * @param string $productClass
     */
    public function __construct(ProductDataStorage $storage, DoctrineHelper $doctrineHelper, $productClass)
    {
        $this->storage = $storage;
        $this->doctrineHelper = $doctrineHelper;
        $this->productClass = $productClass;
    }

    /**
     * @param Request $request
     * @return $this
     */
    public function setRequest($request)
    {
        $this->request = $request;

        return $this;
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
        if ($this->request->get(ProductDataStorage::STORAGE_KEY)) {
            $entity = isset($options['data']) ? $options['data'] : null;
            if ($entity instanceof $this->dataClass && !$this->doctrineHelper->getSingleEntityIdentifier($entity)) {
                $this->fillData($entity);
            }
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
            if (!array_key_exists(ProductDataStorage::PRODUCT_SKU_KEY, $dataRow) ||
                !array_key_exists(ProductDataStorage::PRODUCT_QUANTITY_KEY, $dataRow)
            ) {
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
