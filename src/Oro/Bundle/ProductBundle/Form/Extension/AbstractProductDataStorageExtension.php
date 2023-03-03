<?php

namespace Oro\Bundle\ProductBundle\Form\Extension;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\ProductBundle\Entity\Product;
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
 * Abstract product data storage form type extension that generates new line item and adds it to entity
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
     * {@inheritdoc}
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
        return $this->requestStack->getCurrentRequest()->get(ProductDataStorage::STORAGE_KEY)
               && $this->storage->get();
    }

    /**
     * @param OptionsResolver $resolver
     * @return void
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

    protected function fillItemsData($entity, array $itemsData = [])
    {
        $repository = $this->getProductRepository();
        foreach ($itemsData as $dataRow) {
            if (!array_key_exists(ProductDataStorage::PRODUCT_SKU_KEY, $dataRow)) {
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
                    if (is_array($value)) {
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
                if (null !== $this->logger) {
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
}
