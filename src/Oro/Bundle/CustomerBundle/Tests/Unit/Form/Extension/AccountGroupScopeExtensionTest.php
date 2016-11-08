<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\CustomerBundle\Form\Extension\AccountGroupScopeExtension;
use Oro\Bundle\CustomerBundle\Form\Type\AccountGroupSelectType;
use Oro\Bundle\CustomerBundle\Tests\Unit\Form\Extension\Stub\AccountGroupSelectTypeStub;
use Oro\Bundle\ScopeBundle\Form\Type\ScopeType;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

class AccountGroupScopeExtensionTest extends FormIntegrationTestCase
{
    /**
     * @var AccountGroupScopeExtension
     */
    protected $accountGroupScopeExtension;

    /**
     * @var ScopeManager|\PHPUnit_Framework_MockObject_MockObject $scopeManager
     */
    protected $scopeManager;

    protected function setUp()
    {
        $this->accountGroupScopeExtension = new AccountGroupScopeExtension();

        parent::setUp();
    }

    public function testBuildForm()
    {
        $this->scopeManager->expects($this->once())
            ->method('getScopeEntities')
            ->with('web_content')
            ->willReturn(['accountGroup' => AccountGroup::class]);

        $form = $this->factory->create(
            'oro_scope',
            null,
            ['scope_type' => 'web_content']
        );

        $this->assertTrue($form->has('accountGroup'));
    }

    public function testGetExtendedType()
    {
        $this->assertEquals('oro_scope', $this->accountGroupScopeExtension->getExtendedType());
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $this->scopeManager = $this->getMockBuilder(ScopeManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        return [
            new PreloadedExtension(
                [
                    ScopeType::NAME => new ScopeType($this->scopeManager),
                    AccountGroupSelectType::NAME => new AccountGroupSelectTypeStub(),
                ],
                [
                    ScopeType::NAME => [$this->accountGroupScopeExtension],
                ]
            ),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }
}
