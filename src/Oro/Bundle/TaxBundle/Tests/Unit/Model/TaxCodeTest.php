<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\Entity;

use Oro\Bundle\TaxBundle\Model\TaxCode;

class TaxCodeTest extends \PHPUnit_Framework_TestCase
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

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Strings required
     */
    public function testInvalidArgument()
    {
        TaxCode::create(new \stdClass(), 'string');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Strings required
     */
    public function testInvalidSecondArgument()
    {
        TaxCode::create('string', new \stdClass());
    }
}
