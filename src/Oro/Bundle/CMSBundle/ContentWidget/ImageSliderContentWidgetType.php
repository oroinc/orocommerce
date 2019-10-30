<?php

namespace Oro\Bundle\CMSBundle\ContentWidget;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Entity\ImageSlide;
use Oro\Bundle\CMSBundle\Form\Type\ImageSlideCollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Twig\Environment;

/**
 * Type for the image slider widgets.
 */
class ImageSliderContentWidgetType extends AbstractContentWidgetType
{
    /** @var ManagerRegistry */
    private $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /** {@inheritdoc} */
    public static function getName(): string
    {
        return 'image_slider';
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'oro.cms.content_widget_type.image_slider.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getBackOfficeViewSubBlocks(ContentWidget $contentWidget, Environment $twig): array
    {
        return [
            [
                'title' => 'oro.cms.contentwidget.sections.image_slides.label',
                'subblocks' => [
                    [
                        'data' => [
                            $twig->render(
                                '@OroCMS/ImageSliderContentWidget/view.html.twig',
                                $this->getWidgetData($contentWidget)
                            ),
                        ]
                    ],
                ]
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsForm(ContentWidget $contentWidget, FormFactoryInterface $formFactory): ?FormInterface
    {
        return $formFactory->create(FormType::class)
            ->add(
                'imageSlides',
                ImageSlideCollectionType::class,
                [
                    'data' => $this->getImageSlides($contentWidget),
                    'block' => 'image_slides',
                    'block_config' => [
                        'image_slides' => [
                            'title' => 'oro.cms.contentwidget.sections.image_slides.label'
                        ]
                    ]
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function getWidgetData(ContentWidget $contentWidget): array
    {
        return array_merge($contentWidget->getSettings(), ['imageSlides' => $this->getImageSlides($contentWidget)]);
    }

    /**
     * @param ContentWidget $contentWidget
     * @return array
     */
    private function getImageSlides(ContentWidget $contentWidget): array
    {
        return $this->registry->getManagerForClass(ImageSlide::class)
            ->getRepository(ImageSlide::class)
            ->findBy(['contentWidget' => $contentWidget], ['slideOrder' => 'ASC']);
    }
}
