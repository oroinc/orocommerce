<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\DBAL\Types;

use Doctrine\DBAL\Types\Type;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGStyleType;
use Oro\Component\TestUtils\ORM\Mocks\DatabasePlatformMock;

class WYSIWYGTypeStyleTest extends \PHPUnit\Framework\TestCase
{
    /** @var Type */
    private $type;

    public static function setUpBeforeClass(): void
    {
        Type::addType('wysiwyg_style', WYSIWYGStyleType::class);
    }

    protected function setUp(): void
    {
        $this->type = Type::getType('wysiwyg_style');
    }

    public function testPrefixConst(): void
    {
        $this->assertEquals('_style', WYSIWYGStyleType::TYPE_SUFFIX);
    }

    public function testGetName(): void
    {
        $this->assertEquals('wysiwyg_style', $this->type->getName());
    }

    public function testRequiresSQLCommentHint(): void
    {
        /** @var DatabasePlatformMock $platform */
        $platform = $this->createMock(DatabasePlatformMock::class);

        $this->assertTrue($this->type->requiresSQLCommentHint($platform));
    }
}
