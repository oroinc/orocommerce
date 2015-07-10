<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use OroB2B\Bundle\RFPBundle\Form\Type\UserSelectType;

class UserSelectTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UserSelectType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry $registry */
        $registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new UserSelectType($registry);
    }

    /**
     * Test getName
     */
    public function testGetName()
    {
        $this->assertEquals(UserSelectType::NAME, $this->formType->getName());
    }

    /**
     * Test getParent
     */
    public function testGetParent()
    {
        $this->assertEquals('oro_user_select', $this->formType->getParent());
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
            ->method('setDefaults');

        $this->formType->setDefaultOptions($resolver);
    }
}
