<?php

namespace Oro\Bundle\UPSBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="oro_integration_ups_service")
 * @ORM\Entity
 */
class ShippingService
{
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(type="string", length=10)
     * @ORM\GeneratedValue(strategy="NONE")
     */
    protected $code;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255)
     */
    protected $description;

    /**
     * @ORM\ManyToOne(targetEntity="UPSTransport", inversedBy="applicableShippingServices")
     * @ORM\JoinColumn(name="transport_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $transport;

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
     * @return UPSTransport
     */
    public function getTransport()
    {
        return $this->transport;
    }

    /**
     * @param UPSTransport $transport
     * @return $this
     */
    public function setTransport($transport)
    {
        $this->transport = $transport;
        return $this;
    }
}
