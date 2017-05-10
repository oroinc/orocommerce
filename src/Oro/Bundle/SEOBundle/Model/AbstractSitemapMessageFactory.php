<?php

namespace Oro\Bundle\SEOBundle\Model;

use Oro\Bundle\SEOBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Component\Website\WebsiteInterface;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractSitemapMessageFactory
{
    const WEBSITE_ID = 'websiteId';
    const VERSION = 'version';

    /**
     * @var WebsiteRepository
     */
    protected $websiteRepository;

    /**
     * @var OptionsResolver
     */
    protected $resolver;

    /**
     * @param WebsiteRepository $websiteRepository
     */
    public function __construct(WebsiteRepository $websiteRepository)
    {
        $this->websiteRepository = $websiteRepository;
    }

    /**
     * @param array $data
     * @return WebsiteInterface
     */
    public function getWebsiteFromMessage(array $data)
    {
        $data = $this->getResolvedData($data);

        return $this->websiteRepository->find($data[self::WEBSITE_ID]);
    }

    /**
     * @param array $data
     * @return int
     */
    public function getVersionFromMessage(array $data)
    {
        $data = $this->getResolvedData($data);

        return $data[self::VERSION];
    }

    /**
     * @return OptionsResolver
     */
    protected function getOptionsResolver()
    {
        if (null === $this->resolver) {
            $this->resolver = $this->configureOptions();
        }

        return $this->resolver;
    }

    /**
     * @param array|string $data
     * @return array
     */
    protected function getResolvedData($data)
    {
        try {
            return $this->getOptionsResolver()->resolve($data);
        } catch (ExceptionInterface $e) {
            throw new InvalidArgumentException($e->getMessage());
        }
    }

    /**
     * @return OptionsResolver
     */
    protected function configureOptions()
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired([
            self::WEBSITE_ID,
            self::VERSION,
        ]);

        $resolver->setAllowedTypes(self::WEBSITE_ID, ['int']);
        $resolver->setAllowedTypes(self::VERSION, ['int']);

        $resolver->setNormalizer(self::WEBSITE_ID, function (Options $options, $websiteId) {
            if (!$this->websiteRepository->checkWebsiteExists($websiteId)) {
                throw new InvalidArgumentException(sprintf('No website exists with id "%d"', $websiteId));
            }

            return $websiteId;
        });

        return $resolver;
    }
}
