<?php

namespace Oro\Bundle\DPDBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;

/**
 * @ORM\Table(name="oro_dpd_rate")
 * @ORM\Entity(repositoryClass="Oro\Bundle\DPDBundle\Entity\Repository\RateRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Rate
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var DPDTransport
     * @ORM\ManyToOne(targetEntity="DPDTransport", inversedBy="rates")
     * @ORM\JoinColumn(name="transport_id", referencedColumnName="id", nullable=false)
     */
    protected $transport;

    /**
     * @var ShippingService
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\DPDBundle\Entity\ShippingService")
     * @ORM\JoinColumn(name="shipping_service_id", referencedColumnName="code", onDelete="CASCADE")
     */
    protected $shippingService;

    /**
     * @var Country
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\AddressBundle\Entity\Country")
     * @ORM\JoinColumn(name="country_code", referencedColumnName="iso2_code", nullable=false)
     */
    protected $country;

    /**
     * @var Region
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\AddressBundle\Entity\Region")
     * @ORM\JoinColumn(name="region_code", referencedColumnName="combined_code")
     */
    protected $region;

    /**
     * @var string
     *
     * @ORM\Column(name="region_text", type="string", length=255, nullable=true)
     */
    protected $regionText;

    /**
     * @var float
     *
     * @ORM\Column(name="weight_value", type="float", nullable=true)
     */
    protected $weightValue;

    /**
     * @var string
     *
     * @ORM\Column(name="price_value", type="money", nullable=false)
     */
    protected $priceValue;


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return DPDTransport
     */
    public function getTransport()
    {
        return $this->transport;
    }

    /**
     * @param DPDTransport $transport
     * @return Rate
     */
    public function setTransport(DPDTransport $transport = null)
    {
        $this->transport = $transport;
        return $this;
    }


    /**
     * @return ShippingService
     */
    public function getShippingService()
    {
        return $this->shippingService;
    }

    /**
     * @param ShippingService $shippingService
     * @return Rate
     */
    public function setShippingService($shippingService)
    {
        $this->shippingService = $shippingService;
        return $this;
    }

    /**
     * @return Country
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param Country $country
     * @return Rate
     */
    public function setCountry(Country $country = null)
    {
        $this->country = $country;
        return $this;
    }

    /**
     * @return Region
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * @param Region $region
     * @return Rate
     */
    public function setRegion(Region $region = null)
    {
        $this->region = $region;
        return $this;
    }

    /**
     * @return string
     */
    public function getRegionText()
    {
        return $this->regionText;
    }

    /**
     * @param string $regionText
     * @return Rate
     */
    public function setRegionText($regionText)
    {
        $this->regionText = $regionText;
        return $this;
    }

    /**
     * Get name of region
     *
     * @return string
     */
    public function getRegionName()
    {
        return $this->getRegion() ? $this->getRegion()->getName() : $this->getRegionText();
    }

    /**
     * @return float
     */
    public function getWeightValue()
    {
        return $this->weightValue;
    }

    /**
     * @param float $weightValue
     * @return Rate
     */
    public function setWeightValue($weightValue)
    {
        $this->weightValue = $weightValue;
        return $this;
    }

    /**
     * @return string
     */
    public function getPriceValue()
    {
        return $this->priceValue;
    }

    /**
     * @param string $priceValue
     * @return Rate
     */
    public function setPriceValue($priceValue)
    {
        $this->priceValue = $priceValue;
        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf(
            '%s, %s, %s, %f => %s',
            $this->getShippingService()->getCode(),
            $this->getCountry()?$this->getCountry():'*',
            $this->getRegionName()?$this->getRegionName():'*',
            $this->getWeightValue()?$this->getWeightValue():'*',
            $this->getPriceValue()
        );
    }
}