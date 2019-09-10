<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\TranslationBundle\Async\Topics;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Saves changed product unit translatable labels using the translation manager.
 */
class SaveProductUnitLabels implements ProcessorInterface
{
    /** @var array [domain => [translation_key => translation_template, ...], ...] */
    private $mapping;

    /** @var TranslationManager */
    private $translationManager;

    /** @var Translator */
    private $translator;

    /** @var UnitLabelFormatterInterface */
    private $formatter;

    /** @var MessageProducerInterface */
    protected $producer;

    /**
     * @param TranslationManager          $translationManager
     * @param Translator                  $translator
     * @param UnitLabelFormatterInterface $formatter
     * @param MessageProducerInterface    $producer
     * @param array                       $mapping
     */
    public function __construct(
        TranslationManager $translationManager,
        Translator $translator,
        UnitLabelFormatterInterface $formatter,
        MessageProducerInterface $producer,
        array $mapping
    ) {
        $this->translationManager = $translationManager;
        $this->translator = $translator;
        $this->formatter = $formatter;
        $this->producer = $producer;
        $this->mapping = $mapping;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeFormDataContext $context */

        if (!$context->getForm()->isValid()) {
            // if form is no valid - do not change the translations
            return;
        }

        $data = [
            '{full}'         => $this->getLabelDataFromForm('label', $context),
            '{full_plural}'  => $this->getLabelDataFromForm('pluralLabel', $context),
            '{short}'        => $this->getLabelDataFromForm('shortLabel', $context),
            '{short_plural}' => $this->getLabelDataFromForm('shortPluralLabel', $context),
        ];

        if (!$this->isSubmittedAtLeastOneLabel($data)) {
            return;
        }

        $productUnitCode = $context->getForm()->getData()->getCode();
        $data = $this->collectNotSubmittedLabels($data, $productUnitCode);
        $this->modifyTranslatableLabels($data, $productUnitCode);
    }

    /**
     * @param string                   $fieldName
     * @param CustomizeFormDataContext $context
     *
     * @return string|null
     */
    private function getLabelDataFromForm(string $fieldName, CustomizeFormDataContext $context): ?string
    {
        $form = $context->findFormField($fieldName);
        $value = null;
        if (null !== $form && $form->isSubmitted()) {
            $value = $form->getData();
        }

        return $value;
    }

    /**
     * @param array  $data
     * @param string $productUnitCode
     *
     * @return array
     */
    private function collectNotSubmittedLabels(array $data, string $productUnitCode): array
    {
        $result = [];
        foreach ($data as $key => $label) {
            if (null === $label) {
                $result[$key] = $this->getExistingLabel($key, $productUnitCode);
            } else {
                $result[$key] = $label;
            }
        }

        return $result;
    }

    /**
     * @param string $placeholder
     * @param string $productUnitCode
     *
     * @return string
     */
    private function getExistingLabel(string $placeholder, string $productUnitCode): string
    {
        $value = '';
        switch ($placeholder) {
            case '{full}':
                $value = $this->formatter->format($productUnitCode);
                break;
            case '{short}':
                $value = $this->formatter->format($productUnitCode, true);
                break;
            case '{full_plural}':
                $value = $this->formatter->format($productUnitCode, false, true);
                break;
            case '{short_plural}':
                $value = $this->formatter->format($productUnitCode, true, true);
        }

        return $value;
    }

    /**
     * @param array  $data
     * @param string $productUnitCode
     */
    private function modifyTranslatableLabels(array $data, string $productUnitCode): void
    {
        $search = array_keys($data);
        $replace = array_values($data);
        $locale = $this->translator->getLocale();
        $catalogue = $this->translator->getCatalogue($locale);
        foreach ($this->mapping as $domain => $templates) {
            foreach ($templates as $key => $template) {
                $key = str_replace('{unit}', $productUnitCode, $key);
                $value = str_replace($search, $replace, $template);

                $this->translationManager->saveTranslation($key, $value, $locale, $domain, Translation::SCOPE_UI);
                $catalogue->set($key, $value, $domain);
            }
        }

        // mark translation cache dirty
        $this->translationManager->invalidateCache($locale);
        $this->translationManager->flush();

        // send MQ message to dump JS translations
        $this->producer->send(Topics::JS_TRANSLATIONS_DUMP, []);
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    private function isSubmittedAtLeastOneLabel(array $data): bool
    {
        foreach ($data as $label) {
            if (null !== $label) {
                return true;
            }
        }

        return false;
    }
}
