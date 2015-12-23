<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use OroB2B\Bundle\TaxBundle\Form\Type\TaxProviderType;
use OroB2B\Bundle\TaxBundle\Provider\TaxProviderInterface;
use OroB2B\Bundle\TaxBundle\Provider\TaxProviderRegistry;

class TaxProviderTypeTest extends \PHPUnit_Framework_TestCase
{
    const TAX_PROVIDER_CLASS = 'OroB2B\Bundle\TaxBundle\Provider\TaxProviderInterface';

    /**
     * @var TaxProviderType
     */
    protected $formType;

    /**
     * @var TaxProviderInterface[]
     */
    protected $choices;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->choices = [
            $this->getMock(self::TAX_PROVIDER_CLASS),
            $this->getMock(self::TAX_PROVIDER_CLASS),
        ];

        /** @var \PHPUnit_Framework_MockObject_MockObject|TaxProviderRegistry $registry */
        $registry = $this->getMockBuilder('OroB2B\Bundle\TaxBundle\Provider\TaxProviderRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $registry->expects($this->any())
            ->method('getProviders')
            ->willReturn($this->choices);

        $this->formType = new TaxProviderType($registry);
    }

    /**
     * Test setDefaultOptions
     */
    public function testSetDefaultOptions()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|OptionsResolverInterface $resolver */
        $resolver = $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolverInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $resolver->expects($this->once())
            ->method('setDefaults')
            ->withAnyParameters();

        $this->formType->setDefaultOptions($resolver);
    }

    /**
     * Test getName
     */
    public function testGetName()
    {
        $this->assertEquals(TaxProviderType::NAME, $this->formType->getName());
    }

    /**
     * Test getParent
     */
    public function testGetParent()
    {
        $this->assertEquals('choice', $this->formType->getParent());
    }
}
