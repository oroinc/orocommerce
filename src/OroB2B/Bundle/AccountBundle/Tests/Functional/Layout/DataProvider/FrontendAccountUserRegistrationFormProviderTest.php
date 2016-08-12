<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Layout\DataProvider;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Form\Type\FrontendAccountUserRegistrationType;
use OroB2B\Bundle\AccountBundle\Layout\DataProvider\FrontendAccountUserRegistrationFormProvider;

/**
 * @dbIsolation
 */
class FrontendAccountUserRegistrationFormProviderTest extends WebTestCase
{
    /** @var FrontendAccountUserRegistrationFormProvider */
    protected $dataProvider;

    protected function setUp()
    {
        $this->initClient();

        $this->dataProvider = $this->getContainer()
            ->get('orob2b_account.provider.frontend_account_user_registration_form');
    }

    public function testGetData()
    {
        $actual = $this->dataProvider->getRegisterForm();

        $this->assertInstanceOf('\Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface', $actual);
        $this->assertEquals(FrontendAccountUserRegistrationType::NAME, $actual->getForm()->getName());
        $this->assertNotEmpty('orob2b_account_frontend_account_user_register', $actual->getAction()->getRouteName());
    }
}
