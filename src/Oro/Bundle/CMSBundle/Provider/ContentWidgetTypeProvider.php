<?php

namespace Oro\Bundle\CMSBundle\Provider;

use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeRegistry;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides pairs [<label> => <name>] of available content widget types.
 */
class ContentWidgetTypeProvider
{
    /** @var ContentWidgetTypeRegistry */
    private $contentWidgetTypeRegistry;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(ContentWidgetTypeRegistry $contentWidgetTypeRegistry, TranslatorInterface $translator)
    {
        $this->contentWidgetTypeRegistry = $contentWidgetTypeRegistry;
        $this->translator = $translator;
    }

    public function getAvailableContentWidgetTypes(): array
    {
        $types = [];
        foreach ($this->contentWidgetTypeRegistry->getTypes() as $type) {
            $label = $this->translator->trans($type->getLabel());

            $types[$label] = $type::getName();
        }

        return $types;
    }
}
