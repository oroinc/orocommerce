<?php

namespace Oro\Bundle\FedexShippingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="oro_fedex_ship_service_rule")
 * @ORM\Entity
 */
class ShippingServiceRule
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
     * @ORM\Column(name="limitation_expression_lbs", type="string", length=250)
     */
    private $limitationExpressionLbs;

    /**
     * @var string|null
     *
     * @ORM\Column(name="limitation_expression_kg", type="string", length=250)
     */
    private $limitationExpressionKg;

    /**
     * @var string|null
     *
     * @ORM\Column(name="service_type", type="string", length=250, nullable=true)
     */
    private $serviceType;

    /**
     * @var boolean
     *
     * @ORM\Column(name="residential_address", type="boolean", options={"default": false})
     */
    private $residentialAddress = false;

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
