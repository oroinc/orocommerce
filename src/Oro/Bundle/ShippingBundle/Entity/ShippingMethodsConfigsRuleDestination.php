<?php

namespace Oro\Bundle\ShippingBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

/**
 * @ORM\Entity
 * @ORM\Table("oro_shipping_rule_destination")
 * @ORM\HasLifecycleCallbacks
 * @Config(
 *     mode="hidden",
 * )
 */
class ShippingMethodsConfigsRuleDestination
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $id;

    /**
     * @var Collection|ShippingMethodsConfigsRuleDestinationPostalCode[]
     *
     * @ORM\OneToMany(
     *     targetEntity="ShippingMethodsConfigsRuleDestinationPostalCode",
     *     mappedBy="destination",
     *     cascade={"ALL"},
     *     fetch="EAGER",
     *     orphanRemoval=true
     * )
     */
    protected $postalCodes;

    /**
     * @var Region
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\AddressBundle\Entity\Region")
     * @ORM\JoinColumn(name="region_code", referencedColumnName="combined_code")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "order"=20,
     *              "short"=true,
     *              "identity"=true
     *          }
     *      }
     * )
     */
    protected $region;

    /**
     * @var string
     *
     * @ORM\Column(name="region_text", type="string", length=255, nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "order"=30
     *          }
     *      }
     * )
     */
    protected $regionText;

    /**
     * @var Country
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\AddressBundle\Entity\Country")
     * @ORM\JoinColumn(name="country_code", referencedColumnName="iso2_code", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "order"=40,
     *              "short"=true,
     *              "identity"=true
     *          }
     *      }
     * )
     */
    protected $country;

    /**
     * @var ShippingMethodsConfigsRule
     *
     * @ORM\ManyToOne(
     *     targetEntity="Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule",
     *     inversedBy="destinations"
     * )
     * @ORM\JoinColumn(name="rule_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $methodConfigsRule;

    public function __construct()
    {
        $this->postalCodes = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set region
     *
     * @param Region $region
     * @return $this
     */
    public function setRegion($region)
    {
        $this->region = $region;

        return $this;
    }

    /**
     * Get region
     *
     * @return Region
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * Get name of region
     *
     * @return string
     */
    public function getRegionName()
    {
        return $this->getRegion() ? $this->getRegion()->getName() : '';
    }

    /**
     * Get code of region
     *
     * @return string
     */
    public function getRegionCode()
    {
        return $this->getRegion() ? $this->getRegion()->getCode() : '';
    }

    /**
     * Set region text
     *
     * @param string $regionText
     * @return $this
     */
    public function setRegionText($regionText)
    {
        $this->regionText = $regionText;

        return $this;
    }

    /**
     * Get region test
     *
     * @return string
     */
    public function getRegionText()
    {
        return $this->regionText;
    }

    /**
     * Set country
     *
     * @param Country $country
     * @return $this
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return Country
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Get name of country
     *
     * @return string
     */
    public function getCountryName()
    {
        return $this->getCountry() ? $this->getCountry()->getName() : '';
    }

    /**
     * Get country ISO3 code
     *
     * @return string
     */
    public function getCountryIso3()
    {
        return $this->getCountry() ? $this->getCountry()->getIso3Code() : '';
    }

    /**
     * Get country ISO2 code
     *
     * @return string
     */
    public function getCountryIso2()
    {
        return $this->getCountry() ? $this->getCountry()->getIso2Code() : '';
    }

    /**
     * @param ShippingMethodsConfigsRule $methodConfigsRule
     * @return $this
     */
    public function setMethodConfigsRule(ShippingMethodsConfigsRule $methodConfigsRule)
    {
        $this->methodConfigsRule = $methodConfigsRule;

        return $this;
    }

    /**
     * @return ShippingMethodsConfigsRule
     */
    public function getMethodConfigsRule()
    {
        return $this->methodConfigsRule;
    }

    /**
     * @return Collection|ShippingMethodsConfigsRuleDestinationPostalCode[]
     */
    public function getPostalCodes()
    {
        return $this->postalCodes;
    }

    /**
     * @param ShippingMethodsConfigsRuleDestinationPostalCode $postalCode
     * @return bool
     */
    public function hasPostalCode(ShippingMethodsConfigsRuleDestinationPostalCode $postalCode)
    {
        return $this->postalCodes->contains($postalCode);
    }

    /**
     * @param ShippingMethodsConfigsRuleDestinationPostalCode $postalCode
     * @return $this
     */
    public function addPostalCode(ShippingMethodsConfigsRuleDestinationPostalCode $postalCode)
    {
        if (!$this->hasPostalCode($postalCode)) {
            $postalCode->setDestination($this);
            $this->postalCodes->add($postalCode);
        }

        return $this;
    }

    /**
     * @param ShippingMethodsConfigsRuleDestinationPostalCode $postalCode
     * @return $this
     */
    public function removePostalCode(ShippingMethodsConfigsRuleDestinationPostalCode $postalCode)
    {
        $this->postalCodes->removeElement($postalCode);

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $postalCodesNames = [];
        foreach ($this->getPostalCodes() as $postalCode) {
            $postalCodesNames[] .= $postalCode->getName();
        }

        $postalCodesStr = implode(', ', $postalCodesNames);
        $countryPostalStr = implode(' ', array_filter([$this->getCountry(), $postalCodesStr]));

        return implode(', ', array_filter([$this->getRegionName(), $countryPostalStr]));
    }
}
