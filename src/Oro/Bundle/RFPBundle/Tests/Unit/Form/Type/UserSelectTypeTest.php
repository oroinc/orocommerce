<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\RFPBundle\Form\Type\UserSelectType;
use Oro\Bundle\UserBundle\Form\Type\UserSelectType as BaseUserSelectType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserSelectTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var UserSelectType */
    private $formType;

    protected function setUp(): void
    {
        $this->formType = new UserSelectType($this->createMock(ManagerRegistry::class));
    }

    public function testGetParent()
    {
        $this->assertEquals(BaseUserSelectType::class, $this->formType->getParent());
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);

        $resolver->expects($this->once())
            ->method('setDefaults');

        $this->formType->configureOptions($resolver);
    }
}
