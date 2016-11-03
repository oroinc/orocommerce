<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\CustomerBundle\Form\Type\AccountUserPasswordResetType;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Oro\Component\Testing\Unit\EntityTrait;

use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Form\Type\AccountUserPasswordRequestType;
use Oro\Bundle\CustomerBundle\Layout\DataProvider\FrontendAccountUserFormProvider;

class FrontendAccountUserFormProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var FormFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $formFactory;

    /** @var FrontendAccountUserFormProvider */
    protected $provider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|UrlGeneratorInterface
     */
    protected $router;

    protected function setUp()
    {
        $this->router = $this->getMock('Symfony\Component\Routing\Generator\UrlGeneratorInterface');

        $this->formFactory = $this
            ->getMockBuilder('Symfony\Component\Form\FormFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->provider = new FrontendAccountUserFormProvider($this->formFactory, $this->router);
    }

    protected function tearDown()
    {
        unset($this->provider, $this->handler);
    }

    /**
     * @dataProvider getAccountUserFormProvider
     *
     * @param AccountUser $accountUser
     * @param string $route
     * @param array $routeParameters
     */
    public function testGetAccountUserForm(AccountUser $accountUser, $route, array $routeParameters = [])
    {
        $this->router->expects($this->exactly(2))
            ->method('generate')
            ->with($route, $routeParameters);

        $form = $this->assertAccountUserFormHandlerCalled();
        $actual = $this->provider->getAccountUserForm($accountUser);

        $this->assertInstanceOf(FormInterface::class, $actual);
        $this->assertSame($form, $actual);

        /** test local cache */
        $this->assertSame($actual, $this->provider->getAccountUserForm($accountUser));
    }

    public function testGetForgotPasswordForm()
    {
        $expectedForm = $this->getMock('Symfony\Component\Form\Test\FormInterface');

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(AccountUserPasswordRequestType::NAME)
            ->willReturn($expectedForm);

        // Get form without existing data in locale cache
        $data = $this->provider->getForgotPasswordForm();
        $this->assertInstanceOf(FormInterface::class, $data);

        // Get form with existing data in locale cache
        $data = $this->provider->getForgotPasswordForm();
        $this->assertInstanceOf(FormInterface::class, $data);
    }

    public function testGetResetPasswordForm()
    {
        $expectedForm = $this->getMock('Symfony\Component\Form\Test\FormInterface');

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(AccountUserPasswordResetType::NAME)
            ->willReturn($expectedForm);

        // Get form without existing data in locale cache
        $data = $this->provider->getResetPasswordForm();
        $this->assertInstanceOf(FormInterface::class, $data);

        // Get form with existing data in locale cache
        $data = $this->provider->getResetPasswordForm();
        $this->assertInstanceOf(FormInterface::class, $data);
    }

    /**
     * @dataProvider getProfileFormProvider
     *
     * @param AccountUser $accountUser
     * @param string $route
     * @param array $routeParameters
     */
    public function testGetProfileForm(AccountUser $accountUser, $route, array $routeParameters = [])
    {
        $this->router->expects($this->exactly(2))
            ->method('generate')
            ->with($route, $routeParameters);

        $form = $this->assertAccountUserProfileFormHandlerCalled();
        $actual = $this->provider->getProfileForm($accountUser);

        $this->assertInstanceOf(FormInterface::class, $actual);
        $this->assertSame($form, $actual);

        /** test local cache */
        $this->assertSame($actual, $this->provider->getProfileForm($accountUser));
    }

    /**
     * @return array
     */
    public function getAccountUserFormProvider()
    {
        return [
            [
                'accountUser' => $this->getEntity('Oro\Bundle\CustomerBundle\Entity\AccountUser'),
                'route' => 'oro_customer_frontend_account_user_create'
            ],
            [
                'accountUser' => $this->getEntity('Oro\Bundle\CustomerBundle\Entity\AccountUser', ['id' => 42]),
                'route' => 'oro_customer_frontend_account_user_update',
                'routeParameters' => ['id' => 42]
            ]
        ];
    }

    /**
     * @return array
     */
    public function getProfileFormProvider()
    {
        return [
            [
                'accountUser' => $this->getEntity('Oro\Bundle\CustomerBundle\Entity\AccountUser', ['id' => 42]),
                'route' => 'oro_customer_frontend_account_user_profile_update',
                'routeParameters' => ['id' => 42]
            ]
        ];
    }

    /**
     * @param string $method
     * @return \PHPUnit_Framework_MockObject_MockObject|FormInterface
     */
    protected function assertAccountUserFormHandlerCalled($method = 'TEST')
    {
        /** @var FormConfigInterface|\PHPUnit_Framework_MockObject_MockObject $config */
        $config = $this->getMock('Symfony\Component\Form\FormConfigInterface');
        $config->expects($this->any())
            ->method('getMethod')
            ->willReturn($method);

        /** @var FormView|\PHPUnit_Framework_MockObject_MockObject $config */
        $view = $this->getMock('Symfony\Component\Form\FormView');
        $view->vars = ['multipart' => null];
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $form->expects($this->any())
            ->method('getConfig')
            ->willReturn($config);
        $form->expects($this->any())
            ->method('createView')
            ->willReturn($view);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->willReturn($form);

        return $form;
    }

    /**
     * @param string $method
     * @return \PHPUnit_Framework_MockObject_MockObject|FormInterface
     */
    protected function assertAccountUserProfileFormHandlerCalled($method = 'TEST')
    {
        /** @var FormConfigInterface|\PHPUnit_Framework_MockObject_MockObject $config */
        $config = $this->getMock('Symfony\Component\Form\FormConfigInterface');
        $config->expects($this->any())
            ->method('getMethod')
            ->willReturn($method);

        /** @var FormView|\PHPUnit_Framework_MockObject_MockObject $config */
        $view = $this->getMock('Symfony\Component\Form\FormView');
        $view->vars = ['multipart' => null];
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $form->expects($this->any())
            ->method('getConfig')
            ->willReturn($config);
        $form->expects($this->any())
            ->method('createView')
            ->willReturn($view);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->willReturn($form);

        return $form;
    }
}
