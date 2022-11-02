<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\DBAL\Types;

use Doctrine\DBAL\Types\Type;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGType;
use Oro\Component\TestUtils\ORM\Mocks\DatabasePlatformMock;

class WYSIWYGTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var Type */
    private $type;

    public static function setUpBeforeClass(): void
    {
        Type::addType('wysiwyg', WYSIWYGType::class);
    }

    protected function setUp(): void
    {
        $this->type = Type::getType('wysiwyg');
    }

    public function testGetName(): void
    {
        $this->assertEquals('wysiwyg', $this->type->getName());
    }

    public function testRequiresSQLCommentHint(): void
    {
        /** @var DatabasePlatformMock $platform */
        $platform = $this->createMock(DatabasePlatformMock::class);

        $this->assertTrue($this->type->requiresSQLCommentHint($platform));
    }
}
