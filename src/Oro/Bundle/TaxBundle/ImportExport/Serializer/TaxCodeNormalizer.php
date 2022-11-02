<?php

namespace Oro\Bundle\TaxBundle\ImportExport\Serializer;

use Oro\Bundle\TaxBundle\Entity\AbstractTaxCode;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;

/**
 * Serializer implementation for AbstractTaxCode instances
 */
class TaxCodeNormalizer implements ContextAwareNormalizerInterface, ContextAwareDenormalizerInterface
{
    /**
     * @param AbstractTaxCode $object
     *
     * {@inheritdoc}
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        if (!$object instanceof AbstractTaxCode) {
            return null;
        }

        if (!empty($context['mode']) && $context['mode'] === 'short') {
            return [
                'code' => $object->getCode(),
            ];
        }

        return [
            'code' => $object->getCode(),
            'description' => $object->getDescription(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        /** @var AbstractTaxCode $object */
        $object = new $type;
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
    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        return is_a($type, AbstractTaxCode::class, true);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof AbstractTaxCode;
    }
}
