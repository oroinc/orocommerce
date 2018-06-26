<?php

namespace Oro\Bundle\TaxBundle\ImportExport\Serializer;

use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DenormalizerInterface;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\NormalizerInterface;
use Oro\Bundle\TaxBundle\Entity\AbstractTaxCode;

/**
 * Serializer implementation for AbstractTaxCode instances
 */
class TaxCodeNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * @param AbstractTaxCode $object
     *
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if (!$object instanceof AbstractTaxCode) {
            return null;
        }

        if (!empty($context['mode']) && $context['mode'] === 'short') {
            return [
                'code' => $object->getCode()
            ];
        }

        return [
            'code' => $object->getCode(),
            'description' => $object->getDescription()
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        /** @var AbstractTaxCode $object */
        $object = new $class;
        if (!is_array($data)) {
            $data = ['code' => $data];
        }
        if (isset($data['code'])) {
            $object->setCode($data['code']);
        }
        if (isset($data['description'])) {
            $object->setDescription($data['description']);
        }

        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = [])
    {
        return is_a($type, AbstractTaxCode::class, true);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return $data instanceof AbstractTaxCode;
    }
}
