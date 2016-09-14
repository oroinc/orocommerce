<?php

namespace Oro\Bundle\FrontendNavigationBundle\Tests\Unit\Entity;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\WebsiteBundle\Entity\Website;
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
            ['website', new Website()],
        ];

        $this->assertPropertyAccessors(new MenuUpdate(), $properties);
    }

    public function testGetExtras()
    {
        $website = new Website();
        $image = new File();
        $priority = 10;

        $update = new MenuUpdateStub();
        $update
            ->setImage($image)
            ->setCondition('test condition')
            ->setWebsite($website)
            ->setPriority($priority);

        $expected = [
            'image' => $image,
            'condition' => 'test condition',
            'website' => $website,
            'position' => $priority,
        ];

        $this->assertSame($expected, $update->getExtras());
    }
}
