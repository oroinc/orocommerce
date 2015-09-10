<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Symfony\Component\Form\FormInterface;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\Testing\Unit\FormHandlerTestCase;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Form\Handler\AccountUserHandler;

class AccountUserHandlerTest extends FormHandlerTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\OroB2B\Bundle\AccountBundle\Entity\AccountUserManager
     */
    protected $userManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|FormInterface
     */
    protected $passwordGenerateForm;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|FormInterface
     */
    protected $sendEmailForm;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var AccountUser
     */
    protected $entity;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->entity = new AccountUser();

        $this->userManager = $this->getMockBuilder('OroB2B\Bundle\AccountBundle\Entity\AccountUserManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->passwordGenerateForm = $this->getMockBuilder('Symfony\Component\Form\FormInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->sendEmailForm = $this->getMockBuilder('Symfony\Component\Form\FormInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new AccountUserHandler(
            $this->form,
            $this->request,
            $this->userManager,
            $this->securityFacade
        );
    }

    public function testProcessUnsupportedRequest()
    {
        $this->request->setMethod('GET');

        $this->form->expects($this->never())
            ->method('submit');

        $this->assertFalse($this->handler->process($this->entity));
    }

    /**
     * {@inheritdoc}
     * @dataProvider supportedMethods
     */
    public function testProcessSupportedRequest($method, $isValid, $isProcessed)
    {
        $organization = null;
        if ($isValid) {
            $organization = new Organization();
            $organization->setName('test');

            $organizationToken =
                $this->getMock('Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface');
            $organizationToken->expects($this->any())
                ->method('getOrganizationContext')
                ->willReturn($organization);

            $this->securityFacade->expects($this->any())
                ->method('getToken')
                ->willReturn($organizationToken);

            $this->form->expects($this->at(2))
                ->method('get')
                ->with('passwordGenerate')
                ->will($this->returnValue($this->passwordGenerateForm));

            $this->form->expects($this->at(3))
                ->method('get')
                ->with('sendEmail')
                ->will($this->returnValue($this->sendEmailForm));

            $this->passwordGenerateForm->expects($this->once())
                ->method('getData')
                ->will($this->returnValue(false));

            $this->sendEmailForm->expects($this->once())
                ->method('getData')
                ->will($this->returnValue(false));
        }

        $this->form->expects($this->any())
            ->method('isValid')
            ->will($this->returnValue($isValid));

        $this->request->setMethod($method);

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);

        $this->assertEquals($isProcessed, $this->handler->process($this->entity));
        if ($organization) {
            $this->assertEquals($organization, $this->entity->getOrganization());
            $this->assertTrue($this->entity->hasOrganization($organization));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function testProcessValidData()
    {
        $this->request->setMethod('POST');

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);

        $this->form->expects($this->at(2))
            ->method('get')
            ->with('passwordGenerate')
            ->will($this->returnValue($this->passwordGenerateForm));

        $this->form->expects($this->at(3))
            ->method('get')
            ->with('sendEmail')
            ->will($this->returnValue($this->sendEmailForm));

        $this->passwordGenerateForm->expects($this->once())
            ->method('getData')
            ->will($this->returnValue(true));

        $this->sendEmailForm->expects($this->once())
            ->method('getData')
            ->will($this->returnValue(true));

        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        $this->assertTrue($this->handler->process($this->entity));
    }
}
