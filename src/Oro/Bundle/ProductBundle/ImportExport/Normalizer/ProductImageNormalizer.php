<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Normalizer;

use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\ConfigurableEntityNormalizer;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Symfony\Component\Config\FileLocator;

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

    /**
     * @var  string $productImageDir
     */
    protected $productImageDir;

    /**
     * @param ImageTypeProvider $imageTypeProvider
     */
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

    /**
     * @param FileLocator $fileLocator
     */
    public function setFileLocator(FileLocator $fileLocator)
    {
        $this->fileLocator = $fileLocator;
    }

    /**
     * @param string $productImageDir
     */
    public function setProductImageDir($productImageDir)
    {
        $this->productImageDir = $productImageDir;
    }

    /**
     * @param ProductImage $productImage
     *
     * {@inheritdoc}
     */
    public function normalize($productImage, $format = null, array $context = [])
    {
        $data = parent::normalize($productImage, $format, $context);

        if (array_key_exists('image', $data)) {
            $data['image']['name'] = $productImage->getImage()->getOriginalFileName();
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
        $data['image']['name'] = $productImage->getImage()->getOriginalFileName();

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($productImageData, $class, $format = null, array $context = [])
    {
        try {
            $imagePath = $this->fileLocator->locate(
                sprintf(
                    '%s%s',
                    $this->productImageDir,
                    $productImageData['image']['name']
                )
            );
        } catch (\Exception $e) {
            $imagePath = null;
        }

        $productImageData['image'] = is_array($imagePath) ? current($imagePath) : $imagePath;
        $imageTypes = $this->imageTypeProvider->getImageTypes();

        foreach ($productImageData['types'] as $type => $value) {
            if (!array_key_exists($type, $imageTypes) || !boolval($value)) {
                unset($imageTypes[$type]);
            }
        }

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
