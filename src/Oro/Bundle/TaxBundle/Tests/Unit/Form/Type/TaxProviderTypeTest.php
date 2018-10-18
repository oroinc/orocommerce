<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Oro\Bundle\TaxBundle\Form\Type\TaxProviderType;
use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaxProviderTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TaxProviderType
     */
    protected $formType;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TaxProviderRegistry
     */
    protected $registry;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->registry = $this->getMockBuilder('Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new TaxProviderType($this->registry);
    }

    /**
     * @param string $name
     * @param string $label
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getProviderMock($name, $label)
    {
        $mock = $this->createMock('Oro\Bundle\TaxBundle\Provider\TaxProviderInterface');
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
        $this->assertEquals(['label1' => 'name1', 'label2' => 'name2'], $options['choices']);
    }

    public function testGetParent()
    {
        $this->assertEquals(ChoiceType::class, $this->formType->getParent());
    }
}
