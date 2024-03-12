<?php

namespace Oro\Bundle\ShippingBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;

/**
* Entity that represents Shipping Methods Configs Rule Destination
*
*/
#[ORM\Entity]
#[ORM\Table('oro_shipping_rule_destination')]
#[ORM\HasLifecycleCallbacks]
#[Config(mode: 'hidden')]
class ShippingMethodsConfigsRuleDestination
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?int $id = null;

    /**
     * @var Collection<int, ShippingMethodsConfigsRuleDestinationPostalCode>
     */
    #[ORM\OneToMany(
        mappedBy: 'destination',
        targetEntity: ShippingMethodsConfigsRuleDestinationPostalCode::class,
        cascade: ['ALL'],
        fetch: 'EAGER',
        orphanRemoval: true
    )]
    protected ?Collection $postalCodes = null;

    #[ORM\ManyToOne(targetEntity: Region::class)]
    #[ORM\JoinColumn(name: 'region_code', referencedColumnName: 'combined_code')]
    #[ConfigField(defaultValues: ['importexport' => ['order' => 20, 'short' => true, 'identity' => true]])]
    protected ?Region $region = null;

    #[ORM\Column(name: 'region_text', type: Types::STRING, length: 255, nullable: true)]
    #[ConfigField(defaultValues: ['importexport' => ['order' => 30]])]
    protected ?string $regionText = null;

    #[ORM\ManyToOne(targetEntity: Country::class)]
    #[ORM\JoinColumn(name: 'country_code', referencedColumnName: 'iso2_code', nullable: false)]
    #[ConfigField(defaultValues: ['importexport' => ['order' => 40, 'short' => true, 'identity' => true]])]
    protected ?Country $country = null;

    #[ORM\ManyToOne(targetEntity: ShippingMethodsConfigsRule::class, inversedBy: 'destinations')]
    #[ORM\JoinColumn(name: 'rule_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?ShippingMethodsConfigsRule $methodConfigsRule = null;

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
