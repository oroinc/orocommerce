<?php

namespace Oro\Bundle\CheckoutBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\CheckoutBundle\Model\ExtendCheckoutSource;
use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="orob2b_checkout_source")
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
     * Get first not empty relation
     *
     * @return mixed|null
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
