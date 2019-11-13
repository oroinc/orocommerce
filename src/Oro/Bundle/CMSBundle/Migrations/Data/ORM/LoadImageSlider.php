<?php

namespace Oro\Bundle\CMSBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Gaufrette\Adapter\Local;
use Gaufrette\Filesystem;
use Oro\Bundle\AttachmentBundle\Entity\File as AttachmentFile;
use Oro\Bundle\CMSBundle\ContentWidget\ImageSliderContentWidgetType;
use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Entity\ImageSlide;
use Oro\Bundle\CMSBundle\Entity\TextContentVariant;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Adds content block with Image Slider content widget in it.
 */
class LoadImageSlider extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;
    use UserUtilityTrait;

    /** @var string */
    private const HOME_PAGE_SLIDER_ALIAS = 'home-page-slider';

    /** @var array */
    private $slides = [
        [
            'url' => '/product/',
            'displayInSameWindow' => true,
            'title' => 'Lorem ipsum',
            'textAlignment' => ImageSlide::TEXT_ALIGNMENT_RIGHT,
            'text' => '<h2 class="promo-slider__title">Lorem ipsum</h2><div class="promo-slider__description">
                Praesent magna arcu, placerat id purus vel, facilisis posuere augue. Praesent nec consequat elit, sed 
                elementum elit. Ut dictum nisi imperdiet justo tristique finibus.</div>
                <span class="btn btn--info promo-slider__view-btn">Call to action</span>',
            'mainImage' => 'promo-slider-1',
            'mediumImage' => 'promo-slider-medium-1',
            'smallImage' => 'promo-slider-small-1',
        ],
        [
            'url' => '/product/?categoryId=2&includeSubcategories=1',
            'displayInSameWindow' => true,
            'title' => 'Lorem ipsum',
            'textAlignment' => ImageSlide::TEXT_ALIGNMENT_LEFT,
            'text' => '<h2 class="promo-slider__title">Lorem ipsum</h2><div class="promo-slider__description">
                Praesent magna arcu, placerat id purus vel, facilisis posuere augue. Praesent nec consequat elit, sed 
                elementum elit. Ut dictum nisi imperdiet justo tristique finibus.</div>
                <span class="btn btn--info promo-slider__view-btn">Call to action</span>',
            'mainImage' => 'promo-slider-2',
            'mediumImage' => 'promo-slider-medium-2',
            'smallImage' => 'promo-slider-small-2',
        ],
        [
            'url' => '/product/?categoryId=7&includeSubcategories=1',
            'displayInSameWindow' => true,
            'title' => 'Lorem ipsum',
            'textAlignment' => ImageSlide::TEXT_ALIGNMENT_CENTER,
            'text' => '<div class="promo-slider__info--text-color-dark"><h2 class="promo-slider__title">Lorem ipsum</h2>
                <div class="promo-slider__description">Praesent magna arcu, placerat id purus vel, facilisis posuere 
                augue. Praesent nec consequat elit, sed elementum elit. Ut dictum nisi imperdiet justo tristique 
                finibus.</div><span class="btn btn--info promo-slider__view-btn">Call to action</span></div>',
            'mainImage' => 'promo-slider-3',
            'mediumImage' => 'promo-slider-medium-3',
            'smallImage' => 'promo-slider-small-3',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadAdminUserData::class
        ];
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $user = $this->getFirstUser($manager);

        $widget = new ContentWidget();
        $widget->setWidgetType(ImageSliderContentWidgetType::getName());
        $widget->setName(self::HOME_PAGE_SLIDER_ALIAS);
        $widget->setOrganization($user->getOrganization());
        $widget->setSettings(
            [
                'slidesToShow' => 1,
                'slidesToScroll' => 1,
                'autoplay' => true,
                'autoplaySpeed' => 4000,
                'arrows' => false,
                'dots' => true,
                'infinite' => false,
            ]
        );
        $manager->persist($widget);
        $manager->flush();

        foreach ($this->slides as $order => $data) {
            $slide = new ImageSlide();
            $slide->setMainImage($this->createImage($manager, $user, $data['mainImage']));
            $slide->setMediumImage($this->createImage($manager, $user, $data['mediumImage']));
            $slide->setSmallImage($this->createImage($manager, $user, $data['smallImage']));
            $slide->setContentWidget($widget);
            $slide->setSlideOrder($order + 1);
            $slide->setUrl($data['url']);
            $slide->setDisplayInSameWindow($data['displayInSameWindow']);
            $slide->setTitle($data['title']);
            $slide->setTextAlignment($data['textAlignment']);
            $slide->setText($data['text']);

            $manager->persist($slide);
            $manager->flush();
        }

        $this->getContentBlock(
            $manager,
            $user,
            '<div data-title="home-page-slider" data-type="image_slider" class="content-widget content-placeholder">
                {{ widget("home-page-slider") }}
            </div>'
        );
    }

    /**
     * @param ObjectManager $manager
     * @param User $user
     * @param string $content
     * @return ContentBlock|null
     */
    private function getContentBlock(ObjectManager $manager, User $user, string $content): void
    {
        $contentBlock = $manager->getRepository(ContentBlock::class)
            ->findOneBy(['alias' => self::HOME_PAGE_SLIDER_ALIAS]);

        if (!$contentBlock instanceof ContentBlock) {
            $title = new LocalizedFallbackValue();
            $title->setString('Home Page Slider');
            $manager->persist($title);

            $variant = new TextContentVariant();
            $variant->setDefault(true);
            $variant->setContent($content);
            $manager->persist($variant);

            $slider = new ContentBlock();
            $slider->setOrganization($user->getOrganization());
            $slider->setOwner($user->getOwner());
            $slider->setAlias(self::HOME_PAGE_SLIDER_ALIAS);
            $slider->addTitle($title);
            $slider->addContentVariant($variant);
            $manager->persist($slider);
            $manager->flush();
        } else {
            $html = file_get_contents(__DIR__ . '/data/frontpage_slider.html');

            foreach ($contentBlock->getContentVariants() as $contentVariant) {
                if ($contentVariant->getContent() === $html) {
                    $contentVariant->setContent($content);
                    return;
                }
            }
        }
    }

    /**
     * @param ObjectManager $manager
     * @param User $user
     * @param string $filename
     * @return AttachmentFile
     */
    private function createImage(ObjectManager $manager, User $user, string $filename): AttachmentFile
    {
        $locator = $this->container->get('file_locator');

        $imagePath = $locator->locate(sprintf('@OroCMSBundle/Migrations/Data/ORM/data/promo-slider/%s.jpg', $filename));
        if (is_array($imagePath)) {
            $imagePath = current($imagePath);
        }

        $file = $this->container->get('oro_attachment.file_manager')->createFileEntity($imagePath);
        $file->setOwner($user);
        $manager->persist($file);

        $imageTitle = new LocalizedFallbackValue();
        $imageTitle->setString($filename);
        $manager->persist($imageTitle);

        $digitalAsset = new DigitalAsset();
        $digitalAsset->addTitle($imageTitle)
            ->setSourceFile($file)
            ->setOwner($user)
            ->setOrganization($user->getOrganization());
        $manager->persist($digitalAsset);

        $image = new AttachmentFile();
        $image->setDigitalAsset($digitalAsset);
        $manager->persist($image);
        $manager->flush();

        $this->writeDigitalAssets($file, $locator, $filename, 'original');

        return $image;
    }

    /**
     * @param AttachmentFile $file
     * @param FileLocator $locator
     * @param $filename
     * @param $filter
     */
    private function writeDigitalAssets(AttachmentFile $file, FileLocator $locator, $filename, $filter): void
    {
        $storagePath = $this->container->get('oro_attachment.provider.resized_image_path')
            ->getPathForFilteredImage($file, $filter);

        $rootPath = $locator->locate('@OroCMSBundle/Migrations/Data/ORM/data/promo-slider');
        if (is_array($rootPath)) {
            $rootPath = current($rootPath);
        }

        $filesystem = new Filesystem(new Local($rootPath, false, 0600));

        $file = $filesystem->get(sprintf('%s.jpg', $filename));

        $this->container->get('oro_attachment.manager.protected_mediacache')
            ->writeToStorage($file->getContent(), $storagePath);
    }
}
