<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Form;

use OroB2B\Bundle\RFPBundle\Entity\RequestStatus;
use OroB2B\Bundle\RFPBundle\Form\RequestType;

use Symfony\Component\Form\FormEvents;

class RequestTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestType
     */
    protected $formType;

    /**
     * @var RequestStatus
     */
    protected $requestStatus;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {

        $this->requestStatus = new RequestStatus();

        $repository = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->any())
            ->method('findOneBy')
            ->with(['name' => 'open'])
            ->willReturn($this->requestStatus);

        $manager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $manager->expects($this->any())
            ->method('getRepository')
            ->with('OroB2BRFPBundle:RequestStatus')
            ->willReturn($repository);

        $registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->with('OroUserBundle:User')
            ->willReturn($manager);

        $registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->with('OroB2BRFPBundle:RequestStatus')
            ->willReturn($manager);

        $configManager = $this->getMockBuilder('Oro\Bundle\ApplicationBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $configManager->expects($this->any())
            ->method('get')
            ->with('oro_b2b_rfp_admin.default_request_status')
            ->willReturn('open');

        $this->formType = new RequestType($configManager, $registry);
    }

    /**
     * Test setDefaultOptions
     */
    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');

        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class' => 'OroB2B\Bundle\RFPBundle\Entity\Request'
                ]
            );

        $this->formType->setDefaultOptions($resolver);
    }

    /**
     * Test buildForm
     */
    public function testBuildForm()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilderInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $builder->expects($this->exactly(7))
            ->method('add')
            ->willReturnSelf();

        $builder->expects($this->at(0))
            ->method('add')
            ->with('firstName', 'text', [
                'label' => 'orob2b.rfp.request.first_name.label'
            ])
            ->willReturnSelf();

        $builder->expects($this->at(1))
            ->method('add')
            ->with('lastName', 'text', [
                'label' => 'orob2b.rfp.request.last_name.label'
            ])
            ->willReturnSelf();

        $builder->expects($this->at(2))
            ->method('add')
            ->with('email', 'text', [
                'label' => 'orob2b.rfp.request.email.label'
            ])
            ->willReturnSelf();

        $builder->expects($this->at(3))
            ->method('add')
            ->with('phone', 'text', [
                'label' => 'orob2b.rfp.request.phone.label'
            ])
            ->willReturnSelf();

        $builder->expects($this->at(4))
            ->method('add')
            ->with('company', 'text', [
                'label' => 'orob2b.rfp.request.company.label'
            ])
            ->willReturnSelf();

        $builder->expects($this->at(5))
            ->method('add')
            ->with('role', 'text', [
                'label' => 'orob2b.rfp.request.role.label'
            ])
            ->willReturnSelf();

        $builder->expects($this->at(6))
            ->method('add')
            ->with('body', 'textarea', [
                'label' => 'orob2b.rfp.request.body.label'
            ])
            ->willReturnSelf();

        $builder->expects($this->once())
            ->method('addEventListener')
            ->with(FormEvents::PRE_SUBMIT, [$this->formType, 'preSubmit']);

        $this->formType->buildForm($builder, []);
    }

    /**
     * Test preSubmit
     */
    public function testPreSubmit()
    {
        $form = $this->getMockBuilder('Symfony\Component\Form\FormInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $form->expects($this->once())
            ->method('add')
            ->with('status', 'entity', [
                'class' => 'OroB2B\Bundle\RFPBundle\Entity\RequestStatus',
                'data'  => $this->requestStatus
            ])
            ->willReturnSelf();

        $formEvent = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $formEvent->expects($this->once())
            ->method('getForm')
            ->willReturn($form);

        $this->formType->preSubmit($formEvent);
    }

    /**
     * Test getName
     */
    public function testGetName()
    {
        $this->assertEquals(RequestType::NAME, $this->formType->getName());
    }
}
