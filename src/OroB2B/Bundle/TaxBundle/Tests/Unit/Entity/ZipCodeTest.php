<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\TaxBundle\Entity\TaxJurisdiction;
use Oro\Bundle\TaxBundle\Entity\ZipCode;

class ZipCodeTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', 1],
            ['zipCode', '03507'],
            ['zipRangeStart', '05700'],
            ['zipRangeEnd', '05800'],
            ['taxJurisdiction', new TaxJurisdiction()],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
        ];

        $this->assertPropertyAccessors($this->createZipCode(), $properties);
    }

    public function testIsSingleZipCode()
    {
        $zipCode = $this->createZipCode();
        $zipCode->setZipCode('01000');
        $this->assertTrue($zipCode->isSingleZipCode());
        $this->assertEmpty($zipCode->getZipRangeStart());
        $this->assertEmpty($zipCode->getZipRangeEnd());

        $zipCode->setZipCode(null)
            ->setZipRangeStart('01000')
            ->setZipRangeEnd('02000');
        $this->assertFalse($zipCode->isSingleZipCode());
        $this->assertEmpty($zipCode->getZipCode());
        $this->assertNotEmpty($zipCode->getZipRangeStart());
        $this->assertNotEmpty($zipCode->getZipRangeEnd());
    }

    public function testPreUpdate()
    {
        $zipCode = $this->createZipCode();
        $zipCode->preUpdate();
        $this->assertInstanceOf('\DateTime', $zipCode->getUpdatedAt());
    }

    public function testPrePersist()
    {
        $zipCode = $this->createZipCode();
        $zipCode->prePersist();
        $this->assertInstanceOf('\DateTime', $zipCode->getUpdatedAt());
        $this->assertInstanceOf('\DateTime', $zipCode->getCreatedAt());
    }

    /**
     * @return ZipCode
     */
    protected function createZipCode()
    {
        return new ZipCode();
    }

    public function testToString()
    {
        $zipCode = 'm3c';
        $code = $this->createZipCode()->setZipCode($zipCode);
        $this->assertEquals($zipCode, (string)$code);
    }
}
