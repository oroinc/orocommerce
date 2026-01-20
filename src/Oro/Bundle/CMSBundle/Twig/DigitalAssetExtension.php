<?php

namespace Oro\Bundle\CMSBundle\Twig;

use Oro\Bundle\ApiBundle\Provider\ApiUrlResolver;
use Oro\Bundle\AttachmentBundle\Exception\FileNotFoundException;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlByUuidProvider;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to work with digital assets in wysiwyg fields:
 *   - wysiwyg_image
 *   - wysiwyg_file
 */
class DigitalAssetExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private ContainerInterface $container;
    private ?ApiUrlResolver $apiUrlResolver = null;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('wysiwyg_image', [$this, 'getWysiwygImageUrl']),
            new TwigFunction('wysiwyg_file', [$this, 'getWysiwygFileUrl']),
        ];
    }

    public function getWysiwygImageUrl(
        int $digitalAssetId,
        string $fileUuid,
        string $filterName = 'wysiwyg_original',
        string $format = '',
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        try {
            // Only override referenceType if it was not explicitly passed
            $args = func_get_args();
            $effectiveReferenceType = isset($args[4])
                ? $referenceType
                : $this->getEffectiveReferenceType($referenceType);

            return $this->getFileUrlByUuidProvider()->getFilteredImageUrl(
                $fileUuid,
                $filterName,
                $format,
                $effectiveReferenceType
            );
        } catch (FileNotFoundException $e) {
            return '';
        }
    }

    public function getWysiwygFileUrl(
        int $digitalAssetId,
        string $fileUuid,
        string $action = FileUrlProviderInterface::FILE_ACTION_DOWNLOAD,
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        try {
            // Only override referenceType if it was not explicitly passed
            $args = func_get_args();
            $effectiveReferenceType = isset($args[3])
                ? $referenceType
                : $this->getEffectiveReferenceType($referenceType);

            return $this->getFileUrlByUuidProvider()->getFileUrl(
                $fileUuid,
                $action,
                $effectiveReferenceType
            );
        } catch (FileNotFoundException $e) {
            return '';
        }
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            FileUrlByUuidProvider::class,
            'oro_api.provider.api_url_resolver' => ApiUrlResolver::class,
        ];
    }

    private function getFileUrlByUuidProvider(): FileUrlByUuidProvider
    {
        return $this->container->get(FileUrlByUuidProvider::class);
    }

    private function getApiUrlResolver(): ApiUrlResolver
    {
        if (null === $this->apiUrlResolver) {
            $this->apiUrlResolver = $this->container->get('oro_api.provider.api_url_resolver');
        }

        return $this->apiUrlResolver;
    }

    private function getEffectiveReferenceType(int $referenceType): int
    {
        return $this->getApiUrlResolver()->getEffectiveReferenceType($referenceType);
    }
}
