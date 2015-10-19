<?php

namespace OroB2B\Bundle\AccountBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;

use OroB2B\Bundle\ProductBundle\Event\ProductDuplicateAfterEvent;

class ProductDuplicateListener
{
    /** @var Registry */
    protected $doctrine;

    /** @var string */
    protected $className;

    /**
     * @param Registry $registry
     */
    public function __construct(Registry $registry)
    {
        $this->doctrine = $registry;
    }

    public function onDuplicateProduct(ProductDuplicateAfterEvent $event)
    {

    }

    /**
     * @param string $className
     */
    public function setClassName($className)
    {
        $this->className = $className;
    }
}
