<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Model;

use Oro\Bundle\TaxBundle\Model\TaxCode;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;
use Oro\Bundle\TaxBundle\Model\TaxCodes;

class TaxCodesTest extends \PHPUnit\Framework\TestCase
{
    public function testGetAvailableTypes()
    {
        $this->assertIsArray((new TaxCodes())->getAvailableTypes());
    }

    public function testGetHash()
    {
        $taxCodes = new TaxCodes([TaxCode::create('test1', 'test2')]);
        $hash1 = $taxCodes->getHash();
        $hash2 = $taxCodes->getHash();
        $this->assertIsString($hash1);
        $this->assertIsString($hash2);
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

    public function testInvalidTaxCodeIgnored()
    {
        $taxCodes = new TaxCodes([TaxCode::create('', 'type')]);

        $this->assertEquals([], $taxCodes->getCodes());
    }

    /**
     * @dataProvider isFullFilledTaxCodeProvide
     */
    public function testIsFullFilledTaxCode(array $codes, bool $expected)
    {
        $taxCodes = TaxCodes::create($codes);

        $this->assertEquals($expected, $taxCodes->isFullFilledTaxCode());
    }

    public function isFullFilledTaxCodeProvide(): array
    {
        return [
            'all taxCodes' => [
                'codes' => [
                    TaxCode::create('val1', TaxCodeInterface::TYPE_PRODUCT),
                    TaxCode::create('val2', TaxCodeInterface::TYPE_ACCOUNT)
                ],
                'expected' => true
            ],
            'without customer' => [
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
