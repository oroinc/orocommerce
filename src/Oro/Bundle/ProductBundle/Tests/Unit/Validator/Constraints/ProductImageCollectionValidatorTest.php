<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Helper\ProductImageHelper;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductImageCollection;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductImageCollectionValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductImageCollectionValidatorTest extends ConstraintValidatorTestCase
{
    /** @var ImageTypeProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $imageTypeProvider;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var ProductImageHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $productImageHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->constraint = new ProductImageCollection();
        $this->context = $this->createContext();
        $this->validator = $this->createValidator();
        $this->validator->initialize($this->context);
    }

    /**
     * @return ProductImageCollectionValidator
     */
    protected function createValidator()
    {
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->imageTypeProvider = $this->createMock(ImageTypeProvider::class);
        $this->imageTypeProvider->expects($this->any())
            ->method('getMaxNumberByType')
            ->willReturn(
                [
                    'main' => [
                        'max' => 1,
                        'label' => 'Main'
                    ],
                    'listing' => [
                        'max' => 1,
                        'label' => 'Listing'
                    ]
                ]
            );

        $this->productImageHelper = $this->createMock(ProductImageHelper::class);

        return new ProductImageCollectionValidator(
            $this->imageTypeProvider,
            $this->translator,
            $this->productImageHelper
        );
    }

    public function testValidateValidCollection()
    {
        $collection = new ArrayCollection(
            [
                $this->prepareProductImage(['main']),
            ]
        );

        $this->productImageHelper->expects($this->once())
            ->method('countImagesByType')
            ->willReturn(
                [
                    'main' => 1
                ]
            );

        $this->validator->validate($collection, $this->constraint);

        $this->assertNoViolation();
    }

    public function testValidateInvalidCollection()
    {
        $collection = new ArrayCollection(
            [
                $this->prepareProductImage(['main']),
                $this->prepareProductImage(['main'])
            ]
        );

        $this->productImageHelper->expects($this->once())
            ->method('countImagesByType')
            ->willReturn(
                [
                    'main' => 2
                ]
            );

        $this->translator->expects($this->once())
            ->method('trans')
            ->willReturn('Main');

        $this->validator->validate($collection, $this->constraint);

        $this->buildViolation('oro.product.product_image.type_restriction')
            ->setParameters(
                [
                    '%type%' => 'Main',
                    '%maxNumber%' => 1
                ]
            )
            ->assertRaised();
    }

    /**
     * @param $types
     * @return ProductImage
     */
    private function prepareProductImage($types)
    {
        $productImage = new ProductImage();
        foreach ($types as $type) {
            $productImage->addType($type);
        }

        return $productImage;
    }
}
