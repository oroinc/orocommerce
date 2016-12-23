<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Translation\Loader\YamlFileLoader;

use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowTranslations;

class LoadTranslations extends LoadWorkflowTranslations
{
    const TRANSLATION_PATH = __DIR__ . '/../../../Resources/translations/workflows.en.yml';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $loader = new YamlFileLoader();
        $catalog = $loader->load(
            static::TRANSLATION_PATH,
            Translator::DEFAULT_LOCALE,
            WorkflowTranslationHelper::TRANSLATION_DOMAIN
        );

        foreach ($catalog->all(WorkflowTranslationHelper::TRANSLATION_DOMAIN) as $translationKey => $translationValue) {
            $this->createTranslation($translationKey, $translationValue, Translator::DEFAULT_LOCALE);
        }

        $this->getTranslationManager()->flush();
        $this->container->get('translator.default')->rebuildCache();
    }
}
