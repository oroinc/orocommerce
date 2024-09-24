<?php

namespace Oro\Bundle\TaxBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;

/**
* Entity that represents Zip Code
*
*/
#[ORM\Entity]
#[ORM\Table('oro_tax_zip_code')]
#[ORM\HasLifecycleCallbacks]
#[Config(mode: 'hidden')]
class ZipCode implements DatesAwareInterface
{
    use DatesAwareTrait;

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'zip_code', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $zipCode = null;

    #[ORM\Column(name: 'zip_range_start', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $zipRangeStart = null;

    #[ORM\Column(name: 'zip_range_end', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $zipRangeEnd = null;

    #[ORM\ManyToOne(targetEntity: TaxJurisdiction::class, cascade: ['persist'], inversedBy: 'zipCodes')]
    #[ORM\JoinColumn(name: 'tax_jurisdiction_id', referencedColumnName: 'id', nullable: false)]
    protected ?TaxJurisdiction $taxJurisdiction = null;

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return (string)$this->zipCode;
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
     * Set zipCode
     *
     * @param string $zipCode
     *
     * @return ZipCode
     */
    public function setZipCode($zipCode)
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    /**
     * Get zipCode
     *
     * @return string
     */
    public function getZipCode()
    {
        return $this->zipCode;
    }

    /**
     * Set zipRangeStart
     *
     * @param string $zipRangeStart
     *
     * @return ZipCode
     */
    public function setZipRangeStart($zipRangeStart)
    {
        $this->zipRangeStart = $zipRangeStart;

        return $this;
    }

    /**
     * Get zipRangeStart
     *
     * @return string
     */
    public function getZipRangeStart()
    {
        return $this->zipRangeStart;
    }

    /**
     * Set zipRangeEnd
     *
     * @param string $zipRangeEnd
     *
     * @return ZipCode
     */
    public function setZipRangeEnd($zipRangeEnd)
    {
        $this->zipRangeEnd = $zipRangeEnd;

        return $this;
    }

    /**
     * Get zipRangeEnd
     *
     * @return string
     */
    public function getZipRangeEnd()
    {
        return $this->zipRangeEnd;
    }

    /**
     * Is this code single valued
     *
     * @return bool
     */
    public function isSingleZipCode()
    {
        return $this->getZipCode() !== null;
    }

    /**
     * Set taxJurisdiction
     *
     * @param TaxJurisdiction $taxJurisdiction
     *
     * @return ZipCode
     */
    public function setTaxJurisdiction(TaxJurisdiction $taxJurisdiction)
    {
        $this->taxJurisdiction = $taxJurisdiction;

        return $this;
    }

    /**
     * Get taxJurisdiction
     *
     * @return TaxJurisdiction
     */
    public function getTaxJurisdiction()
    {
        return $this->taxJurisdiction;
    }
}
