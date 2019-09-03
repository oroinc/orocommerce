<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\DBAL\Types;

use Doctrine\DBAL\Types\Type;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGType;

class WYSIWYGTypeTest extends \PHPUnit\Framework\TestCase
{
    public function testGetName()
    {
        Type::addType('wysiwyg', WYSIWYGType::class);
        $type = Type::getType('wysiwyg');
        $this->assertEquals('wysiwyg', $type->getName());
    }
}
