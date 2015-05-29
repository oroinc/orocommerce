<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;
use OroB2B\Bundle\PaymentBundle\Form\Type\PaymentTermType;

class PaymentTermTypeTest extends FormIntegrationTestCase
{
    /**
     * @var PaymentTermType
     */
    protected $formType;

    /**
     * @var PaymentTerm
     */
    protected $newPaymentTerm;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->newPaymentTerm = new PaymentTerm();
        $this->formType = new PaymentTermType(get_class($this->newPaymentTerm));
    }

    /**
     * @dataProvider submitDataProvider
     * @param array $submittedData
     */
    public function testSubmit(array $submittedData)
    {
        $form = $this->factory->create($this->formType, $this->newPaymentTerm);

        $form->submit($submittedData);

        $this->assertTrue($form->isValid());
        $expected = new PaymentTerm();
        $expected->setLabel($submittedData['label']);
        $this->assertEquals($expected, $form->getData());
    }

    public function testGetName()
    {
        $this->assertEquals(PaymentTermType::NAME, $this->formType->getName());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'submit' => [
                'submittedData' => [
                    'label' => 'net 10'
                ]
            ]
        ];
    }
}
