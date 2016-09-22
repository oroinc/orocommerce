<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountUserRole;
use Oro\Bundle\CustomerBundle\Form\Type\AccountUserRoleSelectType;

class AccountUserRoleSelectTypeTest extends FormIntegrationTestCase
{
    const ROLE_CLASS = 'Oro\Bundle\CustomerBundle\Entity\AccountUserRole';

    /** @var  AccountUserRoleSelectType */
    protected $formType;

    /** @var string */
    protected $roleClass;

    public function setUp()
    {
        parent::setUp();
        $translator = $this->createTranslator();
        $this->formType = new AccountUserRoleSelectType($translator);
        $this->formType->setRoleClass(self::ROLE_CLASS);
    }

    public function tearDown()
    {
        unset($this->formType);
        parent::tearDown();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $entityType = new EntityType([]);

        return [
            new PreloadedExtension(
                [
                    $entityType->getName() => $entityType
                ],
                []
            )
        ];
    }

    public function testDefaultOptions()
    {
        $form = $this->factory->create($this->formType);

        $expectedOptions = [
            'class' => self::ROLE_CLASS,
            'multiple' => true,
            'expanded' => true,
            'required' => true,
        ];

        $formOptions = $form->getConfig()->getOptions();

        // @todo Uncomment when phpunit >=4.4.0
        // $this->assertArraySubset($expectedOptions, $formOptions);
        $this->assertArrayHasKey('choice_label', $formOptions);
        $this->assertInternalType('callable', $formOptions['choice_label']);

        $roleWithoutAccount = new AccountUserRole();
        $roleWithoutAccount->setLabel('roleWithoutAccount');
        $this->assertEquals(
            'roleWithoutAccount (oro.customer.accountuserrole.type.predefined.label.trans)',
            $formOptions['choice_label']($roleWithoutAccount)
        );

        $account = new Account();
        $roleWithAccount = new AccountUserRole();
        $roleWithAccount->setAccount($account);
        $roleWithAccount->setLabel('roleWithAccount');
        $this->assertEquals(
            'roleWithAccount (oro.customer.accountuserrole.type.customizable.label.trans)',
            $formOptions['choice_label']($roleWithAccount)
        );

        $testEntity = new Account();
        $testEntity->setName('TestEntityValue');
        $this->assertEquals('TestEntityValue', $formOptions['choice_label']($testEntity));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    private function createTranslator()
    {
        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                function ($message) {
                    return $message . '.trans';
                }
            );

        return $translator;
    }
}
