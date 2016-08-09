<?php

namespace Oro\Bundle\WebsiteBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\FrontendLocalizationBundle\Layout\DataProvider\FrontendEnabledLocalizationsProvider;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManager;

use Oro\Bundle\LocaleBundle\Entity\Localization;

use Oro\Component\Layout\ContextInterface;

class FrontendEnabledLocalizationsProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UserLocalizationManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $userLocalizationManager;

    /**
     * @var FrontendEnabledLocalizationsProvider
     */
    protected $dataProvider;

    protected function setUp()
    {
        $this->userLocalizationManager = $this->getMockBuilder(UserLocalizationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dataProvider = new FrontendEnabledLocalizationsProvider($this->userLocalizationManager);
    }

    public function testGetIdentifier()
    {
        $this->assertEquals(FrontendEnabledLocalizationsProvider::NAME, $this->dataProvider->getIdentifier());
    }

    public function testGetData()
    {
        $localizations = [new Localization(), new Localization()];

        /** @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject $context **/
        $context = $this->getMock('Oro\Component\Layout\ContextInterface');

        $this->userLocalizationManager->expects($this->once())
            ->method('getEnabledLocalizations')
            ->willReturn($localizations);

        $this->assertSame($localizations, $this->dataProvider->getData($context));
    }
}
