<?php

namespace Oro\Bundle\WebsiteBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\FrontendLocalizationBundle\Layout\DataProvider\FrontendAccountUserLocalizationProvider;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManager;

use Oro\Bundle\LocaleBundle\Entity\Localization;

use Oro\Component\Layout\ContextInterface;

class FrontendAccountUserLocalizationProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UserLocalizationManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $userLocalizationManager;

    /**
     * @var FrontendAccountUserLocalizationProvider
     */
    protected $dataProvider;

    protected function setUp()
    {
        $this->userLocalizationManager = $this->getMockBuilder(UserLocalizationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dataProvider = new FrontendAccountUserLocalizationProvider($this->userLocalizationManager);
    }

    public function testGetIdentifier()
    {
        $this->assertEquals(FrontendAccountUserLocalizationProvider::NAME, $this->dataProvider->getIdentifier());
    }

    public function testGetData()
    {
        $localization = new Localization();

        /** @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject $context **/
        $context = $this->getMock('Oro\Component\Layout\ContextInterface');

        $this->userLocalizationManager->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        $this->assertSame($localization, $this->dataProvider->getData($context));
    }
}
