<?php

namespace Oro\Bundle\CheckoutBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\EntityExtendBundle\EntityReflectionClass;
use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;

/**
 * Checkout Source entity
 *
 * @ORM\Entity
 * @ORM\Table(name="oro_checkout_source")
 * @Config
 */
class CheckoutSource implements ExtendEntityInterface
{
    use ExtendEntityTrait;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var bool
     *
     * @ORM\Column(name="deleted", type="boolean", options={"default"=false})
     */
    protected $deleted = false;

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function setDeleted(bool $deleted): CheckoutSource
    {
        $this->deleted = (bool)$deleted;

        return $this;
    }

    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * Get first not empty relation
     *
     * @return CheckoutSourceEntityInterface|null
     */
    public function getEntity()
    {
        $reflectionClass = new EntityReflectionClass($this);
        $properties = $reflectionClass->getProperties();

        foreach ($properties as $property) {
            if ($property->getName() !== 'id') {
                $property->setAccessible(true);
                $value = $property->getValue($this);
                if ($value instanceof CheckoutSourceEntityInterface) {
                    return $value;
                }
            }
        }

        return null;
    }

    public function clear(): void
    {
        $reflectionClass = new EntityReflectionClass($this);
        $properties = $reflectionClass->getProperties();
        foreach ($properties as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($this);
            if ($value instanceof CheckoutSourceEntityInterface) {
                $property->setValue($this, null);
            }
        }
    }
}
