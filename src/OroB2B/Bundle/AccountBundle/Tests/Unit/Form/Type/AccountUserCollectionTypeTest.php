<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserCollectionType;

class AccountUserCollectionTypeTest extends \PHPUnit_Framework_TestCase
{
    const CLASS_USER_ENTITY = 'OroB2B\Bundle\AccountBundle\Entity\AccountUser';

    /** @var UserCollectionType */
    protected $formType;

    protected function setUp()
    {
        $this->formType = new AccountUserCollectionType();
        $this->formType->setDataClass(self::CLASS_USER_ENTITY);
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
                    'required' => false,
                    'class'  => self::CLASS_USER_ENTITY,
                    'property' => 'fullName',
                    'multiple' => true,
                    'attr' => [
                        'class' => 'account-accountuser-collection',
                    ],
                ]
            );

        $this->formType->setDefaultOptions($resolver);
    }

    public function testGetParent()
    {
        $this->assertEquals('genemu_jqueryselect2_entity', $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals(AccountUserCollectionType::NAME, $this->formType->getName());
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider buildViewProvider
     */
    public function testBuildView(array $inputData, array $expectedData)
    {
        $formView = new FormView();
        $formView->vars = $inputData;

        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $this->formType->buildView($formView, $form, []);

        $this->assertEquals($expectedData, $formView->vars['attr']);
    }

    /**
     * @return array
     */
    public function buildViewProvider()
    {
        return [
            'empty choices' => [
                'input' => [
                    'choices' => [],
                ],
                'expected' => [
                    'data-items' => json_encode([]),
                ],
            ],
            'few choices' => [
                'input' => [
                    'choices' => [
                        $this->getChoiceView(1, 'value1', 'label1'),
                        $this->getChoiceView(1, 'value2', 'label2'),
                        $this->getChoiceView(2, 'value3', 'label3'),
                    ],
                ],
                'expected' => [
                    'data-items' => json_encode([
                        'value1' => ['value' => 'value1', 'label' => 'label1', 'account' => 1],
                        'value2' => ['value' => 'value2', 'label' => 'label2', 'account' => 1],
                        'value3' => ['value' => 'value3', 'label' => 'label3', 'account' => 2],
                    ]),
                ],
            ],
        ];
    }

    /**
     * @param int $accountId
     * @param string $value
     * @param string $label
     * @return ChoiceView
     */
    protected function getChoiceView($accountId, $value, $label)
    {
        /* @var $account Account|\PHPUnit_Framework_MockObject_MockObject */
        $account = $this->getMock('OroB2B\Bundle\AccountBundle\Entity\Account');
        $account->expects($this->any())
            ->method('getId')
            ->willReturn($accountId);

        /* @var $accountUser AccountUser|\PHPUnit_Framework_MockObject_MockObject */
        $accountUser = $this->getMock('OroB2B\Bundle\AccountBundle\Entity\AccountUser');
        $accountUser->expects($this->any())
            ->method('getAccount')
            ->willReturn($account);

        return new ChoiceView($accountUser, $value, $label);
    }
}
