<?php


namespace Oro\Bundle\CMSBundle\Tests\Unit\DBAL\Types;

use Doctrine\DBAL\Types\Type;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGPropertiesType;

class WYSIWYGPropertiesTypeTest extends \PHPUnit\Framework\TestCase
{
    public function testSuffixConst(): void
    {
        $this->assertEquals('_properties', WYSIWYGPropertiesType::TYPE_SUFFIX);
    }

    public function testGetName(): void
    {
        $typeName = 'wysiwyg_properties';
        Type::addType($typeName, WYSIWYGPropertiesType::class);
        $type = Type::getType($typeName);
        $this->assertEquals($typeName, $type->getName());
    }
}
