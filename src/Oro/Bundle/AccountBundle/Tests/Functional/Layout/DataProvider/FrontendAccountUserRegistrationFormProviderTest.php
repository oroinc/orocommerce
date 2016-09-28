<?php

namespace Oro\Bundle\AccountBundle\Tests\Functional\Layout\DataProvider;

use Oro\Bundle\AccountBundle\Form\Type\FrontendAccountUserRegistrationType;
use Oro\Bundle\AccountBundle\Layout\DataProvider\FrontendAccountUserRegistrationFormProvider;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

/**
 * @dbIsolation
 */
class FrontendAccountUserRegistrationFormProviderTest extends WebTestCase
{
    /** @var FrontendAccountUserRegistrationFormProvider */
    protected $dataProvider;

    /**
     * @var WebsiteManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $websiteManager;

    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->websiteManager = $this->getMockBuilder(WebsiteManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dataProvider = new FrontendAccountUserRegistrationFormProvider(
            $this->getContainer()->get('form.factory'),
            $this->getContainer()->get('doctrine'),
            $this->getContainer()->get('oro_config.manager'),
            $this->websiteManager,
            $this->getContainer()->get('oro_user.manager')
        );
    }

    public function testGetData()
    {
        $this->websiteManager->expects($this->any())
            ->method('getCurrentWebsite')
            ->willReturn($this->getContainer()->get('oro_website.manager')->getDefaultWebsite());

        $actual = $this->dataProvider->getRegisterForm();

        $this->assertInstanceOf('\Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface', $actual);
        $this->assertEquals(FrontendAccountUserRegistrationType::NAME, $actual->getForm()->getName());
        $this->assertNotEmpty('oro_account_frontend_account_user_register', $actual->getAction()->getRouteName());
    }
}
