<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\DBAL\Types;

use Doctrine\DBAL\Types\Type;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGType;
use Oro\Component\TestUtils\ORM\Mocks\DatabasePlatformMock;

class WYSIWYGTypeTest extends \PHPUnit\Framework\TestCase
{
    public function testGetName(): void
    {
        Type::addType('wysiwyg', WYSIWYGType::class);
        $type = Type::getType('wysiwyg');
        $this->assertEquals('wysiwyg', $type->getName());
    }

    public function testRequiresSQLCommentHint(): void
    {
        $type = Type::getType('wysiwyg');
        /** @var DatabasePlatformMock $platform */
        $platform = $this->createMock(DatabasePlatformMock::class);

        $this->assertTrue($type->requiresSQLCommentHint($platform));
    }
}
