<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormView;

use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\CustomerBundle\Form\Type\ParentCustomerSelectType;

class ParentCustomerSelectTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ParentCustomerSelectType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new ParentCustomerSelectType();
    }

    public function testGetName()
    {
        $this->assertEquals(ParentCustomerSelectType::NAME, $this->type->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('oro_jqueryselect2_hidden', $this->type->getParent());
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'))
            ->willReturnCallback(
                function (array $options) {
                    $this->assertArrayHasKey('autocomplete_alias', $options);
                    $this->assertArrayHasKey('configs', $options);
                    $this->assertEquals('orob2b_customer_parent', $options['autocomplete_alias']);
                    $this->assertEquals(
                        [
                            'extra_config' => 'parent_aware',
                            'placeholder' => 'orob2b.customer.form.choose_parent'
                        ],
                        $options['configs']
                    );
                }
            );

        $this->type->setDefaultOptions($resolver);
    }

    /**
     * @param object|null $parentData
     * @param int|null $expectedParentId
     * @dataProvider buildViewDataProvider
     */
    public function testBuildView($parentData, $expectedParentId)
    {
        $parentForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $parentForm->expects($this->any())
            ->method('getData')
            ->willReturn($parentData);

        $formView = new FormView();

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->any())
            ->method('getParent')
            ->willReturn($parentForm);

        $this->type->buildView($formView, $form, []);

        $this->assertArrayHasKey('parent_id', $formView->vars);
        $this->assertEquals($expectedParentId, $formView->vars['parent_id']);
    }

    /**
     * @return array
     */
    public function buildViewDataProvider()
    {
        $customerId = 42;
        $customer = new Customer();

        $reflection = new \ReflectionProperty(get_class($customer), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($customer, $customerId);

        return [
            'without customer' => [
                'parentData' => null,
                'expectedParentId' => null,
            ],
            'with customer' => [
                'parentData' => $customer,
                'expectedParentId' => $customerId,
            ],
        ];
    }
}
