<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\RFPBundle\Form\Type\UserSelectType;
use Oro\Bundle\UserBundle\Form\Type\UserSelectType as BaseUserSelectType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserSelectTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var UserSelectType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry $registry */
        $registry = $this->getMockBuilder('Doctrine\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new UserSelectType($registry);
    }

    /**
     * Test getParent
     */
    public function testGetParent()
    {
        $this->assertEquals(BaseUserSelectType::class, $this->formType->getParent());
    }

    /**
     * Test configureOptions
     */
    public function testConfigureOptions()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|OptionsResolver $resolver */
        $resolver = $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $resolver->expects($this->once())
            ->method('setDefaults');

        $this->formType->configureOptions($resolver);
    }
}
