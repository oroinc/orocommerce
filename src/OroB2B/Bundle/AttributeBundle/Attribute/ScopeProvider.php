<?php

namespace OroB2B\Bundle\AttributeBundle\Attribute;

use Symfony\Component\Translation\TranslatorInterface;

class ScopeProvider
{

    /**
     * @var array|array[]
     */
    private $choices;

    /**
     * @var array|string[]
     */
    private $scopes;

    /**
     * @var \Symfony\Component\Translation\TranslatorInterface
     */
    private $translator;

    /**
     * @param \Symfony\Component\Translation\TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
        $this->scopes = [
            'global',
            'shared',
            'website',
        ];
        $this->choices = [
            'global'    => $this->translator->trans('orob2b.attribute.attribute.scope.global'),
            'shared'    => $this->translator->trans('orob2b.attribute.attribute.scope.shared'),
            'website'   => $this->translator->trans('orob2b.attribute.attribute.scope.website'),
        ];
    }

    /**
     * @return array ['sometype' => 'translated label',...]
     */
    public function getChoices()
    {
        return $this->choices;
    }

    /**
     * @return array|string[]
     */
    public function getScopes()
    {
        return $this->scopes;
    }
}
