<?php

namespace Oro\Bundle\FedexShippingBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
* Entity that represents Shipping Service Rule
*
*/
#[ORM\Entity]
#[ORM\Table(name: 'oro_fedex_ship_service_rule')]
class ShippingServiceRule
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(name: 'limitation_expression_lbs', type: Types::STRING, length: 250)]
    private ?string $limitationExpressionLbs = null;

    #[ORM\Column(name: 'limitation_expression_kg', type: Types::STRING, length: 250)]
    private ?string $limitationExpressionKg = null;

    #[ORM\Column(name: 'service_type', type: Types::STRING, length: 250, nullable: true)]
    private ?string $serviceType = null;

    #[ORM\Column(name: 'residential_address', type: Types::BOOLEAN, options: ['default' => false])]
    private ?bool $residentialAddress = false;

    /**
     * @return integer|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getLimitationExpressionLbs()
    {
        return $this->limitationExpressionLbs;
    }

    public function setLimitationExpressionLbs(string $limitationExpressionLbs): self
    {
        $this->limitationExpressionLbs = $limitationExpressionLbs;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLimitationExpressionKg()
    {
        return $this->limitationExpressionKg;
    }

    public function setLimitationExpressionKg(string $limitationExpressionKg): self
    {
        $this->limitationExpressionKg = $limitationExpressionKg;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getServiceType()
    {
        return $this->serviceType;
    }

    /**
     * @param null|string $serviceType
     *
     * @return self
     */
    public function setServiceType($serviceType): self
    {
        $this->serviceType = $serviceType;

        return $this;
    }

    public function isResidentialAddress(): bool
    {
        return $this->residentialAddress;
    }

    public function setResidentialAddress(bool $residentialAddress): self
    {
        $this->residentialAddress = $residentialAddress;

        return $this;
    }
}
