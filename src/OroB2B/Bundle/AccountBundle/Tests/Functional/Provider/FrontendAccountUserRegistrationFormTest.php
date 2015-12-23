<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Provider;

use Oro\Component\Layout\LayoutContext;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Form\Type\FrontendAccountUserRegistrationType;
use OroB2B\Bundle\AccountBundle\Provider\FrontendAccountUserRegistrationForm;

/**
 * @dbIsolation
 */
class FrontendAccountUserRegistrationFormTest extends WebTestCase
{
    /** @var LayoutContext */
    protected $context;

    /** @var FrontendAccountUserRegistrationForm */
    protected $dataProvider;

    protected function setUp()
    {
        $this->initClient();

        $this->context = new LayoutContext();
        $this->dataProvider = $this->getContainer()
            ->get('orob2b_account.provider.frontend_account_user_registration_form');
    }

    public function testGetIdentifier()
    {
        $this->assertEquals('orob2b_account_frontend_account_user_register', $this->dataProvider->getIdentifier());
    }

    public function testGetData()
    {
        $actual = $this->dataProvider->getData($this->context);

        $this->assertInstanceOf('\Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface', $actual);
        $this->assertEquals($this->dataProvider->getForm(), $actual->getForm());
        $this->assertEquals(FrontendAccountUserRegistrationType::NAME, $actual->getForm()->getName());
        $this->assertNotEmpty('orob2b_account_frontend_account_user_register', $actual->getAction()->getRouteName());
    }
}
