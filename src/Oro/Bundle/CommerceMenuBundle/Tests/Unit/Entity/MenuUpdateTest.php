<?php

namespace Oro\Bundle\CommerceMenuBundle\Tests\Unit\Entity;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\CommerceMenuBundle\Entity\MenuUpdate;
use Oro\Bundle\CommerceMenuBundle\Tests\Unit\Entity\Stub\MenuUpdateStub;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class MenuUpdateTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['condition', 'condition'],
        ];

        $this->assertPropertyAccessors(new MenuUpdate(), $properties);
    }

    public function testGetExtras()
    {
        $image = new File();
        $priority = 10;

        $update = new MenuUpdateStub();
        $update
            ->setImage($image)
            ->setCondition('test condition')
            ->setIcon('test-icon')
            ->setPriority($priority)
            ->setDivider(true);

        $expected = [
            'image' => $image,
            'condition' => 'test condition',
            'divider' => true,
            'translateDisabled' => false,
            'position' => $priority,
            'icon' => 'test-icon',
        ];

        $this->assertSame($expected, $update->getExtras());
    }
}
