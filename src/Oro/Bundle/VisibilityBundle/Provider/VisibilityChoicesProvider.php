<?php

namespace Oro\Bundle\VisibilityBundle\Provider;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides details about available visibility types.
 */
class VisibilityChoicesProvider
{
    /** @var TranslatorInterface */
    private $translator;

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
        $translationPattern = 'oro.visibility.' . $className . '.choice.%s';

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
            $result[$this->format($translationPattern, $choice)] = $choice;
        }

        return $result;
    }

    /**
     * @param string $translationPattern
     * @param string $choice
     * @return string
     */
    public function format($translationPattern, $choice)
    {
        return $this->translator->trans(sprintf($translationPattern, $choice));
    }
}
