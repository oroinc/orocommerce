<?php

namespace Oro\Bundle\DPDBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="oro_dpd_shipping_service")
 * @ORM\Entity
 */
class ShippingService
{
    const CLASSIC_SERVICE_SUBSTR = 'Classic';
    const EXPRESS_SERVICE_SUBSTR = 'Express';

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=10)
     */
    protected $code;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255)
     */
    protected $description;

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
        return (substr($this->code, 0, strlen(static::CLASSIC_SERVICE_SUBSTR)) === static::CLASSIC_SERVICE_SUBSTR);
    }

    /**
     * @return boolean
     */
    public function isExpressService() {
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
