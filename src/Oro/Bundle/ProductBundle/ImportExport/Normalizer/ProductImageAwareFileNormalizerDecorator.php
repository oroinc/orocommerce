<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Normalizer;

use Oro\Bundle\AttachmentBundle\ImportExport\FileNormalizer;
use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DenormalizerInterface;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\NormalizerInterface;
use Oro\Bundle\ProductBundle\Entity\ProductImage;

/**
 * Adds corresponding changes to the handling of files during product image import.
 */
class ProductImageAwareFileNormalizerDecorator implements DenormalizerInterface, NormalizerInterface
{
    /** @var FileNormalizer */
    private $fileNormalizer;

    /** @var FileManager */
    private $fileManager;

    public function __construct(FileNormalizer $fileNormalizer, FileManager $fileManager)
    {
        $this->fileNormalizer = $fileNormalizer;
        $this->fileManager = $fileManager;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = []): bool
    {
        return $this->fileNormalizer->supportsDenormalization($data, $type, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        return $this->fileNormalizer->supportsNormalization($data, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $type, $format = null, array $context = [])
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

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $result = $this->fileNormalizer->normalize($object, $format, $context);

        if (isset($context['entityName']) && $context['entityName'] === ProductImage::class) {
            $result = $result['uri'];
        }

        return $result;
    }

    private function isRelativePath(string $path): bool
    {
        return
            false === strpos($path, '://')
            && !is_file($path);
    }
}
