<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Entity;

use OroB2B\Bundle\TaxBundle\Model\TaxCode;
use OroB2B\Bundle\TaxBundle\Model\TaxCodes;

class TaxCodesTest extends \PHPUnit_Framework_TestCase
{
    public function testGetAvailableTypes()
    {
        $this->assertInternalType('array', (new TaxCodes())->getAvailableTypes());
    }

    public function testGetHash()
    {
        $taxCodes = new TaxCodes([TaxCode::create('test1', 'test2')]);
        $hash1 = $taxCodes->getHash();
        $hash2 = $taxCodes->getHash();
        $this->assertInternalType('string', $hash1);
        $this->assertInternalType('string', $hash2);
        $this->assertEquals($hash1, $hash2);
    }

    public function testGetPlainTypedCodes()
    {
        $taxCodes = new TaxCodes(
            [TaxCode::create('val1', 'type1'), TaxCode::create('val2', 'type1'), TaxCode::create('val2', 'type2')]
        );

        $this->assertEquals(['type1' => ['val1', 'val2'], 'type2' => ['val2']], $taxCodes->getPlainTypedCodes());
    }

    public function testGetCodes()
    {
        $codes = [TaxCode::create('val1', 'type1'), TaxCode::create('val2', 'type1'), TaxCode::create('val2', 'type2')];
        $taxCodes = new TaxCodes($codes);

        $this->assertEquals($codes, $taxCodes->getCodes());
    }

    public function testInvalidTaxCodeTypeIgnored()
    {
        $taxCodes = new TaxCodes([new \stdClass()]);

        $this->assertEquals([], $taxCodes->getCodes());
    }

    public function testInvalidTaxCodeIgnored()
    {
        $taxCodes = new TaxCodes([TaxCode::create('', 'type')]);

        $this->assertEquals([], $taxCodes->getCodes());
    }
}
