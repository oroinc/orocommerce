<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AddressBundle\Tests\Unit\Form\Type\AddressFormExtensionTestCase;

abstract class AbstractAddressTestCase extends AddressFormExtensionTestCase
{
    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(
        bool $isValid,
        mixed $defaultData,
        mixed $viewData,
        array $submittedData,
        array $expectedData
    ) {
        $form = $this->factory->create($this->getFormTypeClass(), $defaultData);

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

    abstract public function submitDataProvider(): array;

    abstract protected function getFormTypeClass(): string;
}
