<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use OroB2B\Bundle\TaxBundle\Form\Type\TaxProviderType;

class TaxProviderTypeTest extends \PHPUnit_Framework_TestCase
{
    const TAX_PROVIDER_CLASS = 'OroB2B\Bundle\TaxBundle\Entity\TaxProvider';

    /**
     * @var TaxProviderType
     */
    protected $formType;

    /**
     * @var \OroB2B\Bundle\TaxBundle\Entity\TaxProvider[]
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

        /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry $registry */
        $registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new TaxProviderType($registry);
        $this->formType->setTaxProviderClass(self::TAX_PROVIDER_CLASS);
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
