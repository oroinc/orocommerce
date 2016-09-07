<?php

namespace Oro\Bundle\FrontendNavigationBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\FrontendNavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\FrontendNavigationBundle\Tests\Unit\Entity\Stub\MenuUpdateStub;

class MenuUpdateTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['titles', new LocalizedFallbackValue()],
            ['condition', 'condition'],
            ['website', new Website()],
        ];

        $this->assertPropertyAccessors(new MenuUpdate(), $properties);
    }

    public function testGetExtras()
    {
        $website = new Website();

        $update = new MenuUpdateStub();
        $update
            ->setImage('test image')
            ->setCondition('test condition')
            ->setWebsite($website)
        ;

        $expected = [
            'image' => 'test image',
            'condition' => 'test condition',
            'website' => $website,
        ];

        $this->assertSame($expected, $update->getExtras());
    }
}
