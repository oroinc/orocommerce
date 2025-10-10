<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Normalizer;

use Oro\Bundle\AttachmentBundle\ImportExport\FileNormalizer;
use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Adds corresponding changes to the handling of files during product image import.
 */
class ProductImageAwareFileNormalizerDecorator implements
    DenormalizerInterface,
    NormalizerInterface
{
    private FileNormalizer $fileNormalizer;

    private FileManager $fileManager;

    public function __construct(FileNormalizer $fileNormalizer, FileManager $fileManager)
    {
        $this->fileNormalizer = $fileNormalizer;
        $this->fileManager = $fileManager;
    }

    #[\Override]
    public function supportsDenormalization($data, string $type, ?string $format = null, array $context = []): bool
    {
        return $this->fileNormalizer->supportsDenormalization($data, $type, $format, $context);
    }

    #[\Override]
    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return $this->fileNormalizer->supportsNormalization($data, $format, $context);
    }

    #[\Override]
    public function denormalize($data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (isset($context['entityName']) && $context['entityName'] === ProductImage::class) {
            $path = $data;
            if ($path && $this->isRelativePath($path)) {
                $path = $this->fileManager->getReadonlyFilePath($path);
            }
            $data = ['uri' => $path, 'uuid' => ''];
        }

        return $this->fileNormalizer->denormalize($data, $type, $format, $context);
    }

    #[\Override]
    public function normalize(
        mixed $object,
        ?string $format = null,
        array $context = []
    ): float|int|bool|\ArrayObject|array|string|null {
        $result = $this->fileNormalizer->normalize($object, $format, $context);

        if (isset($context['entityName']) && $context['entityName'] === ProductImage::class) {
            $result = $result['uri'];
        }

        return $result;
    }

    private function isRelativePath(string $path): bool
    {
        return
            !str_contains($path, '://')
            && !is_file($path);
    }

    public function getSupportedTypes(?string $format): array
    {
        return $this->fileNormalizer->getSupportedTypes($format);
    }
}
