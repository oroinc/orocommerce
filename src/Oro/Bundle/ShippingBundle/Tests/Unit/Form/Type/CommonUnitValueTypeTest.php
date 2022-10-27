<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ShippingBundle\Form\Type\CommonUnitValueType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class CommonUnitValueTypeTest extends FormIntegrationTestCase
{
    protected function setUp(): void
    {
        // In case the system localization does not converge with the test.
        \Locale::setDefault('en_US');
        parent::setUp();
    }

    public function testConfigure(): void
    {
        $form = $this->factory->create(CommonUnitValueType::class);
        $this->assertTrue($form->getConfig()->hasOption('scale'));
        $this->assertEquals(PHP_FLOAT_DIG, $form->getConfig()->getOption('scale'));

        $form = $this->factory->create(CommonUnitValueType::class, null, ['scale' => 20]);
        $this->assertEquals(20, $form->getConfig()->getOption('scale'));
    }

    public function testConfigureExceptions(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->factory->create(CommonUnitValueType::class, null, ['scale' => PHP_FLOAT_DIG - 1]);
        $this->factory->create(CommonUnitValueType::class, null, ['scale' => 'string']);
    }

    /**
     * @dataProvider formDataProvider
     */
    public function testSubmit($actual, $expected): void
    {
        $form = $this->factory->create(CommonUnitValueType::class, $actual);

        $form->submit($actual);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expected, $form->getViewData());
    }

    public function formDataProvider(): array
    {
        return [
            'integer' => [
                'actual' => 12345678901234,
                'expected' => '12345678901234'
            ],
            'integer with zeros' => [
                'actual' => 1000000000000,
                'expected' => '1000000000000'
            ],
            'float' => [
                'actual' => 1.1,
                'expected' => '1.1'
            ],
            'scientific notation float' => [
                'actual' => 6.00e-6, // real number 0.00000600
                'expected' => '0.000006'
            ],
        ];
    }
}
