<?php

namespace Oro\Bundle\FedexShippingBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
* Entity that represents Fedex Shipping Service
*
*/
#[ORM\Entity]
#[ORM\Table(name: 'oro_fedex_shipping_service')]
class FedexShippingService
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(name: 'code', type: Types::STRING, length: 200)]
    private ?string $code = null;

    #[ORM\Column(name: 'description', type: Types::STRING, length: 200)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: ShippingServiceRule::class)]
    #[ORM\JoinColumn(name: 'rule_id', referencedColumnName: 'id', nullable: false)]
    private ?ShippingServiceRule $rule = null;

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
