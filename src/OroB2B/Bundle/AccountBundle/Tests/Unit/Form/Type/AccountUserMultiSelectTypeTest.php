<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\UserBundle\Form\Type\UserMultiSelectType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;

use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserMultiSelectType;

class AccountUserMultiSelectTypeTest extends AbstractTest
{
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
                    'autocomplete_alias' => 'orob2b_account_account_user',
                    'configs' => [
                        'multiple' => true,
                        'component' => 'autocomplete-accountuser',
                        'placeholder' => 'orob2b.account.accountuser.form.choose',
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
     * @return array
     */
    public function submitProvider()
    {
        return [
            'empty data' => [
                'isValid'       => true,
                'submittedData' => [],
                'expectedData'  => [],
                'defaultData'   => [],
            ],
            'valid data' => [
                'isValid'       => true,
                'submittedData' => [2, 3],
                'expectedData'  => [
                    $this->getAccountUser(2),
                    $this->getAccountUser(3),
                ],
                'defaultData'   => [
                    $this->getAccountUser(1),
                ],
            ],
            'invalid data' => [
                'isValid'       => false,
                'submittedData' => [5],
                'expectedData'  => null,
                'defaultData'   => [
                    $this->getAccountUser(1),
                ],
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
            $this->getValidatorExtension(true),
        ];
    }
}
