<?php

namespace Oro\Bundle\CheckoutBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroCheckoutBundle_Entity_CheckoutSource;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\EntityExtendBundle\EntityReflectionClass;
use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;

/**
 * Checkout Source entity
 *
 * @mixin OroCheckoutBundle_Entity_CheckoutSource
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_checkout_source')]
#[Config]
class CheckoutSource implements ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'deleted', type: Types::BOOLEAN, options: ['default' => false])]
    protected ?bool $deleted = false;

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
