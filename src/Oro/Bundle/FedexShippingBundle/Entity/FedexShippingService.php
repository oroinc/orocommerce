<?php

namespace Oro\Bundle\FedexShippingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="oro_fedex_shipping_service")
 * @ORM\Entity
 */
class FedexShippingService
{
    /**
     * @var integer|null
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="code", type="string", length=200)
     */
    private $code;

    /**
     * @var string|null
     *
     * @ORM\Column(name="description", type="string", length=200)
     */
    private $description;

    /**
     * @var ShippingServiceRule|null
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\FedexShippingBundle\Entity\ShippingServiceRule")
     * @ORM\JoinColumn(name="rule_id", referencedColumnName="id", nullable=false)
     */
    private $rule;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return ShippingServiceRule|null
     */
    public function getRule()
    {
        return $this->rule;
    }

    public function setRule(ShippingServiceRule $rule): self
    {
        $this->rule = $rule;

        return $this;
    }
}
