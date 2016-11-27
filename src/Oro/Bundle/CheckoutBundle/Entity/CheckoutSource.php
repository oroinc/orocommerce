<?php

namespace Oro\Bundle\CheckoutBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\CheckoutBundle\Model\ExtendCheckoutSource;
use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="oro_checkout_source")
 * @Config
 */
class CheckoutSource extends ExtendCheckoutSource
{
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
     * @param int $id
     * @return CheckoutSource
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param bool $deleted
     *
     * @return $this
     */
    public function setDeleted($deleted)
    {
        $this->deleted = (bool)$deleted;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDeleted()
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
        $reflectionClass = new \ReflectionClass($this);
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
}
