<?php

namespace Oro\Bundle\SEOBundle\Model;

use Oro\Bundle\SEOBundle\Sitemap\Provider\UrlItemsProviderRegistry;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\Website\WebsiteInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SitemapMessageFactory extends AbstractSitemapMessageFactory
{
    const TYPE = 'type';
    const JOB_ID = 'jobId';

    /**
     * @var UrlItemsProviderRegistry
     */
    private $providerRegistry;

    /**
     * @param UrlItemsProviderRegistry $providerRegistry
     */
    public function setProviderRegistry($providerRegistry)
    {
        $this->providerRegistry = $providerRegistry;
    }

    /**
     * @param WebsiteInterface $website
     * @param string $type
     * @param int $version
     * @param Job $job
     * @return array
     */
    public function createMessage(WebsiteInterface $website, $type, $version, Job $job)
    {
        return $this->getResolvedData(
            [
                self::WEBSITE_ID => $website->getId(),
                self::TYPE => $type,
                self::VERSION => $version,
                self::JOB_ID => $job->getId(),
            ]
        );
    }

    /**
     * @param array $data
     * @return string
     */
    public function getTypeFromMessage(array $data)
    {
        $data = $this->getResolvedData($data);

        return $data[self::TYPE];
    }

    /**
     * @param array $data
     * @return int
     */
    public function getJobIdFromMessage(array $data)
    {
        $data = $this->getResolvedData($data);

        return $data[self::JOB_ID];
    }

    /**
     * @return OptionsResolver
     */
    protected function configureOptions()
    {
        $resolver = parent::configureOptions();

        $resolver->setRequired([
            self::TYPE,
            self::JOB_ID,
        ]);

        $resolver->setAllowedTypes(self::TYPE, ['string']);
        $resolver->setAllowedTypes(self::JOB_ID, ['int']);

        $resolver->setAllowedValues(
            self::TYPE,
            $this->providerRegistry->getProviderNames()
        );

        return $resolver;
    }
}
