<?php

namespace Oro\Bundle\VisibilityBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Event\ProductDuplicateAfterEvent;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;

class ProductDuplicateListener
{
    /** @var  string */
    protected $fieldName;

    /** @var ManagerRegistry */
    protected $registry;

    /** @var string */
    protected $visibilityToAllClassName;

    /** @var string */
    protected $visibilityCustomerClassName;

    /** @var string */
    protected $visibilityCustomerGroupClassName;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function onDuplicateProduct(ProductDuplicateAfterEvent $event)
    {
        $product = $event->getProduct();
        $sourceProduct = $event->getSourceProduct();
        $manager = $this->registry->getManagerForClass(ClassUtils::getClass($product));
        $this->duplicateVisibility(
            $this->visibilityToAllClassName,
            $product,
            $sourceProduct,
            $manager
        );
        $this->duplicateVisibility(
            $this->visibilityCustomerClassName,
            $product,
            $sourceProduct,
            $manager
        );
        $this->duplicateVisibility(
            $this->visibilityCustomerGroupClassName,
            $product,
            $sourceProduct,
            $manager
        );
        $manager->flush();
    }

    /**
     * @param string $className
     * @param object $entity
     * @param object $sourceEntity
     * @param ObjectManager $manager
     */
    protected function duplicateVisibility($className, $entity, $sourceEntity, $manager)
    {
        /** @var EntityRepository $repository */
        $repository = $manager->getRepository($className);
        $repository->createQueryBuilder('entity')
            ->delete($className, 'entity')
            ->andWhere(sprintf('entity.%s = :entity', $this->fieldName))
            ->setParameter('entity', $entity)
            ->getQuery()
            ->execute();

        /** @var VisibilityInterface[] $visibilities */
        $visibilities = $repository->findBy([$this->fieldName => $sourceEntity]);
        foreach ($visibilities as $visibility) {
            $duplicateVisibility = clone $visibility;
            $duplicateVisibility->setTargetEntity($entity);
            $manager->persist($duplicateVisibility);
        }
    }

    /**
     * @param string $visibilityToAllClassName
     */
    public function setVisibilityToAllClassName($visibilityToAllClassName)
    {
        $this->visibilityToAllClassName = $visibilityToAllClassName;
    }

    /**
     * @param string $visibilityCustomerClassName
     */
    public function setVisibilityCustomerClassName($visibilityCustomerClassName)
    {
        $this->visibilityCustomerClassName = $visibilityCustomerClassName;
    }

    /**
     * @param string $visibilityCustomerGroupClassName
     */
    public function setVisibilityCustomerGroupClassName($visibilityCustomerGroupClassName)
    {
        $this->visibilityCustomerGroupClassName = $visibilityCustomerGroupClassName;
    }

    /**
     * @param string $fieldName
     */
    public function setFieldName($fieldName)
    {
        $this->fieldName = $fieldName;
    }
}
