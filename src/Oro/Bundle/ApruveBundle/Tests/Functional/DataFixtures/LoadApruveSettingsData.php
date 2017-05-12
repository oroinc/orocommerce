<?php

namespace Oro\Bundle\ApruveBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ApruveBundle\Entity\ApruveSettings;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadApruveSettingsData extends AbstractFixture implements FixtureInterface, ContainerAwareInterface
{
    const APRUVE_LABEL = 'Apruve';
    const MERCHANT_ID = 'sampleMerchantId';
    const API_KEY = 'sampleApiKey';
    const WEBHOOK_TOKEN_1 = 'sampleToken_1';
    const WEBHOOK_TOKEN_2 = 'sampleToken_2';
    const WEBHOOK_TOKEN_3 = 'sampleToken_3';

    /**
     * @var array Transports configuration
     */
    const TRANSPORTS = [
        [
            'reference' => 'apruve:transport_1',
            'label' => self::APRUVE_LABEL,
            'short_label' => self::APRUVE_LABEL,
            'api_key' => self::API_KEY,
            'merchant_id' => self::MERCHANT_ID,
            'webhook_token' => self::WEBHOOK_TOKEN_1,
        ],
        [
            'reference' => 'apruve:transport_2',
            'label' => self::APRUVE_LABEL,
            'short_label' => self::APRUVE_LABEL,
            'api_key' => self::API_KEY,
            'merchant_id' => self::MERCHANT_ID,
            'webhook_token' => self::WEBHOOK_TOKEN_2,
        ],
        [
            'reference' => 'apruve:transport_3',
            'label' => self::APRUVE_LABEL,
            'short_label' => self::APRUVE_LABEL,
            'api_key' => self::API_KEY,
            'merchant_id' => self::MERCHANT_ID,
            'webhook_token' => self::WEBHOOK_TOKEN_3,
        ],
    ];

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::TRANSPORTS as $data) {
            $entity = new ApruveSettings();
            $entity->addLabel($this->createLocalizedValue($data['label']));
            $entity->addShortLabel($this->createLocalizedValue($data['short_label']));
            $entity->setApruveApiKey($this->encryptData($data['api_key']));
            $entity->setApruveMerchantId($this->encryptData($data['merchant_id']));
            $entity->setApruveWebhookToken($data['webhook_token']);
            $manager->persist($entity);
            $this->setReference($data['reference'], $entity);
        }
        $manager->flush();
    }

    /**
     * @param string $string
     *
     * @return LocalizedFallbackValue
     */
    private function createLocalizedValue($string)
    {
        return (new LocalizedFallbackValue())->setString($string);
    }

    /**
     * @param string $data
     *
     * @return string
     */
    private function encryptData($data)
    {
        return $this->container->get('oro_security.encoder.mcrypt')->encryptData($data);
    }
}
