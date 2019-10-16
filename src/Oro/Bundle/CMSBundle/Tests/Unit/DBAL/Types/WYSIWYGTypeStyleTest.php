<?php


namespace Oro\Bundle\CMSBundle\Tests\Unit\DBAL\Types;

use Doctrine\DBAL\Types\Type;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGStyleType;

class WYSIWYGTypeStyleTest extends \PHPUnit\Framework\TestCase
{
    public function testPrefixConst(): void
    {
        $this->assertEquals('_style', WYSIWYGStyleType::TYPE_SUFFIX);
    }

    public function testGetName(): void
    {
        Type::addType('wysiwyg_style', WYSIWYGStyleType::class);
        $type = Type::getType('wysiwyg_style');
        $this->assertEquals('wysiwyg_style', $type->getName());
    }
}
