<?php

namespace Oro\Bundle\FrontendNavigationBundle\Tests\Unit\Entity;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use Oro\Bundle\FrontendNavigationBundle\Entity\MenuUpdate;

class MenuUpdateTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['titles', new LocalizedFallbackValue()],
            ['image', 'image.jpg'],
            ['description', 'description'],
            ['condition', 'condition'],
            ['website', new Website()],
        ];

        $this->assertPropertyAccessors(new MenuUpdate(), $properties);
    }

    public function testGetExtras()
    {
        $website = new Website();

        $update = new MenuUpdate();
        $update
            ->setImage('test image')
            ->setDescription('test description')
            ->setCondition('test condition')
            ->setWebsite($website)
        ;

        $expected = [
            'image' => 'test image',
            'description' => 'test description',
            'condition' => 'test condition',
            'website' => $website,
        ];

        $this->assertSame($expected, $update->getExtras());
    }
}
