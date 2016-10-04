<?php

namespace Oro\Bundle\FrontendNavigationBundle\Tests\Unit\Entity;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\FrontendNavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\FrontendNavigationBundle\Tests\Unit\Entity\Stub\MenuUpdateStub;

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
            ->setPriority($priority);

        $expected = [
            'image' => $image,
            'condition' => 'test condition',
            'existsInNavigationYml' => false,
            'position' => $priority
        ];

        $this->assertSame($expected, $update->getExtras());
    }
}
