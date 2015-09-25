<?php

namespace OroB2B\Bundle\AccountBundle\Formatter;

use Symfony\Component\Translation\TranslatorInterface;

class ChoiceFormatter
{
    /**
     * @var array
     */
    protected $choices = [];

    /**
     * @var string
     */
    protected $translationPattern = '%s';

    /**
     * @var TranslatorInterface
     */
    public $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param string $value
     * @return array
     */
    public function format($value)
    {
        $translationKey = sprintf($this->translationPattern, $value);

        return $this->translator->trans($translationKey);
    }

    /**
     * @return array
     */
    public function formatChoices()
    {
        $result = [];
        foreach ($this->choices as $choice) {
            $result[$choice] = $this->format($choice);
        }

        return $result;
    }

    /**
     * @param callable|array $choices
     */
    public function setChoices($choices)
    {
        if (is_callable($choices)) {
            $choices = call_user_func($choices);
        }
        $this->choices = $choices;
    }

    /**
     * @param string $translationPattern
     */
    public function setTranslationPattern($translationPattern)
    {
        $this->translationPattern = $translationPattern;
    }
}
