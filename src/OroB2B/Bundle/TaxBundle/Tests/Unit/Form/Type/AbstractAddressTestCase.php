<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\AbstractType;

use Oro\Component\Testing\Unit\AddressFormExtensionTestCase;

abstract class AbstractAddressTestCase extends AddressFormExtensionTestCase
{
    /**
     * @dataProvider submitDataProvider
     * @param bool $isValid
     * @param mixed $defaultData
     * @param mixed $viewData
     * @param array $submittedData
     * @param array $expectedData
     */
    public function testSubmit(
        $isValid,
        $defaultData,
        $viewData,
        array $submittedData,
        $expectedData
    ) {
        $form = $this->factory->create($this->getFormType(), $defaultData);

        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($viewData, $form->getViewData());

        $form->submit($submittedData);
        $this->assertEquals($isValid, $form->isValid());

        foreach ($expectedData as $field => $data) {
            $this->assertTrue($form->has($field));
            $fieldForm = $form->get($field);
            $this->assertEquals($data, $fieldForm->getData());
        }
    }

    /**
     * @return array
     */
    abstract public function submitDataProvider();

    /**
     * @return AbstractType
     */
    abstract protected function getFormType();
}
