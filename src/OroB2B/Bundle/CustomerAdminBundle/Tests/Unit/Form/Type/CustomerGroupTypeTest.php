<?php

namespace OroB2B\Bundle\CustomerAdminBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use OroB2B\Bundle\CustomerAdminBundle\Form\Type\CustomerGroupType;

class CustomerGroupTypeTest extends FormIntegrationTestCase
{
    /**
     * @var CustomerGroupType
     */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->formType = new CustomerGroupType();
    }

    protected function tearDown()
    {
        unset($this->em, $this->formType);
    }

    /**
     * @param array $options
     * @param mixed $defaultData
     * @param mixed $viewData
     * @param mixed $submittedData
     * @param mixed $expectedData
     * @dataProvider submitDataProvider
     */
    public function testSubmit(array $options, $defaultData, $viewData, $submittedData, $expectedData)
    {
        $form = $this->factory->create($this->formType, $defaultData, $options);

        $formConfig = $form->getConfig();
        $this->assertNull($formConfig->getOption('data_class'));

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
            'default' => [
                'options' => [],
                'defaultData' => [],
                'viewData' => [],
                'submittedData' => [
                    'name' => 'customer_group_name'
                ],
                'expectedData' => [
                    'name' => 'customer_group_name'
                ]
            ]
        ];
    }

    public function testGetName()
    {
        $this->assertInternalType('string', $this->formType->getName());
        $this->assertEquals('orob2b_customer_admin_customer_group_type', $this->formType->getName());
    }
}
