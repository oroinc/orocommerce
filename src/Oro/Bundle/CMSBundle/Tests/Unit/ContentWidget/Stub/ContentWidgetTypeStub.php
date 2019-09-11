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
    /**
     * {@inheritdoc}
     */
    public static function getName(): string
    {
        return 'stub_type';
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsForm(ContentWidget $contentWidget, FormFactoryInterface $formFactory): FormInterface
    {
        return $formFactory->createBuilder(FormType::class, $contentWidget)
            ->getForm();
    }

    /**
     * {@inheritdoc}
     */
    public function getBackOfficeViewBlock(ContentWidget $contentWidget, Environment $twig): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getWidgetData(ContentWidget $contentWidget): array
    {
        return [];
    }
}
