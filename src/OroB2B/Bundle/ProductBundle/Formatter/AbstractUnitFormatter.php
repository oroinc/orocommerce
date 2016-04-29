<?php

namespace OroB2B\Bundle\ProductBundle\Formatter;

use Symfony\Component\Translation\TranslatorInterface;

abstract class AbstractUnitFormatter
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var string */
    private $translationPrefix;

    /**
     * @param TranslatorInterface $translator
     */
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
