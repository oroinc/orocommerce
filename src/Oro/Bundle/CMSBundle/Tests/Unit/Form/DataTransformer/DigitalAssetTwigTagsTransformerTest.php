<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\CMSBundle\Form\DataTransformer\DigitalAssetTwigTagsTransformer;
use Oro\Bundle\CMSBundle\Tools\DigitalAssetTwigTagsConverter;
use Symfony\Component\Form\Exception\TransformationFailedException;

class DigitalAssetTwigTagsTransformerTest extends \PHPUnit\Framework\TestCase
{
    private const CONTEXT = [
        'entityClass' => \stdClass::class,
        'entityId' => 42,
        'fieldName' => 'sampleField',
    ];

    private DigitalAssetTwigTagsConverter|\PHPUnit\Framework\MockObject\MockObject $digitalAssetTwigTagsConverter;

    private DigitalAssetTwigTagsTransformer $dataTransformer;

    protected function setUp(): void
    {
        $this->digitalAssetTwigTagsConverter = $this->createMock(DigitalAssetTwigTagsConverter::class);

        $this->dataTransformer = new DigitalAssetTwigTagsTransformer(
            $this->digitalAssetTwigTagsConverter,
            self::CONTEXT
        );
    }

    public function testTransform(): void
    {
        $value = 'test';
        $convertedValue = 'converted';

        $this->digitalAssetTwigTagsConverter->expects(self::once())
            ->method('convertToUrls')
            ->with($value, self::CONTEXT)
            ->willReturn($convertedValue);

        self::assertSame($convertedValue, $this->dataTransformer->transform($value));
    }

    public function testTransformForNullValue(): void
    {
        self::assertSame('', $this->dataTransformer->transform(null));
    }

    public function testTransformForEmptyStringValue(): void
    {
        self::assertSame('', $this->dataTransformer->transform(''));
    }

    public function testTransformForNotStringValue(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Expected a string.');

        $this->dataTransformer->transform(123);
    }

    public function testTransformWhenConvertFailed(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Failed to convert TWIG tags to URLs.');

        $this->digitalAssetTwigTagsConverter->expects(self::once())
            ->method('convertToUrls')
            ->willThrowException(new \Exception('some error'));

        $this->dataTransformer->transform('test');
    }

    public function testReverseTransform(): void
    {
        $value = 'test';
        $convertedValue = 'converted';

        $this->digitalAssetTwigTagsConverter->expects(self::once())
            ->method('convertToTwigTags')
            ->with($value, self::CONTEXT)
            ->willReturn($convertedValue);

        self::assertSame($convertedValue, $this->dataTransformer->reverseTransform($value));
    }

    public function testReverseTransformForEmptyStringValue(): void
    {
        self::assertNull($this->dataTransformer->reverseTransform(''));
    }

    public function testReverseTransformForNotStringValue(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Expected a string.');

        $this->dataTransformer->reverseTransform(123);
    }

    public function testReverseTransformWhenConvertFailed(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Failed to convert URLs to TWIG tags.');

        $this->digitalAssetTwigTagsConverter->expects(self::once())
            ->method('convertToTwigTags')
            ->willThrowException(new \Exception('some error'));

        $this->dataTransformer->reverseTransform('test');
    }
}
