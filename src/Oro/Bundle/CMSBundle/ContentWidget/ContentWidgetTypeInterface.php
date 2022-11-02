<?php

namespace Oro\Bundle\CMSBundle\ContentWidget;

use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Twig\Environment;

/**
 * Interface for the content widget types.
 */
interface ContentWidgetTypeInterface
{
    public static function getName(): string;

    public function getLabel(): string;

    public function getSettingsForm(ContentWidget $contentWidget, FormFactoryInterface $formFactory): ?FormInterface;

    public function getBackOfficeViewSubBlocks(ContentWidget $contentWidget, Environment $twig): array;

    public function getWidgetData(ContentWidget $contentWidget): array;

    public function isInline(): bool;

    public function getDefaultTemplate(ContentWidget $contentWidget, Environment $twig): string;
}
