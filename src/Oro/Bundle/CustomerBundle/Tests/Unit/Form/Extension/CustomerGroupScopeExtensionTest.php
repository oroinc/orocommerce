<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Form\Extension\CustomerGroupScopeExtension;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerGroupSelectType;
use Oro\Bundle\CustomerBundle\Tests\Unit\Form\Extension\Stub\CustomerGroupSelectTypeStub;
use Oro\Bundle\ScopeBundle\Form\Type\ScopeType;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

class CustomerGroupScopeExtensionTest extends FormIntegrationTestCase
{
    /**
     * @var CustomerGroupScopeExtension
     */
    protected $customerGroupScopeExtension;

    /**
     * @var ScopeManager|\PHPUnit_Framework_MockObject_MockObject $scopeManager
     */
    protected $scopeManager;

    protected function setUp()
    {
        $this->customerGroupScopeExtension = new CustomerGroupScopeExtension();

        parent::setUp();
    }

    public function testBuildForm()
    {
        $this->scopeManager->expects($this->once())
            ->method('getScopeEntities')
            ->with('web_content')
            ->willReturn(['customerGroup' => CustomerGroup::class]);

        $form = $this->factory->create(
            ScopeType::NAME,
            null,
            [ScopeType::SCOPE_TYPE_OPTION => 'web_content']
        );

        $this->assertTrue($form->has('customerGroup'));
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(ScopeType::class, $this->customerGroupScopeExtension->getExtendedType());
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
                    CustomerGroupSelectType::NAME => new CustomerGroupSelectTypeStub(),
                ],
                [
                    ScopeType::NAME => [$this->customerGroupScopeExtension],
                ]
            ),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }
}
