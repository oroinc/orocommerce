<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider;

use Oro\Bundle\CheckoutBundle\Handler\RegistrationHandler;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserManager;
use Oro\Bundle\CustomerBundle\Form\Handler\FrontendCustomerUserHandler;
use Oro\Bundle\CustomerBundle\Layout\DataProvider\FrontendCustomerUserRegistrationFormProvider;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

class RegistrationHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RegistrationHandler
     */
    private $registrationHandler;

    /**
     * @var FrontendCustomerUserRegistrationFormProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $registrationFormProvider;

    /**
     * @var CustomerUserManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerUserManager;

    /**
     * @var FrontendCustomerUserHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerUserHandler;

    /**
     * @var UpdateHandlerFacade|\PHPUnit_Framework_MockObject_MockObject
     */
    private $updateHandlerFacade;

    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $translator;

    protected function setUp()
    {
        $this->registrationFormProvider = $this->createMock(FrontendCustomerUserRegistrationFormProvider::class);
        $this->customerUserManager = $this->createMock(CustomerUserManager::class);
        $this->customerUserHandler = $this->createMock(FrontendCustomerUserHandler::class);
        $this->updateHandlerFacade = $this->createMock(UpdateHandlerFacade::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->registrationHandler = new RegistrationHandler(
            $this->registrationFormProvider,
            $this->customerUserManager,
            $this->customerUserHandler,
            $this->updateHandlerFacade,
            $this->translator
        );
    }

    public function testHandleRegistrationWithGetMethod()
    {
        $request = new Request();
        $request->setMethod(Request::METHOD_GET);
        $this->assertFalse($this->registrationHandler->handle($request));
    }

    public function testHandleRegistrationWithoutParameter()
    {
        $request = new Request();
        $request->setMethod(Request::METHOD_POST);
        $this->assertFalse($this->registrationHandler->handle($request));
    }

    public function testHandleRegistrationWithoutRegisterForm()
    {
        $request = new Request();
        $request->setMethod(Request::METHOD_POST);
        $request->query->add(['isRegistration' => true]);
        $this->assertFalse($this->registrationHandler->handle($request));
    }

    /**
     * @param bool $isConfirmationRequired
     * @param string $registrationMessage
     * @param bool $isFormSubmitted
     * @param int $isFormValidExpectedCount
     * @param bool $isFormValid
     * @param bool $expectedResult
     * @dataProvider getHandleRegistrationUpdateDataProvider
     */
    public function testHandleRegistrationUpdate(
        $isConfirmationRequired,
        $registrationMessage,
        $isFormSubmitted,
        $isFormValidExpectedCount,
        $isFormValid,
        $expectedResult
    ) {
        $request = new Request();
        $request->setMethod(Request::METHOD_POST);
        $request->query->add(['isRegistration' => true]);

        $formData = new \stdClass();
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('getData')
            ->willReturn($formData);
        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn($isFormSubmitted);
        $form->expects($this->exactly($isFormValidExpectedCount))
            ->method('isValid')
            ->willReturn($isFormValid);
        $this->registrationFormProvider->expects($this->once())
            ->method('getRegisterForm')
            ->willReturn($form);

        $this->customerUserManager->expects($this->once())
            ->method('isConfirmationRequired')
            ->willReturn($isConfirmationRequired);

        $translatedMessage = $registrationMessage . '_translated';
        $this->translator->expects($this->once())
            ->method('trans')
            ->willReturn($translatedMessage);

        $this->updateHandlerFacade->expects($this->once())
            ->method('update')
            ->with(
                $formData,
                $form,
                $translatedMessage,
                $request,
                $this->customerUserHandler
            );

        $this->assertEquals($expectedResult, $this->registrationHandler->handle($request));
    }

    /**
     * @return array
     */
    public function getHandleRegistrationUpdateDataProvider()
    {
        return [
          'not submitted form' => [
              'isConfirmationRequired' => false,
              'registrationMessage' => 'oro.customer.controller.customeruser.registered.message',
              'isFormSubmitted' => false,
              'isFormValidExpectedCount' => 0,
              'isFormValid' => false,
              'expectedResult' => false
          ],
          'submitted and not valid form' => [
              'isConfirmationRequired' => true,
              'registrationMessage' => 'oro.customer.controller.customeruser.registered_with_confirmation.message',
              'isFormSubmitted' => true,
              'isFormValidExpectedCount' => 1,
              'isFormValid' => false,
              'expectedResult' => false
          ],
          'submitted and valid form' => [
              'isConfirmationRequired' => true,
              'registrationMessage' => 'oro.customer.controller.customeruser.registered_with_confirmation.message',
              'isFormSubmitted' => true,
              'isFormValidExpectedCount' => 1,
              'isFormValid' => true,
              'expectedResult' => true
          ]
        ];
    }
}
