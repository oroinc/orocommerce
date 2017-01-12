<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerUserPasswordRequestType;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerUserPasswordResetType;
use Oro\Bundle\CustomerBundle\Form\Type\FrontendOwnerSelectType;
use Oro\Bundle\CustomerBundle\Layout\DataProvider\FrontendCustomerUserFormProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FrontendCustomerUserFormProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var FormFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $formFactory;

    /** @var FrontendCustomerUserFormProvider */
    protected $provider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|UrlGeneratorInterface
     */
    protected $router;

    protected function setUp()
    {
        $this->router = $this->createMock('Symfony\Component\Routing\Generator\UrlGeneratorInterface');

        $this->formFactory = $this
            ->getMockBuilder('Symfony\Component\Form\FormFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->provider = new FrontendCustomerUserFormProvider($this->formFactory, $this->router);
    }

    protected function tearDown()
    {
        unset($this->provider, $this->handler);
    }

    /**
     * @dataProvider getCustomerUserFormProvider
     *
     * @param CustomerUser $customerUser
     * @param string $route
     * @param array $routeParameters
     */
    public function testGetCustomerUserFormView(CustomerUser $customerUser, $route, array $routeParameters = [])
    {
        $this->router->expects($this->exactly(2))
            ->method('generate')
            ->with($route, $routeParameters);

        $form = $this->assertCustomerUserFormHandlerCalled();
        $actual = $this->provider->getCustomerUserFormView($customerUser);

        $this->assertInstanceOf(FormView::class, $actual);
        $this->assertSame($form->createView(), $actual);

        /** test local cache */
        $this->assertSame($actual, $this->provider->getCustomerUserFormView($customerUser));
    }

    /**
     * @dataProvider getCustomerUserFormProvider
     *
     * @param CustomerUser $customerUser
     * @param string $route
     * @param array $routeParameters
     */
    public function testGetCustomerUserForm(CustomerUser $customerUser, $route, array $routeParameters = [])
    {
        $this->router->expects($this->exactly(2))
            ->method('generate')
            ->with($route, $routeParameters);

        $form = $this->assertCustomerUserFormHandlerCalled();
        $actual = $this->provider->getCustomerUserForm($customerUser);

        $this->assertInstanceOf(FormInterface::class, $actual);
        $this->assertSame($form, $actual);

        /** test local cache */
        $this->assertSame($actual, $this->provider->getCustomerUserForm($customerUser));
    }

    public function testGetForgotPasswordFormView()
    {
        $formView = $this->createMock(FormView::class);

        $expectedForm = $this->createMock('Symfony\Component\Form\Test\FormInterface');
        $expectedForm->expects($this->once())
            ->method('createView')
            ->willReturn($formView);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(CustomerUserPasswordRequestType::NAME)
            ->willReturn($expectedForm);

        // Get form without existing data in locale cache
        $data = $this->provider->getForgotPasswordFormView();
        $this->assertInstanceOf(FormView::class, $data);

        // Get form with existing data in locale cache
        $data = $this->provider->getForgotPasswordFormView();
        $this->assertInstanceOf(FormView::class, $data);
    }

    public function testGetForgotPasswordForm()
    {
        $expectedForm = $this->createMock('Symfony\Component\Form\Test\FormInterface');

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(CustomerUserPasswordRequestType::NAME)
            ->willReturn($expectedForm);

        // Get form without existing data in locale cache
        $data = $this->provider->getForgotPasswordForm();
        $this->assertInstanceOf(FormInterface::class, $data);

        // Get form with existing data in locale cache
        $data = $this->provider->getForgotPasswordForm();
        $this->assertInstanceOf(FormInterface::class, $data);
    }

    public function testGetResetPasswordFormView()
    {
        $formView = $this->createMock(FormView::class);

        $expectedForm = $this->createMock('Symfony\Component\Form\Test\FormInterface');
        $expectedForm->expects($this->once())
            ->method('createView')
            ->willReturn($formView);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(CustomerUserPasswordResetType::NAME)
            ->willReturn($expectedForm);

        // Get form without existing data in locale cache
        $data = $this->provider->getResetPasswordFormView();
        $this->assertInstanceOf(FormView::class, $data);

        // Get form with existing data in locale cache
        $data = $this->provider->getResetPasswordFormView();
        $this->assertInstanceOf(FormView::class, $data);
    }

    public function testGetResetPasswordForm()
    {
        $expectedForm = $this->createMock('Symfony\Component\Form\Test\FormInterface');

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(CustomerUserPasswordResetType::NAME)
            ->willReturn($expectedForm);

        // Get form without existing data in locale cache
        $data = $this->provider->getResetPasswordForm();
        $this->assertInstanceOf(FormInterface::class, $data);

        // Get form with existing data in locale cache
        $data = $this->provider->getResetPasswordForm();
        $this->assertInstanceOf(FormInterface::class, $data);
    }

    public function testGetCustomerUserSelectFormView()
    {
        $form = $this->createMock(FormInterface::class);
        $view = $this->createMock(FormView::class);

        $form->expects($this->once())
            ->method('createView')
            ->willReturn($view);

        $target = new \stdClass();
        $selectedCustomerUser = new CustomerUser();
        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(FrontendOwnerSelectType::NAME, $selectedCustomerUser, ['targetObject' => $target])
            ->willReturn($form);
        $this->assertSame($view, $this->provider->getCustomerUserSelectFormView($selectedCustomerUser, $target));
    }

    /**
     * @dataProvider getProfileFormProvider
     *
     * @param CustomerUser $customerUser
     * @param string $route
     * @param array $routeParameters
     */
    public function testGetProfileFormView(CustomerUser $customerUser, $route, array $routeParameters = [])
    {
        $this->router->expects($this->exactly(2))
            ->method('generate')
            ->with($route, $routeParameters);

        $form = $this->assertCustomerUserProfileFormHandlerCalled();
        $actual = $this->provider->getProfileFormView($customerUser);

        $this->assertInstanceOf(FormView::class, $actual);
        $this->assertSame($form->createView(), $actual);

        /** test local cache */
        $this->assertSame($actual, $this->provider->getProfileFormView($customerUser));
    }

    /**
     * @dataProvider getProfileFormProvider
     *
     * @param CustomerUser $customerUser
     * @param string $route
     * @param array $routeParameters
     */
    public function testGetProfileForm(CustomerUser $customerUser, $route, array $routeParameters = [])
    {
        $this->router->expects($this->exactly(2))
            ->method('generate')
            ->with($route, $routeParameters);

        $form = $this->assertCustomerUserProfileFormHandlerCalled();
        $actual = $this->provider->getProfileForm($customerUser);

        $this->assertInstanceOf(FormInterface::class, $actual);
        $this->assertSame($form, $actual);

        /** test local cache */
        $this->assertSame($actual, $this->provider->getProfileForm($customerUser));
    }

    /**
     * @return array
     */
    public function getCustomerUserFormProvider()
    {
        return [
            [
                'customerUser' => $this->getEntity('Oro\Bundle\CustomerBundle\Entity\CustomerUser'),
                'route' => 'oro_customer_frontend_customer_user_create'
            ],
            [
                'customerUser' => $this->getEntity('Oro\Bundle\CustomerBundle\Entity\CustomerUser', ['id' => 42]),
                'route' => 'oro_customer_frontend_customer_user_update',
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
                'customerUser' => $this->getEntity('Oro\Bundle\CustomerBundle\Entity\CustomerUser', ['id' => 42]),
                'route' => 'oro_customer_frontend_customer_user_profile_update',
                'routeParameters' => ['id' => 42]
            ]
        ];
    }

    /**
     * @param string $method
     * @return \PHPUnit_Framework_MockObject_MockObject|FormInterface
     */
    protected function assertCustomerUserFormHandlerCalled($method = 'TEST')
    {
        /** @var FormConfigInterface|\PHPUnit_Framework_MockObject_MockObject $config */
        $config = $this->createMock('Symfony\Component\Form\FormConfigInterface');
        $config->expects($this->any())
            ->method('getMethod')
            ->willReturn($method);

        /** @var FormView|\PHPUnit_Framework_MockObject_MockObject $config */
        $view = $this->createMock('Symfony\Component\Form\FormView');
        $view->vars = ['multipart' => null];
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock('Symfony\Component\Form\FormInterface');

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
    protected function assertCustomerUserProfileFormHandlerCalled($method = 'TEST')
    {
        /** @var FormConfigInterface|\PHPUnit_Framework_MockObject_MockObject $config */
        $config = $this->createMock('Symfony\Component\Form\FormConfigInterface');
        $config->expects($this->any())
            ->method('getMethod')
            ->willReturn($method);

        /** @var FormView|\PHPUnit_Framework_MockObject_MockObject $config */
        $view = $this->createMock('Symfony\Component\Form\FormView');
        $view->vars = ['multipart' => null];
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock('Symfony\Component\Form\FormInterface');

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
