<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type;

use OroB2B\Bundle\AccountBundle\Form\Type\FrontendAccountUserRoleType;

class FrontendAccountUserRoleTypeTest extends AbstractAccountUserRoleTypeTest
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
        $expectedData,
        array $expectedFieldData = []
    ) {
        $form = $this->factory->create($this->formType, $defaultData, $options);

        $this->assertTrue($form->has('appendUsers'));
        $this->assertTrue($form->has('removeUsers'));
        $this->assertFalse($form->has('account'));

        $formConfig = $form->getConfig();
        $this->assertEquals(self::DATA_CLASS, $formConfig->getOption('data_class'));

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

        foreach ($expectedFieldData as $field => $data) {
            $this->assertTrue($form->has($field));
            $fieldForm = $form->get($field);
            $this->assertEquals($data, $fieldForm->getData());
        }
    }

    /**
     * @inheritdoc
     */
    public function testGetName()
    {
        $this->assertEquals(FrontendAccountUserRoleType::NAME, $this->formType->getName());
    }

    /**
     * @inheritdoc
     */
    protected function createAccountUserRoleFormTypeAndSetDataClass()
    {
        $this->formType = new FrontendAccountUserRoleType();
        $this->formType->setDataClass(self::DATA_CLASS);
    }
}
