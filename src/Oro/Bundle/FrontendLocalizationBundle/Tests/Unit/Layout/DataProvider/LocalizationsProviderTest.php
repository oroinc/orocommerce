<?php

namespace Oro\Bundle\WebsiteBundle\Tests\Unit\Layout\DataProvider;

use Oro\Component\Layout\ContextInterface;

use Oro\Bundle\FrontendLocalizationBundle\Layout\DataProvider\LocalizationsProvider;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManager;

class LocalizationsProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var UserLocalizationManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $localizationManager;

    /** @var LocalizationsProvider */
    protected $localizationsProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->localizationManager = $this->getMockBuilder(UserLocalizationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->localizationsProvider = new LocalizationsProvider($this->localizationManager);
    }

    public function testGetIdentifier()
    {
        $this->assertEquals('orob2b_website_localizations', $this->localizationsProvider->getIdentifier());
    }

    public function testGetData()
    {
        /* @var $context ContextInterface|\PHPUnit_Framework_MockObject_MockObject */
        $context = $this->getMock(ContextInterface::class);

        $this->localizationManager->expects($this->once())
            ->method('getEnabledLocalizations')
            ->willReturn(['L1', 'L2', 'L3']);

        $this->localizationManager->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn('L1');

        $this->assertEquals(
            [
                'localizations' => ['L1', 'L2', 'L3'],
                'current_localization' => 'L1',
            ],
            $this->localizationsProvider->getData($context)
        );
    }
}
