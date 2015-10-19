<?php

namespace OroB2B\Bundle\AccountBundle\Provider;

use Symfony\Component\Translation\TranslatorInterface;

class VisibilityChoicesProvider
{
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
     * @param string $sourceClass
     * @param object $target
     * @return array
     */
    public function getFormattedChoices($sourceClass, $target)
    {
        $choices = $this->getChoices($sourceClass, $target);

        $sourceClassReflection = new \ReflectionClass($sourceClass);
        $className = strtolower($sourceClassReflection->getShortName());
        $translationPattern = 'orob2b.account.visibility.' . $className . '.choice.%s';

        return $this->formatChoices($translationPattern, $choices);
    }

    /**
     * @param string $sourceClass
     * @param object $target
     * @return array
     */
    public function getChoices($sourceClass, $target)
    {
        return call_user_func([$sourceClass, 'getVisibilityList'], $target);
    }

    /**
     * @param string $translationPattern
     * @param array $choices
     * @return array
     */
    public function formatChoices($translationPattern, $choices)
    {
        $result = [];
        foreach ($choices as $choice) {
            $result[$choice] = $this->format($translationPattern, $choice);
        }

        return $result;
    }

    /**
     * @param string $translationPattern
     * @param string $choice
     * @return array
     */
    public function format($translationPattern, $choice)
    {
        return $this->translator->trans(sprintf($translationPattern, $choice));
    }
}
