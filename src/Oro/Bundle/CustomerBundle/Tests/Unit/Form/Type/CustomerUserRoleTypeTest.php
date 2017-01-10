<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CustomerBundle\Form\Type\CustomerUserRoleType;

class CustomerUserRoleTypeTest extends AbstractCustomerUserRoleTypeTest
{
    /**
     * @inheritdoc
     * @dataProvider submitDataProvider
     */
    public function testSubmit(
        array $options,
        $defaultData,
        $viewData,
        array $submittedData,
        $expectedData
    ) {
        $form = $this->factory->create($this->formType, $defaultData, $options);

        $this->assertTrue($form->has('appendUsers'));
        $this->assertTrue($form->has('removeUsers'));
        $this->assertTrue($form->has('account'));
        $this->assertTrue($form->has('selfManaged'));

        $formConfig = $form->getConfig();
        $this->assertEquals(self::DATA_CLASS, $formConfig->getOption('data_class'));

        $this->assertFalse($formConfig->getOption('hide_self_managed'));

        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($viewData, $form->getViewData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        $actualData = $form->getData();
        $this->assertEquals($expectedData, $actualData);

        if ($defaultData && $defaultData->getRole()) {
            $this->assertEquals($expectedData->getRole(), $actualData->getRole());
        } else {
            $this->assertNotEmpty($actualData->getRole());
        }
    }

    /**
     * @inheritdoc
     */
    public function testGetName()
    {
        $this->assertEquals(CustomerUserRoleType::NAME, $this->formType->getName());
    }

    /**
     * Create form type
     */
    protected function createCustomerUserRoleFormTypeAndSetDataClass()
    {
        $this->formType = new CustomerUserRoleType();
        $this->formType->setDataClass(static::DATA_CLASS);
    }
}
