<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Form\Type;

use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Form\Type\PaymentTermType;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class PaymentTermTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var PaymentTermType */
    private $formType;

    protected function setUp(): void
    {
        $this->formType = new PaymentTermType(PaymentTerm::class);
        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([$this->formType], [])
        ];
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(mixed $defaultData, array $submittedData, array $expectedData)
    {
        $form = $this->factory->create(PaymentTermType::class, $defaultData);

        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($defaultData, $form->getViewData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        /** @var PaymentTerm $result */
        $result = $form->getData();
        $this->assertEquals($expectedData['label'], $result->getLabel());
    }

    public function submitDataProvider(): array
    {
        return [
            'new payment term' => [
                'defaultData' => null,
                'submittedData' => [
                    'label' => 'Test Payment Term',
                ],
                'expectedData' => [
                    'label' => 'Test Payment Term',
                ],
            ],
            'update payment term' => [
                'defaultData' => $this->getEntity(PaymentTerm::class, ['id' => 1]),
                'submittedData' => [
                    'label' => 'Test Payment Term Update',
                ],
                'expectedData' => [
                    'label' => 'Test Payment Term Update',
                ],
            ],
        ];
    }
}
