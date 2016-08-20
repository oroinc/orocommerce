<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Entity;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\TaxBundle\Entity\TaxJurisdiction;
use Oro\Bundle\TaxBundle\Entity\ZipCode;

class TaxJurisdictionTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', 1],
            ['code', 'fr4a'],
            ['description', 'description'],
            ['country', new Country('UA')],
            ['region', new Region('code')],
            ['regionText', 'region'],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
        ];

        $this->assertPropertyAccessors($this->createTaxJurisdiction(), $properties);
    }

    public function testRelations()
    {
        $this->assertPropertyCollections($this->createTaxJurisdiction(), [
            ['zipCodes', new ZipCode()],
        ]);
    }

    public function testGetRegionName()
    {
        $taxJurisdiction = $this->createTaxJurisdiction();
        $regionName = 'Region name';
        $region = new Region('code');
        $region->setName($regionName);
        $taxJurisdiction->setRegion($region);
        $this->assertEquals($regionName, $taxJurisdiction->getRegionName());

        $textRegionName = 'Text region name';
        $taxJurisdiction->setRegion(null);
        $taxJurisdiction->setRegionText($textRegionName);
        $this->assertEquals($textRegionName, $taxJurisdiction->getRegionName());
    }

    public function testPreUpdate()
    {
        $taxJurisdiction = $this->createTaxJurisdiction();
        $taxJurisdiction->preUpdate();
        $this->assertInstanceOf('\DateTime', $taxJurisdiction->getUpdatedAt());
    }

    public function testPrePersist()
    {
        $taxJurisdiction = $this->createTaxJurisdiction();
        $taxJurisdiction->prePersist();
        $this->assertInstanceOf('\DateTime', $taxJurisdiction->getUpdatedAt());
        $this->assertInstanceOf('\DateTime', $taxJurisdiction->getCreatedAt());
    }

    /**
     * @return TaxJurisdiction
     */
    protected function createTaxJurisdiction()
    {
        return new TaxJurisdiction();
    }

    public function testToString()
    {
        $tax = $this->createTaxJurisdiction();
        $tax->setCode('code');
        $this->assertEquals('code', (string)$tax);
    }
}
