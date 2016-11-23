<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Form\PreloadedExtension;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityIdentifierType;
use Oro\Bundle\PaymentBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentBundle\Form\Type\PaymentTermType;

class PaymentTermTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    const ACCOUNT_CLASS = 'Oro\Bundle\CustomerBundle\Entity\Account';
    const ACCOUNT_GROUP_CLASS = 'Oro\Bundle\CustomerBundle\Entity\AccountGroup';

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
        $this->formType->setAccountClass(self::ACCOUNT_CLASS);
        $this->formType->setAccountGroupClass(self::ACCOUNT_GROUP_CLASS);
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $entityIdentifierType = new EntityIdentifierType(
            [
                1 => $this->getEntity(self::ACCOUNT_CLASS, ['id' => 1]),
                2 => $this->getEntity(self::ACCOUNT_CLASS, ['id' => 2]),
                3 => $this->getEntity(self::ACCOUNT_GROUP_CLASS, ['id' => 3]),
                4 => $this->getEntity(self::ACCOUNT_GROUP_CLASS, ['id' => 4])
            ]
        );

        return [
            new PreloadedExtension(
                [
                    $entityIdentifierType->getName() => $entityIdentifierType
                ],
                []
            )
        ];
    }

    /**
     * @dataProvider submitDataProvider
     * @param null|array $defaultData
     * @param array $submittedData
     * @param array $expectedData
     */
    public function testSubmit($defaultData, array $submittedData, array $expectedData)
    {
        if ($defaultData) {
            $existingPaymentTerm = new PaymentTerm();
            $class = new \ReflectionClass($existingPaymentTerm);
            $prop  = $class->getProperty('id');
            $prop->setAccessible(true);

            $prop->setValue($existingPaymentTerm, 42);
            $existingPaymentTerm->setLabel($defaultData['label']);

            $defaultData = $existingPaymentTerm;
        }

        $form = $this->factory->create($this->formType, $defaultData);

        $this->assertEquals($defaultData, $form->getData());
        if (isset($existingPaymentTerm)) {
            $this->assertEquals($existingPaymentTerm, $form->getViewData());
        } else {
            $this->assertNull($form->getViewData());
        }

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        /** @var PaymentTerm $result */
        $result = $form->getData();
        $this->assertEquals($expectedData['label'], $result->getLabel());
        $this->assertEquals($expectedData['appendAccounts'], $form->get('appendAccounts')->getData());
        $this->assertEquals($expectedData['removeAccounts'], $form->get('removeAccounts')->getData());
        $this->assertEquals($expectedData['appendAccountGroups'], $form->get('appendAccountGroups')->getData());
        $this->assertEquals($expectedData['removeAccountGroups'], $form->get('removeAccountGroups')->getData());
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
            'new payment term' => [
                'defaultData' => null,
                'submittedData' => [
                    'label' => 'Test Payment Term',
                    'appendAccounts' => [],
                    'removeAccounts' => [],
                    'appendAccountGroups' => [],
                    'removeAccountGroups' => [],
                ],
                'expectedData' => [
                    'label' => 'Test Payment Term',
                    'appendAccounts' => [],
                    'removeAccounts' => [],
                    'appendAccountGroups' => [],
                    'removeAccountGroups' => [],
                ]
            ],
            'update payment term' => [
                'defaultData' => [
                    'label' => 'Test Payment Term',
                    'appendAccounts' => [],
                    'removeAccounts' => [],
                    'appendAccountGroups' => [],
                    'removeAccountGroups' => [],
                    'appendWebsites' => [],
                    'removeWebsites' => [],
                ],
                'submittedData' => [
                    'label' => 'Test Payment Term Update',
                    'appendAccounts' => [1],
                    'removeAccounts' => [2],
                    'appendAccountGroups' => [3],
                    'removeAccountGroups' => [4],
                ],
                'expectedData' => [
                    'label' => 'Test Payment Term Update',
                    'appendAccounts' => [$this->getEntity(self::ACCOUNT_CLASS, ['id' => 1])],
                    'removeAccounts' => [$this->getEntity(self::ACCOUNT_CLASS, ['id' => 2])],
                    'appendAccountGroups' => [
                        $this->getEntity(self::ACCOUNT_GROUP_CLASS, ['id' => 3])
                    ],
                    'removeAccountGroups' => [
                        $this->getEntity(self::ACCOUNT_GROUP_CLASS, ['id' => 4])
                    ]
                ]
            ]
        ];
    }
}
