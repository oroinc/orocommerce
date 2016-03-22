<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Form\Type;

use OroB2B\Bundle\PaymentBundle\EventListener\PaypalPasswordSubscriber;
use OroB2B\Bundle\PaymentBundle\Form\Type\PaypalPasswordType;

use OroB2B\Bundle\PaymentBundle\Tests\Unit\Form\Type\Stub\ParentPaypalPasswordTypeStub;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaypalPasswordTermTypeTest extends FormIntegrationTestCase
{
    /** @var PaypalPasswordType */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->formType = new PaypalPasswordType();
    }

    protected function tearDown()
    {
        unset($this->formType);
    }

    public function testConfigureOptions()
    {
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);

        $defaultOptions = $resolver->getDefinedOptions();
        $this->assertArrayNotHasKey('always_empty', $defaultOptions);
    }

    /**
     * @param string|string[] $expectedDefaultData
     * @param string|string[] $submittedData
     * @param string|string[] $expectedData
     * @param bool $withParent
     * @dataProvider submitProvider
     */
    public function testSubmit($expectedDefaultData, $submittedData, $expectedData, $withParent)
    {
        if ($withParent) {
            $formType = new ParentPaypalPasswordTypeStub($this->factory->createBuilder($this->formType));
        } else {
            $formType = $this->formType;
        }

        $form = $this->factory->create($formType);
        $this->assertEquals($expectedDefaultData, $form->getData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        $actualData = $form->getData();
        $this->assertEquals($expectedData, $actualData);
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        return [
            [
                'expectedDefaultData' => PaypalPasswordSubscriber::PASSWORD_PLACEHOLDER,
                'submittedData' => 'new password',
                'expectedData' => 'new password',
                'withParent' => false,
            ],
            [
                'expectedDefaultData' => PaypalPasswordSubscriber::PASSWORD_PLACEHOLDER,
                'submittedData' => PaypalPasswordSubscriber::PASSWORD_PLACEHOLDER,
                'expectedData' => PaypalPasswordSubscriber::PASSWORD_PLACEHOLDER,
                'withParent' => false,
            ],
            [
                'expectedDefaultData' => null,
                'submittedData' => [PaypalPasswordType::NAME => PaypalPasswordSubscriber::PASSWORD_PLACEHOLDER],
                'expectedData' => [],
                'withParent' => true,
            ],
            [
                'expectedDefaultData' => null,
                'submittedData' => [PaypalPasswordType::NAME => 'new password'],
                'expectedData' => [PaypalPasswordType::NAME => 'new password'],
                'withParent' => true,
            ],
        ];
    }

    public function testGetName()
    {
        $this->assertEquals('orob2b_payment_paypal_password_type', $this->formType->getName());
    }
}
