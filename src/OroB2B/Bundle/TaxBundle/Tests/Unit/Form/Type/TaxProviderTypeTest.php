<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\TaxBundle\Form\Type\TaxProviderType;
use OroB2B\Bundle\TaxBundle\Provider\TaxProviderRegistry;

class TaxProviderTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TaxProviderType
     */
    protected $formType;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TaxProviderRegistry
     */
    protected $registry;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->registry = $this->getMockBuilder('OroB2B\Bundle\TaxBundle\Provider\TaxProviderRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new TaxProviderType($this->registry);
    }

    /**
     * @param string $name
     * @param string $label
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getProviderMock($name, $label)
    {
        $mock = $this->getMock('OroB2B\Bundle\TaxBundle\Provider\TaxProviderInterface');
        $mock->expects($this->once())->method('getName')->willReturn($name);
        $mock->expects($this->once())->method('getLabel')->willReturn($label);

        return $mock;
    }

    public function testConfigureOptions()
    {
        $choices = [
            $this->getProviderMock('name1', 'label1'),
            $this->getProviderMock('name2', 'label2'),
        ];

        $this->registry->expects($this->once())
            ->method('getProviders')
            ->willReturn($choices);

        $resolver = new OptionsResolver();

        $this->formType->configureOptions($resolver);

        $options = $resolver->resolve([]);
        $this->assertArrayHasKey('choices', $options);
        $this->assertEquals(['name1' => 'label1', 'name2' => 'label2'], $options['choices']);
    }

    public function testGetName()
    {
        $this->assertEquals(TaxProviderType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('choice', $this->formType->getParent());
    }
}
