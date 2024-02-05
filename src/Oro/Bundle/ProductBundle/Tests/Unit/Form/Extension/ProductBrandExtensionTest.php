<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\ProductBundle\Form\Extension\ProductBrandExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ProductBrandExtensionTest extends TestCase
{
    private ProductBrandExtension $brandExtension;
    private FormBuilderInterface|MockObject $formBuilder;
    private AuthorizationCheckerInterface|MockObject $authorizationChecker;

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->formBuilder = $this->createMock(FormBuilderInterface::class);
        $this->brandExtension = new ProductBrandExtension(
            $this->authorizationChecker
        );
    }

    public function testBuildForm(): void
    {
        $this->formBuilder
            ->expects(self::exactly(2))
            ->method('addEventListener')
            ->willReturnSelf();

        $this->brandExtension->buildForm($this->formBuilder, []);
    }
}
