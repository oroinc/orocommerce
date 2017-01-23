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
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
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
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isClassicService() {
        // FIXME: Use 0 === strpos($this->code, static::CLASSIC_SERVICE_SUBSTR) instead?
        return (substr($this->code, 0, strlen(static::CLASSIC_SERVICE_SUBSTR)) === static::CLASSIC_SERVICE_SUBSTR);
    }

    /**
     * @return boolean
     */
    public function isExpressService() {
        // FIXME: Use 0 === strpos($this->code, static::CLASSIC_SERVICE_SUBSTR) instead?
        return (substr($this->code, 0, strlen(static::EXPRESS_SERVICE_SUBSTR)) === static::EXPRESS_SERVICE_SUBSTR);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getDescription();
    }
}
