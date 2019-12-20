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
    /**
     * @return string
     */
    public static function getName(): string;

    /**
     * @return string
     */
    public function getLabel(): string;

    /**
     * @param ContentWidget $contentWidget
     * @param FormFactoryInterface $formFactory
     * @return null|FormInterface
     */
    public function getSettingsForm(ContentWidget $contentWidget, FormFactoryInterface $formFactory): ?FormInterface;

    /**
     * @param ContentWidget $contentWidget
     * @param Environment $twig
     * @return array
     */
    public function getBackOfficeViewSubBlocks(ContentWidget $contentWidget, Environment $twig): array;

    /**
     * @param ContentWidget $contentWidget
     * @return array
     */
    public function getWidgetData(ContentWidget $contentWidget): array;

    /**
     * @return bool
     */
    public function isInline(): bool;

    /**
     * @param ContentWidget $contentWidget
     * @param Environment $twig
     * @return string
     */
    public function getDefaultTemplate(ContentWidget $contentWidget, Environment $twig): string;
}
