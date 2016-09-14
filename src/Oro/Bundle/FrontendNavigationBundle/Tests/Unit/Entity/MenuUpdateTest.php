<?php

namespace Oro\Bundle\FrontendNavigationBundle\Tests\Unit\Entity;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use Oro\Bundle\LocaleBundle\Entity\Localization;
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

    public function testTitleAccessors()
    {
        $update = new MenuUpdate();
        $this->assertEmpty($update->getTitles()->toArray());

        $firstTitle = $this->createLocalizedValue();

        $secondTitle = $this->createLocalizedValue();

        $update->addTitle($firstTitle)
            ->addTitle($secondTitle)
            ->addTitle($secondTitle);

        $this->assertCount(2, $update->getTitles()->toArray());

        $this->assertEquals([$firstTitle, $secondTitle], array_values($update->getTitles()->toArray()));

        $update->removeTitle($firstTitle)
            ->removeTitle($firstTitle);

        $this->assertEquals([$secondTitle], array_values($update->getTitles()->toArray()));
    }

    /**
     * @param bool|false $default
     *
     * @return LocalizedFallbackValue
     */
    protected function createLocalizedValue($default = false)
    {
        $localized = (new LocalizedFallbackValue())->setString('some string');

        if (!$default) {
            $localized->setLocalization(new Localization());
        }

        return $localized;
    }
}
