<?php

namespace Oro\Bundle\DPDBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="oro_dpd_shipping_service")
 * @ORM\Entity(repositoryClass="Oro\Bundle\DPDBundle\Entity\Repository\ShippingServiceRepository")
 */
class ShippingService
{
    const CLASSIC_SERVICE_SUBSTR = 'Classic';
    const EXPRESS_SERVICE_SUBSTR = 'Express';

    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="code", type="string", length=30)
     */
    protected $code;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255)
     */
    protected $description;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_express", type="boolean")
     */
    protected $expressService;

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     *
     * @return $this
     */
    public function setCode($code)
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

    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @param bool $isExpressService
     *
     * @return $this
     */
    public function setExpressService($isExpressService)
    {
        $this->expressService = $isExpressService;

        return $this;
    }

    /**
     * @return bool
     */
    public function isExpressService()
    {
        return $this->expressService;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getDescription();
    }
}
