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
use OroB2B\Bundle\ProductBundle\Model\ComponentProcessorInterface;

abstract class AbstractPostQuickAddTypeExtension extends AbstractTypeExtension
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
        if ($this->request->get(ComponentProcessorInterface::TRANSFORM)) {
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

        if (array_key_exists(ComponentProcessorInterface::ENTITY_DATA_KEY, $data)) {
            $this->fillEntityData($entity, $data[ComponentProcessorInterface::ENTITY_DATA_KEY]);
        }

        if (!array_key_exists(ComponentProcessorInterface::ENTITY_ITEMS_DATA_KEY, $data)) {
            return;
        }

        $itemsData = $data[ComponentProcessorInterface::ENTITY_ITEMS_DATA_KEY];

        $repository = $this->getProductRepository();
        foreach ($itemsData as $dataRow) {
            if (!array_key_exists(ComponentProcessorInterface::PRODUCT_SKU_FIELD_NAME, $dataRow) ||
                !array_key_exists(ComponentProcessorInterface::PRODUCT_QUANTITY_FIELD_NAME, $dataRow)
            ) {
                continue;
            }

            $product = $repository->findOneBySku($dataRow[ComponentProcessorInterface::PRODUCT_SKU_FIELD_NAME]);
            if (!$product) {
                continue;
            }

            $item = $this->getItem($product, $entity);
            if (!$item) {
                continue;
            }
            $this->fillEntityData($item, $dataRow);
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
     * @return object|null
     */
    abstract protected function getItem(Product $product, $entity);

    /**
     * @return ProductRepository
     */
    protected function getProductRepository()
    {
        return $this->doctrineHelper->getEntityRepository($this->productClass);
    }
}
