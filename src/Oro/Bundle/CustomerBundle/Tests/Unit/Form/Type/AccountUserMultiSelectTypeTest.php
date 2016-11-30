<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\UserBundle\Form\Type\UserMultiSelectType;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Form\Type\AccountUserMultiSelectType;

class AccountUserMultiSelectTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var AccountUserMultiSelectType */
    protected $formType;

    protected function setUp()
    {
        $this->formType = new AccountUserMultiSelectType();

        parent::setUp();
    }

    protected function tearDown()
    {
        unset($this->formType);
    }

    public function testSetDefaultOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'autocomplete_alias' => 'oro_account_account_user',
                    'configs' => [
                        'multiple' => true,
                        'component' => 'autocomplete-accountuser',
                        'placeholder' => 'oro.customer.accountuser.form.choose',
                    ],
                    'attr' => [
                        'class' => 'account-accountuser-multiselect',
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
        $this->assertEquals(AccountUserMultiSelectType::NAME, $this->formType->getName());
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
                'defaultData' => [$this->getAccountUser(1)],
                'submittedData' => [2, 3],
                'isValid' => true,
                'expectedData' => [$this->getAccountUser(2), $this->getAccountUser(3)]
            ],
            'invalid data' => [
                'defaultData' => [$this->getAccountUser(1)],
                'submittedData' => [5]
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $accountUserSelectType = new EntityType(
            [
                1 => $this->getAccountUser(1),
                2 => $this->getAccountUser(2),
                3 => $this->getAccountUser(3),
            ],
            UserMultiSelectType::NAME,
            [
                'multiple' => true,
            ]
        );
        return [
            new PreloadedExtension(
                [
                    $accountUserSelectType->getName() => $accountUserSelectType,
                ],
                []
            ),
            $this->getValidatorExtension(false),
        ];
    }

    /**
     * @param int $id
     * @return AccountUser
     */
    protected function getAccountUser($id)
    {
        return $this->getEntity('Oro\Bundle\CustomerBundle\Entity\AccountUser', ['id' => $id, 'salt' => $id]);
    }
}
