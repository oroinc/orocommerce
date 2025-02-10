<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CheckoutBundle\Form\Type\SaveAddressType;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SaveAddressTypeTest extends FormIntegrationTestCase
{
    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    #[\Override]
    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        parent::setUp();
    }

    #[\Override]
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [new SaveAddressType($this->authorizationChecker)],
                []
            )
        ];
    }

    public function testCreateByUserWithoutPermissions()
    {
        $this->authorizationChecker
            ->expects($this->any())
            ->method('isGranted')
            ->willReturn(false);

        $form = $this->factory->create(SaveAddressType::class);
        $this->assertInstanceOf(HiddenType::class, $form->getConfig()->getType()->getParent()->getInnerType());
        $this->assertEquals(0, $form->getConfig()->getData());
    }

    public function testCreateByUserWithPermissions()
    {
        $this->authorizationChecker
            ->expects($this->any())
            ->method('isGranted')
            ->with('CREATE', 'entity:' . CustomerUserAddress::class)
            ->willReturn(true);

        $form = $this->factory->create(SaveAddressType::class);
        $this->assertInstanceOf(CheckboxType::class, $form->getConfig()->getType()->getParent()->getInnerType());
    }
}
