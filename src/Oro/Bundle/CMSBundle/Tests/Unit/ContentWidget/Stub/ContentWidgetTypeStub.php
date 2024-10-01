<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\ContentWidget\Stub;

use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeInterface;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Twig\Environment;

class ContentWidgetTypeStub implements ContentWidgetTypeInterface
{
    #[\Override]
    public static function getName(): string
    {
        return 'stub_type';
    }

    #[\Override]
    public function getLabel(): string
    {
        return 'stub type label';
    }

    #[\Override]
    public function getSettingsForm(ContentWidget $contentWidget, FormFactoryInterface $formFactory): FormInterface
    {
        return $formFactory->createBuilder(FormType::class, $contentWidget)
            ->getForm();
    }

    #[\Override]
    public function getBackOfficeViewSubBlocks(ContentWidget $contentWidget, Environment $twig): array
    {
        return [];
    }

    #[\Override]
    public function getWidgetData(ContentWidget $contentWidget): array
    {
        return ['settings' => $contentWidget->getSettings()];
    }

    #[\Override]
    public function isInline(): bool
    {
        return true;
    }

    #[\Override]
    public function getDefaultTemplate(ContentWidget $contentWidget, Environment $twig): string
    {
        return '<b>default template</b>';
    }
}
