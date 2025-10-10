<?php

namespace Oro\Bundle\TaxBundle\ImportExport\Serializer;

use Oro\Bundle\TaxBundle\Entity\AbstractTaxCode;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Serializer implementation for AbstractTaxCode instances
 */
class TaxCodeNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * @param AbstractTaxCode $object
     *
     */
    #[\Override]
    public function normalize(
        mixed $object,
        ?string $format = null,
        array $context = []
    ): float|int|bool|\ArrayObject|array|string|null {
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

    #[\Override]
    public function denormalize($data, string $type, ?string $format = null, array $context = []): mixed
    {
        /** @var AbstractTaxCode $object */
        $object = new $type();
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

    #[\Override]
    public function supportsDenormalization($data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_a($type, AbstractTaxCode::class, true);
    }

    #[\Override]
    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof AbstractTaxCode;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [AbstractTaxCode::class => true];
    }
}
