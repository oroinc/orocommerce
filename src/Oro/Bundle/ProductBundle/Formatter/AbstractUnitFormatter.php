<?php

namespace Oro\Bundle\ProductBundle\Formatter;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides common functionality for formatting product units.
 *
 * This base class manages the translation prefix used for unit label translations and provides access
 * to the translator service. Subclasses should implement specific formatting logic
 * for different unit display formats (e.g., short, long, code).
 */
abstract class AbstractUnitFormatter
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var string */
    private $translationPrefix;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param string $translationPrefix
     */
    public function setTranslationPrefix($translationPrefix)
    {
        $this->translationPrefix = $translationPrefix;
    }

    /**
     * @return string
     *
     * @throws \Exception
     */
    protected function getTranslationPrefix()
    {
        if (!$this->translationPrefix) {
            throw new \Exception('Translation prefix must be defined.');
        }

        return $this->translationPrefix;
    }
}
