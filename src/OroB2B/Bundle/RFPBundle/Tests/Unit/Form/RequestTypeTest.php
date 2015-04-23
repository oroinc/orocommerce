<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Form;

use Oro\Bundle\ApplicationBundle\Config\ConfigManager;

use OroB2B\Bundle\RFPBundle\Entity\Request;
use OroB2B\Bundle\RFPBundle\Entity\RequestStatus;
use OroB2B\Bundle\RFPBundle\Form\RequestType;

use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class RequestTypeTest extends FormIntegrationTestCase
{
    /**
     * @var RequestType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $requestStatus = new RequestStatus();

        $repository = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->any())
            ->method('findOneBy')
            ->with(['name' => 'open'])
            ->willReturn($requestStatus);

        $manager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $manager->expects($this->any())
            ->method('getRepository')
            ->with('OroB2BRFPBundle:RequestStatus')
            ->willReturn($repository);

        /**
         * @var \Doctrine\Common\Persistence\ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject $registry
         */
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

        /**
         * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject $configManager
         */
        $configManager = $this->getMockBuilder('Oro\Bundle\ApplicationBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $configManager->expects($this->any())
            ->method('get')
            ->with('oro_b2b_rfp_admin.default_request_status')
            ->willReturn('open');

        /**
         * @var \Symfony\Component\Validator\ValidatorInterface|\PHPUnit_Framework_MockObject_MockObject $validator
         */
        $validator = $this->getMock('\Symfony\Component\Validator\ValidatorInterface');

        $validator->expects($this->any())
            ->method('validate')
            ->will($this->returnValue(new ConstraintViolationList()));

        $this->factory = Forms::createFormFactoryBuilder()
            ->addExtensions($this->getExtensions())
            ->addTypeExtension(new FormTypeValidatorExtension($validator))
            ->getFormFactory();

        $this->formType = new RequestType($configManager, $registry);
    }

    /**
     * Test setDefaultOptions
     */
    public function testSetDefaultOptions()
    {
        /**
         * @var OptionsResolverInterface|\PHPUnit_Framework_MockObject_MockObject $resolver
         */
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
     * Test getName
     */
    public function testGetName()
    {
        $this->assertEquals(RequestType::NAME, $this->formType->getName());
    }

    /**
     * @param mixed $defaultData
     * @param mixed $viewData
     * @param mixed $submittedData
     * @param mixed $expectedData
     * @dataProvider submitDataProvider
     */
    public function testSubmit($defaultData, $viewData, $submittedData, $expectedData)
    {
        $form = $this->factory->create($this->formType, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($viewData, $form->getViewData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'new request' => [
                'defaultData' => new Request(),
                'viewData'    => new Request(),
                'submittedData' => [
                    'firstName' => 'Grzegorz',
                    'lastName'  => 'Brzeczyszczykiewicz',
                    'email'     => 'grzegorz@nsdap.de',
                    'phone'     => '+38 (044) 247-68-00',
                    'company'   => 'NSDAP',
                    'role'      => 'obersturmbannfuhrer',
                    'body'      => 'I wanna buy more Tiger I and Tiger II'
                ],
                'expectedData' => (new Request())
                    ->setFirstName('Grzegorz')
                    ->setLastName('Brzeczyszczykiewicz')
                    ->setEmail('grzegorz@nsdap.de')
                    ->setPhone('+38 (044) 247-68-00')
                    ->setCompany('NSDAP')
                    ->setRole('obersturmbannfuhrer')
                    ->setBody('I wanna buy more Tiger I and Tiger II')
                    ->setStatus(new RequestStatus())
            ]
        ];
    }
}