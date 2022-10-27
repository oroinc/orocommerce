<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Model;

use Oro\Bundle\TaxBundle\Model\TaxCode;

class TaxCodeTest extends \PHPUnit\Framework\TestCase
{
    public function testNew()
    {
        $code = 'code';
        $type = 'type';

        $taxCode = TaxCode::create($code, $type);
        $this->assertEquals($code, $taxCode->getCode());
        $this->assertEquals($type, $taxCode->getType());

        $taxCodeConstructor = new TaxCode($code, $type);
        $this->assertEquals($code, $taxCodeConstructor->getCode());
        $this->assertEquals($type, $taxCodeConstructor->getType());

        $this->assertEquals($taxCode, $taxCodeConstructor);
    }

    public function testInvalidArgument()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Strings required');

        TaxCode::create(new \stdClass(), 'string');
    }

    public function testInvalidSecondArgument()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Strings required');

        TaxCode::create('string', new \stdClass());
    }
}
