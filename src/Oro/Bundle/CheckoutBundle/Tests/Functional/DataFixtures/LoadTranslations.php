<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowTranslations;
use Symfony\Component\Translation\Loader\YamlFileLoader;

class LoadTranslations extends LoadWorkflowTranslations
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $loader = new YamlFileLoader();
        $catalog = $loader->load(
            static::getTranslationPath(),
            Translator::DEFAULT_LOCALE,
            WorkflowTranslationHelper::TRANSLATION_DOMAIN
        );

        foreach ($catalog->all(WorkflowTranslationHelper::TRANSLATION_DOMAIN) as $translationKey => $translationValue) {
            $this->createTranslation($translationKey, $translationValue, Translator::DEFAULT_LOCALE);
        }

        $this->getTranslationManager()->flush();
        $this->container->get('translator.default')->rebuildCache();
    }

    /**
     * @return string
     */
    protected static function getTranslationPath()
    {
        return __DIR__ . '/../../../Resources/translations/workflows.en.yml';
    }
}
