<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\CMSBundle\Entity\ImageSlide;
use Oro\Bundle\CMSBundle\Validator\Constraints\HasAtLeastOneSizeImage;
use Oro\Bundle\CMSBundle\Validator\Constraints\HasAtLeastOneSizeImageValidator;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Constraints\IsNull;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class HasAtLeastOneSizeImageValidatorTest extends ConstraintValidatorTestCase
{
    private MockObject|TranslatorInterface $translator;

    #[\Override]
    protected function createValidator()
    {
        $this->translator = $this->createMock(TranslatorInterface::class);

        return new HasAtLeastOneSizeImageValidator($this->translator);
    }

    public function testValidateUnsupportedConstraint(): void
    {
        $constraint = new IsNull();

        $this->expectExceptionObject(
            new UnexpectedTypeException($constraint, HasAtLeastOneSizeImage::class)
        );

        $this->validator->validate(new \stdClass(), $constraint);
    }

    public function testValidateUnsupportedClass(): void
    {
        $value = new \stdClass();

        $this->expectExceptionObject(new UnexpectedValueException($value, ImageSlide::class));

        $constraint = new HasAtLeastOneSizeImage();
        $this->validator->validate($value, $constraint);
    }

    public function testValidateUnsupportedWhenContentWidgetNull(): void
    {
        $constraint = new HasAtLeastOneSizeImage();

        $this->validator->validate(null, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateNoViolations(): void
    {
        $constraint = new HasAtLeastOneSizeImage();

        $imageSlide = new \Oro\Bundle\CMSBundle\Tests\Unit\Entity\Stub\ImageSlide();
        $imageSlide
            ->setExtraLargeImage($this->getFile(1001))
            ->setLargeImage2x($this->getFile(2002))
            ->setMediumImage3x($this->getFile(3003))
            ->setSmallImage($this->getFile(4001));

        $this->validator->validate($imageSlide, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateViolations(): void
    {
        $constraint = new HasAtLeastOneSizeImage();
        $this->translator
            ->expects(self::exactly(6))
            ->method('trans')
            ->willReturnCallback(fn ($key) => str_replace('oro.cms.imageslide.', '', $key));

        $imageSlide = new \Oro\Bundle\CMSBundle\Tests\Unit\Entity\Stub\ImageSlide();
        $imageSlide
            ->setLargeImage2x($this->getFile(2002))
            ->setSmallImage($this->getFile(4001));

        $this->validator->validate($imageSlide, $constraint);

        $this
            ->buildViolation('oro.cms.image_slider.image.has_at_least_one_size_image.message')
            ->setParameter(
                '{{ fields }}',
                'extra_large_image.label, extra_large_image2x.label, extra_large_image3x.label'
            )
            ->atPath('property.path.extraLargeImage')
            ->setCode(NotBlank::IS_BLANK_ERROR)
            ->buildNextViolation('oro.cms.image_slider.image.has_at_least_one_size_image.message')
            ->setParameter(
                '{{ fields }}',
                'medium_image.label, medium_image2x.label, medium_image3x.label'
            )
            ->atPath('property.path.mediumImage')
            ->setCode(NotBlank::IS_BLANK_ERROR)
            ->assertRaised();
    }

    private function getFile(int $id): File
    {
        $file = new File();
        ReflectionUtil::setId($file, $id);

        return $file;
    }
}
