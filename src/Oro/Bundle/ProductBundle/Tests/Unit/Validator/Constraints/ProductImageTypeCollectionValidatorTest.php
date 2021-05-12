<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\LayoutBundle\Model\ThemeImageType;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductImageTypeCollection;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductImageTypeCollectionValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductImageTypeCollectionValidatorTest extends ConstraintValidatorTestCase
{
    /** @var ImageTypeProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $imageTypeProvider;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->constraint = new ProductImageTypeCollection();
        $this->context = $this->createContext();
        $this->validator = $this->createValidator();
        $this->validator->initialize($this->context);
    }

    /**
     * @return ProductImageTypeCollectionValidator
     */
    protected function createValidator()
    {
        $this->imageTypeProvider = $this->createMock(ImageTypeProvider::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        return new ProductImageTypeCollectionValidator(
            $this->imageTypeProvider,
            $this->translator
        );
    }

    public function testValidateValidCollection()
    {
        $value = new ArrayCollection(
            [
                new ProductImageType('main'),
            ]
        );

        $this->imageTypeProvider->expects($this->once())
            ->method('getImageTypes')
            ->willReturn(
                [
                    'main' => new ThemeImageType('main', 'Main', [])
                ]
            );

        $this->validator->validate($value, $this->constraint);

        $this->assertNoViolation();
    }

    public function testValidateInvalidCollection()
    {
        $value = new ArrayCollection(
            [
                new ProductImageType('main'),
                new ProductImageType('main'),
            ]
        );

        $this->imageTypeProvider->expects($this->once())
            ->method('getImageTypes')
            ->willReturn(
                [
                    'main' => new ThemeImageType('main', 'Main', [])
                ]
            );

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('Main')
            ->willReturn('Main');

        $this->validator->validate($value, $this->constraint);

        $this->buildViolation('oro.product.product_image_type.type_restriction')
            ->setParameters(
                [
                    '%type%' => 'Main',
                ]
            )
            ->assertRaised();
    }
}
