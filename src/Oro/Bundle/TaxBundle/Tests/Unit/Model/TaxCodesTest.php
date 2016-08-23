<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\Entity;

use Oro\Bundle\TaxBundle\Model\TaxCode;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;
use Oro\Bundle\TaxBundle\Model\TaxCodes;

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

    /**
     * @dataProvider isFullFilledTaxCodeProvide
     * @param array $codes
     * @param bool $expected
     */
    public function testIsFullFilledTaxCode(array $codes, $expected)
    {
        $taxCodes = TaxCodes::create($codes);

        $this->assertEquals($expected, $taxCodes->isFullFilledTaxCode());
    }

    /**
     * @return array
     */
    public function isFullFilledTaxCodeProvide()
    {
        return [
            'all taxCodes' => [
                'codes' => [
                    TaxCode::create('val1', TaxCodeInterface::TYPE_PRODUCT),
                    TaxCode::create('val2', TaxCodeInterface::TYPE_ACCOUNT)
                ],
                'expected' => true
            ],
            'without account' => [
                'codes' => [
                    TaxCode::create('val1', TaxCodeInterface::TYPE_PRODUCT),
                ],
                'expected' => false
            ],
            'without product' => [
                'codes' => [
                    TaxCode::create('val2', TaxCodeInterface::TYPE_ACCOUNT)
                ],
                'expected' => false
            ]
        ];
    }
}
