<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;

/**
 * Saves changed product unit translatable labels using the translation manager.
 */
class SaveProductUnitLabels implements ProcessorInterface
{
    private TranslationManager $translationManager;
    private LocaleAwareInterface $translator;
    private UnitLabelFormatterInterface $formatter;
    /** @var array [domain => [translation key => translation template, ...], ...] */
    private array $mapping;

    public function __construct(
        TranslationManager $translationManager,
        LocaleAwareInterface $translator,
        UnitLabelFormatterInterface $formatter,
        array $mapping
    ) {
        $this->translationManager = $translationManager;
        $this->translator = $translator;
        $this->formatter = $formatter;
        $this->mapping = $mapping;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
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

    private function getLabelDataFromForm(string $fieldName, CustomizeFormDataContext $context): ?string
    {
        $form = $context->findFormField($fieldName);
        $value = null;
        if (null !== $form && $form->isSubmitted()) {
            $value = $form->getData();
        }

        return $value;
    }

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

    private function getExistingLabel(string $placeholder, string $productUnitCode): string
    {
        return match ($placeholder) {
            '{full}' => $this->formatter->format($productUnitCode),
            '{short}' => $this->formatter->format($productUnitCode, true),
            '{full_plural}' => $this->formatter->format($productUnitCode, false, true),
            '{short_plural}' => $this->formatter->format($productUnitCode, true, true),
            default => ''
        };
    }

    private function modifyTranslatableLabels(array $data, string $productUnitCode): void
    {
        $search = array_keys($data);
        $replace = array_values($data);
        $locale = $this->translator->getLocale();
        foreach ($this->mapping as $domain => $templates) {
            foreach ($templates as $key => $template) {
                $key = str_replace('{unit}', $productUnitCode, $key);
                $value = str_replace($search, $replace, $template);
                $this->translationManager->saveTranslation($key, $value, $locale, $domain, Translation::SCOPE_UI);
            }
        }
        $this->translationManager->flush();
    }

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
