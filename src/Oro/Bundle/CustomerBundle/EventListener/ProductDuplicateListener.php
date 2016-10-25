<?php

namespace Oro\Bundle\CustomerBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\CustomerBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\ProductBundle\Event\ProductDuplicateAfterEvent;

class ProductDuplicateListener
{
    /** @var  string */
    protected $fieldName;

    /** @var ManagerRegistry */
    protected $registry;

    /** @var string */
    protected $visibilityToAllClassName;

    /** @var string */
    protected $visibilityAccountClassName;

    /** @var string */
    protected $visibilityAccountGroupClassName;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param ProductDuplicateAfterEvent $event
     */
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
            $this->visibilityAccountClassName,
            $product,
            $sourceProduct,
            $manager
        );
        $this->duplicateVisibility(
            $this->visibilityAccountGroupClassName,
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
     * @param string $visibilityAccountClassName
     */
    public function setVisibilityAccountClassName($visibilityAccountClassName)
    {
        $this->visibilityAccountClassName = $visibilityAccountClassName;
    }

    /**
     * @param string $visibilityAccountGroupClassName
     */
    public function setVisibilityAccountGroupClassName($visibilityAccountGroupClassName)
    {
        $this->visibilityAccountGroupClassName = $visibilityAccountGroupClassName;
    }

    /**
     * @param string $fieldName
     */
    public function setFieldName($fieldName)
    {
        $this->fieldName = $fieldName;
    }
}
