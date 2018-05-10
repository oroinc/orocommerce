<?php

namespace Oro\Bundle\TaxBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

/**
 * Entity that represents tax jusrisdiction
 *
 * @ORM\Entity
 * @ORM\Table("oro_tax_jurisdiction")
 * @ORM\HasLifecycleCallbacks
 * @Config(
 *     mode="hidden",
 *     routeName="oro_tax_jurisdiction_index",
 *     routeView="oro_tax_jurisdiction_view",
 *     routeUpdate="oro_tax_jurisdiction_update",
 *     defaultValues={
 *         "security"={
 *             "type"="ACL",
 *             "group_name"=""
 *         },
 *     }
 * )
 */
class TaxJurisdiction implements DatesAwareInterface
{
    use DatesAwareTrait;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255, unique=true)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "order"=10,
     *              "identity"=true
     *          }
     *      }
     * )
     */
    protected $code;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $description;

    /**
     * @var Country
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\AddressBundle\Entity\Country")
     * @ORM\JoinColumn(name="country_code", referencedColumnName="iso2_code")
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
     * @var Collection|ZipCode[]
     *
     * @ORM\OneToMany(
     *      targetEntity="Oro\Bundle\TaxBundle\Entity\ZipCode",
     *      mappedBy="taxJurisdiction",
     *      cascade={"all"},
     *      orphanRemoval=true
     * )
     */
    protected $zipCodes;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->zipCodes = new ArrayCollection();
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
     * Set code
     *
     * @param string $code
     *
     * @return TaxJurisdiction
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return TaxJurisdiction
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set country
     *
     * @param Country $country
     *
     * @return TaxJurisdiction
     */
    public function setCountry(Country $country = null)
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
     * Set region
     *
     * @param Region $region
     *
     * @return TaxJurisdiction
     */
    public function setRegion(Region $region = null)
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
     * Set regionText
     *
     * @param string $regionText
     *
     * @return TaxJurisdiction
     */
    public function setRegionText($regionText)
    {
        $this->regionText = $regionText;

        return $this;
    }

    /**
     * Get regionText
     *
     * @return string
     */
    public function getRegionText()
    {
        return $this->regionText;
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
     * Add zipCode
     *
     * @param ZipCode $zipCode
     *
     * @return TaxJurisdiction
     */
    public function addZipCode(ZipCode $zipCode)
    {
        if (!$this->zipCodes->contains($zipCode)) {
            $zipCode->setTaxJurisdiction($this);
            $this->zipCodes[] = $zipCode;
        }

        return $this;
    }

    /**
     * Remove zipCode
     *
     * @param ZipCode $zipCode
     * @return $this
     */
    public function removeZipCode(ZipCode $zipCode)
    {
        if ($this->zipCodes->contains($zipCode)) {
            $this->zipCodes->removeElement($zipCode);
        }

        return $this;
    }

    /**
     * Get zipCodes
     *
     * @return Collection
     */
    public function getZipCodes()
    {
        return $this->zipCodes;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->code;
    }
}
