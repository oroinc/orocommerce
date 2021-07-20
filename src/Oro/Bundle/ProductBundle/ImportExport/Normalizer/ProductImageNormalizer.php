<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Normalizer;

use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\ConfigurableEntityNormalizer;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Symfony\Component\Config\FileLocator;

/**
 * Import/export normalizer for ProductImage entities.
 */
class ProductImageNormalizer extends ConfigurableEntityNormalizer
{
    /**
     * @var string $productImageClass
     */
    protected $productImageClass;

    /**
     * @var  ImageTypeProvider $imageTypeProvider
     */
    protected $imageTypeProvider;

    /**
     * @var  FileLocator $fileLocator
     */
    protected $fileLocator;

    public function setImageTypeProvider(ImageTypeProvider $imageTypeProvider)
    {
        $this->imageTypeProvider = $imageTypeProvider;
    }

    /**
     * @param string $productImageClass
     */
    public function setProductImageClass($productImageClass)
    {
        $this->productImageClass = $productImageClass;
    }

    public function setFileLocator(FileLocator $fileLocator)
    {
        $this->fileLocator = $fileLocator;
    }

    /**
     * @param ProductImage $productImage
     *
     * {@inheritdoc}
     */
    public function normalize($productImage, $format = null, array $context = [])
    {
        $data = parent::normalize($productImage, $format, $context);

        $name = $productImage->getImage()->getOriginalFileName();
        if (!$name) {
            $name = $productImage->getImage()->getFilename();
        }

        if (array_key_exists('image', $data)) {
            $data['image']['name'] = $name;
        }

        if (!array_key_exists('types', $data)) {
            return $data;
        }

        $imageTypesKeys = array_keys($this->imageTypeProvider->getImageTypes());
        $availableTypesArray = array_fill_keys($imageTypesKeys, false);

        foreach ($data['types'] as $key => $type) {
            if (array_key_exists($type['type'], $availableTypesArray)) {
                $availableTypesArray[$type['type']] = true;
            }
        }

        $data['types'] = $availableTypesArray;
        $data['image']['name'] = $name;

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($productImageData, $class, $format = null, array $context = [])
    {
        $imageTypes = $this->imageTypeProvider->getImageTypes();
        foreach ($productImageData['types'] as $type => $value) {
            if (!array_key_exists($type, $imageTypes) || !boolval($value)) {
                unset($imageTypes[$type]);
            }
        }

        $imagePath = '';
        if (!empty($productImageData['image']['name'])) {
            $imagePath = $productImageData['image']['name'];
        }

        $productImageData['image'] = $imagePath;
        $productImageData['types'] = array_keys($imageTypes);

        return parent::denormalize($productImageData, $class, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return is_a($data, $this->productImageClass);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = [])
    {
        return is_a($type, $this->productImageClass, true);
    }
}
