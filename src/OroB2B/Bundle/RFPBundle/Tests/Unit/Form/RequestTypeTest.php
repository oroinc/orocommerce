<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Form;

use OroB2B\Bundle\RFPBundle\Entity\Request;
use OroB2B\Bundle\RFPBundle\Form\RequestType;

use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;
use Symfony\Component\Validator\ConstraintViolationList;

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

        $this->formType = new RequestType();
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
                ,
                'isValid' => true
            ]
        ];
    }
}
