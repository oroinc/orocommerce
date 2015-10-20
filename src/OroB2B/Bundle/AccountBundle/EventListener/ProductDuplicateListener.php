<?php

namespace OroB2B\Bundle\AccountBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Util\ClassUtils;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\ProductBundle\Event\ProductDuplicateAfterEvent;

class ProductDuplicateListener
{
    const FIELD_NAME = 'product';

    /** @var Registry */
    protected $doctrine;

    /** @var string */
    protected $visibilityToAllClassName;

    /** @var string */
    protected $visibilityAccountClassName;

    /** @var string */
    protected $visibilityAccountGroupClassName;

    /**
     * @param Registry $registry
     */
    public function __construct(Registry $registry)
    {
        $this->doctrine = $registry;
    }

    /**
     * @param ProductDuplicateAfterEvent $event
     */
    public function onDuplicateProduct(ProductDuplicateAfterEvent $event)
    {
        $product = $event->getProduct();
        $sourceProduct = $event->getSourceProduct();
        $manager = $this->doctrine->getManagerForClass(ClassUtils::getClass($product));
        $this->duplicateVisibility(
            $this->visibilityToAllClassName,
            self::FIELD_NAME,
            $product,
            $sourceProduct,
            $manager
        );
        $this->duplicateVisibility(
            $this->visibilityAccountClassName,
            self::FIELD_NAME,
            $product,
            $sourceProduct,
            $manager
        );
        $this->duplicateVisibility(
            $this->visibilityAccountGroupClassName,
            self::FIELD_NAME,
            $product,
            $sourceProduct,
            $manager
        );
        $manager->flush();
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param object $entity
     * @param object $sourceEntity
     * @param ObjectManager $manager
     */
    protected function duplicateVisibility($className, $fieldName, $entity, $sourceEntity, $manager)
    {
        /** @var VisibilityInterface[] $visibilities */
        $visibilities = $manager->getRepository($className)->findBy([$fieldName => $sourceEntity]);
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
}
