<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Tests\Functional\Translation;

use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyProvider;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadStrategyLanguages;
use Oro\Bundle\TranslationBundle\Tests\Functional\Stub\Strategy\TranslationStrategy;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Translation\MessageCatalogueInterface;

/**
 * @dbIsolationPerTest
 */
class TranslatorTest extends FrontendWebTestCase
{
    /** @var TranslationStrategyProvider */
    private $provider;

    /** @var string */
    private $cacheDir;

    /** @var Translator */
    private $translator;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadStrategyLanguages::class]);

        $this->translator = $this->getContainer()->get('translator');

        $this->provider = new TranslationStrategyProvider([$this->createStrategy()]);
        $this->translator->setStrategyProvider($this->provider);

        $this->cacheDir = $this->getContainer()->getParameter('kernel.cache_dir').DIRECTORY_SEPARATOR.'translations';
    }

    /**
     * This test is just a preparation step for the next one - testTransWithDifferentLocaleAfterEnLocale().
     *
     * Deletes all translation caches, makes translator load and create cache for only one locale.
     *
     * @return array
     */
    public function testGetCatalogueWhenNoCacheExists()
    {
        $key = uniqid('TRANSLATION_KEY_', true);
        $enValue = uniqid('TEST_VALUE1_', true);

        /** @var $manager TranslationManager */
        $manager = $this->getContainer()->get('oro_translation.manager.translation');
        $manager->saveTranslation(
            $key,
            $enValue,
            'en',
            TranslationManager::DEFAULT_DOMAIN,
            Translation::SCOPE_INSTALLED
        );
        $manager->flush();
        $manager->clear();

        // Delete all translation caches.
        $filesystem = new Filesystem();
        if ($filesystem->exists($this->cacheDir)) {
            $filesystem->remove($this->cacheDir);
        }

        if (!$filesystem->exists($this->cacheDir)) {
            $filesystem->mkdir($this->cacheDir);
        }

        $iterator = new \FilesystemIterator($this->cacheDir);
        $this->assertFalse($iterator->valid(), 'Translation cache must be empty');

        // This will initialize translation cache for en locale.
        $catalogue = $this->translator->getCatalogue('en');

        // Check that translation cache does not contain any other locales.
        foreach ($iterator as $file) {
            $locale = explode('.', $file->getFilename())[1];
            $this->assertEquals('en', $locale, 'Translation cache must contain only en locale');
        }

        $this->assertInstanceOf(MessageCatalogueInterface::class, $catalogue);
        $this->assertGreaterThan(0, \count($catalogue->all()));

        return [$key, $enValue];
    }

    /**
     * Tests that translator is capable of loading new translation catalogue when translation cache for it does not
     * exist.
     *
     * @depends testGetCatalogueWhenNoCacheExists
     * @ticket BB-14803
     */
    public function testTransWithDifferentLocaleAfterSomeLocaleIsLoadedFromCache(array $data)
    {
        [$key, $enValue] = $data;
        $lang1Value = uniqid('TEST_VALUE1_', true);

        /** @var $manager TranslationManager */
        $manager = $this->getContainer()->get('oro_translation.manager.translation');
        $manager->saveTranslation(
            $key,
            $lang1Value,
            'lang1',
            TranslationManager::DEFAULT_DOMAIN,
            Translation::SCOPE_INSTALLED
        );
        $manager->flush();
        $manager->clear();

        // Load en locale from cache.
        $this->translator->getCatalogue('en');

        // Check that translator returns correct translation for locale without translation cache.
        $this->assertEquals(
            $enValue,
            $this->translator->trans($key, [], TranslationManager::DEFAULT_DOMAIN, 'en'),
            'Translator must be able to load old EN catalogue and return correct EN translation.'
        );

        $this->assertEquals(
            $lang1Value,
            $this->translator->trans($key, [], TranslationManager::DEFAULT_DOMAIN, 'lang1'),
            'Translator must be able to load new LANG1 catalogue and return correct LANG1 translation.'
        );

        $this->assertEquals(
            $lang1Value,
            $this->translator->trans($key, [], TranslationManager::DEFAULT_DOMAIN, 'lang2'),
            'Translator must be able to load new LANG2 catalogue and return correct LANG1 translation.'
        );

        $this->assertEquals(
            $enValue,
            $this->translator->trans($key, [], TranslationManager::DEFAULT_DOMAIN, 'lang3'),
            'Translator must be able to load new LANG3 catalogue and return correct EN translation.'
        );

        $this->assertEquals(
            $enValue,
            $this->translator->trans($key, [], TranslationManager::DEFAULT_DOMAIN, 'lang4'),
            'Translator must be able to load new LANG4 catalogue and return correct EN translation.'
        );
    }

    private function createStrategy(): TranslationStrategyInterface
    {
        return new TranslationStrategy('strategy1', [
            'en' => [
                'lang1' => [
                    'lang2' => [],
                ],
                'lang3' => [
                    'lang4' => [],
                ],
            ],
        ]);
    }
}
