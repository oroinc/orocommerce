<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\UserBundle\Form\Type\UserMultiSelectType;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerUserMultiSelectType;

class CustomerUserMultiSelectTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var CustomerUserMultiSelectType */
    protected $formType;

    protected function setUp()
    {
        $this->formType = new CustomerUserMultiSelectType();

        parent::setUp();
    }

    protected function tearDown()
    {
        unset($this->formType);
    }

    public function testSetDefaultOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolver */
        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'autocomplete_alias' => 'oro_customer_customer_user',
                    'configs' => [
                        'multiple' => true,
                        'component' => 'autocomplete-customeruser',
                        'placeholder' => 'oro.customer.customeruser.form.choose',
                    ],
                    'attr' => [
                        'class' => 'customer-customeruser-multiselect',
                    ],
                ]
            );

        $this->formType->setDefaultOptions($resolver);
    }

    public function testGetParent()
    {
        $this->assertEquals(UserMultiSelectType::NAME, $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals(CustomerUserMultiSelectType::NAME, $this->formType->getName());
    }

    /**
     * @dataProvider submitProvider
     *
     * @param array $defaultData
     * @param array $submittedData
     * @param bool $isValid
     * @param array|null $expectedData
     */
    public function testSubmit(array $defaultData, array $submittedData, $isValid = false, $expectedData = null)
    {
        $form = $this->factory->create($this->formType, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);

        $this->assertEquals($isValid, $form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        return [
            'empty data' => [
                'defaultData' => [],
                'submittedData' => [],
                'isValid' => true,
                'expectedData' => []
            ],
            'valid data' => [
                'defaultData' => [$this->getCustomerUser(1)],
                'submittedData' => [2, 3],
                'isValid' => true,
                'expectedData' => [$this->getCustomerUser(2), $this->getCustomerUser(3)]
            ],
            'invalid data' => [
                'defaultData' => [$this->getCustomerUser(1)],
                'submittedData' => [5]
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $customerUserSelectType = new EntityType(
            [
                1 => $this->getCustomerUser(1),
                2 => $this->getCustomerUser(2),
                3 => $this->getCustomerUser(3),
            ],
            UserMultiSelectType::NAME,
            [
                'multiple' => true,
            ]
        );
        return [
            new PreloadedExtension(
                [
                    $customerUserSelectType->getName() => $customerUserSelectType,
                ],
                []
            ),
            $this->getValidatorExtension(false),
        ];
    }

    /**
     * @param int $id
     * @return CustomerUser
     */
    protected function getCustomerUser($id)
    {
        return $this->getEntity('Oro\Bundle\CustomerBundle\Entity\CustomerUser', ['id' => $id, 'salt' => $id]);
    }
}
