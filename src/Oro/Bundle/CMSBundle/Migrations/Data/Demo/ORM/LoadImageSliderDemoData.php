<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Entity\File as AttachmentFile;
use Oro\Bundle\CMSBundle\ContentWidget\ImageSliderContentWidgetType;
use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Entity\ImageSlide;
use Oro\Bundle\CMSBundle\Entity\TextContentVariant;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Adds content block with Image Slider content widget in it.
 */
class LoadImageSliderDemoData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;
    use UserUtilityTrait;

    public const HOME_PAGE_SLIDER_ALIAS = 'home-page-slider';

    public const SLIDES = [
        [
            'url' => '/product/',
            'displayInSameWindow' => true,
            'altImageText' => 'Seasonal Sale',
            'header' => 'Seasonal Sale',
            'textAlignment' => ImageSlide::TEXT_ALIGNMENT_LEFT,
            'text' => '
                <p>Get 25 Percent Off the Order Total With a Coupon Code <em>SALE25</em></p>
                <p>Explore our bestselling collections of industrial, medical, and furniture supplies.</p>
            ',
            'extraLargeImage' => 'promo-slider-1-extra-large',
            'extraLargeImage2x' => 'promo-slider-1-extra-large-2x',
            'largeImage' => 'promo-slider-1-large',
            'largeImage2x' => 'promo-slider-1-large-2x',
            'mediumImage' => 'promo-slider-1-medium',
            'mediumImage2x' => 'promo-slider-1-medium-2x',
            'smallImage' => 'promo-slider-1-small',
            'smallImage2x' => 'promo-slider-1-small-2x',
        ],
        [
            'url' => '/navigation-root/new-arrivals/lighting-products',
            'displayInSameWindow' => true,
            'altImageText' => 'Bright New Day In Lighting',
            'header' => 'Bright New Day In Lighting',
            'textAlignment' => ImageSlide::TEXT_ALIGNMENT_CENTER,
            'text' => '
                <p>Explore our new-season collection of models and brands</p>
            ',
            'extraLargeImage' => 'promo-slider-2-extra-large',
            'extraLargeImage2x' => 'promo-slider-2-extra-large-2x',
            'largeImage' => 'promo-slider-2-large',
            'largeImage2x' => 'promo-slider-2-extra-large-2x',
            'mediumImage' => 'promo-slider-2-medium',
            'mediumImage2x' => 'promo-slider-2-medium-2x',
            'smallImage' => 'promo-slider-2-small',
            'smallImage2x' => 'promo-slider-2-small-2x',

        ],
        [
            'url' => '/medical/medical-apparel',
            'displayInSameWindow' => true,
            'altImageText' => 'Best-Priced Medical Supplies',
            'header' => 'Best-Priced Medical Supplies',
            'textAlignment' => ImageSlide::TEXT_ALIGNMENT_RIGHT,
            'text' => '
                <p>Find and buy quality medical equipment and home healthcare supplies</p>
            ',
            'extraLargeImage' => 'promo-slider-3-extra-large',
            'extraLargeImage2x' => 'promo-slider-3-extra-large-2x',
            'largeImage' => 'promo-slider-3-large',
            'largeImage2x' => 'promo-slider-3-large-2x',
            'mediumImage' => 'promo-slider-3-medium',
            'mediumImage2x' => 'promo-slider-3-medium-2x',
            'smallImage' => 'promo-slider-3-small',
            'smallImage2x' => 'promo-slider-3-small-2x',
        ],
    ];

    public const IMAGE_TYPES = [
        'extraLargeImage',
        'largeImage',
        'mediumImage',
        'smallImage',
        'extraLargeImage2x',
        'largeImage2x',
        'mediumImage2x',
        'smallImage2x',
        'extraLargeImage3x',
        'largeImage3x',
        'mediumImage3x',
        'smallImage3x',
    ];

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadAdminUserData::class
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $user = $this->getFirstUser($manager);

        $contentWidget = $this->getContentWidgetByName($manager, static::HOME_PAGE_SLIDER_ALIAS);
        if ($contentWidget instanceof ContentWidget) {
            return;
        }

        $widget = new ContentWidget();
        $widget->setWidgetType(ImageSliderContentWidgetType::getName());
        $widget->setName(static::HOME_PAGE_SLIDER_ALIAS);
        $widget->setOrganization($this->getOrganization($manager));
        $widget->setSettings(
            [
                'slidesToShow' => 1,
                'slidesToScroll' => 1,
                'autoplay' => true,
                'autoplaySpeed' => 4000,
                'arrows' => false,
                'dots' => true,
                'infinite' => false,
                'scaling' => ImageSliderContentWidgetType::SCALING_CROP_IMAGES,
            ]
        );
        $manager->persist($widget);
        $manager->flush();
        $organization = $this->hasReference('default_organization')
            ? $this->getReference('default_organization')
            : $manager->getRepository(Organization::class)->getFirst();

        foreach (static::SLIDES as $order => $data) {
            $slide = new ImageSlide();
            foreach (self::IMAGE_TYPES as $imageType) {
                $filename = $data[$imageType] ?? null;
                if ($filename) {
                    call_user_func_array(
                        [$slide, sprintf('set%s', ucfirst($imageType))],
                        [$this->createImage($manager, $user, $filename)]
                    );
                }
            }
            $slide->setContentWidget($widget);
            $slide->setSlideOrder($order + 1);
            $slide->setUrl($data['url']);
            $slide->setDisplayInSameWindow($data['displayInSameWindow']);
            $slide->setAltImageText($data['altImageText']);
            $slide->setTextAlignment($data['textAlignment']);
            $slide->setText($data['text']);
            $slide->setOrganization($organization);
            $slide->setHeader($data['header']);

            $manager->persist($slide);
            $manager->flush();
        }

        $this->updateOrCreateContentBlock(
            $manager,
            $user,
            '<div data-title="home-page-slider" data-type="image_slider" class="content-widget content-placeholder">
                {{ widget("home-page-slider") }}
            </div>'
        );
    }

    protected function getOrganization(ObjectManager $manager): Organization
    {
        return $this->getFirstUser($manager)->getOrganization();
    }

    protected function updateOrCreateContentBlock(ObjectManager $manager, User $user, string $content): void
    {
        $contentBlock = $manager->getRepository(ContentBlock::class)
            ->findOneBy(['alias' => static::HOME_PAGE_SLIDER_ALIAS]);

        if (!$contentBlock instanceof ContentBlock) {
            $title = new LocalizedFallbackValue();
            $title->setString('Home Page Slider');
            $manager->persist($title);

            $variant = new TextContentVariant();
            $variant->setDefault(true);
            $variant->setContent($content);
            $manager->persist($variant);

            $slider = new ContentBlock();
            $slider->setOrganization($this->getOrganization($manager));
            $slider->setOwner($user->getOwner());
            $slider->setAlias(static::HOME_PAGE_SLIDER_ALIAS);
            $slider->addTitle($title);
            $slider->addContentVariant($variant);
            $manager->persist($slider);
        } else {
            $html = file_get_contents(__DIR__ . '/data/frontpage_slider.html');

            foreach ($contentBlock->getContentVariants() as $contentVariant) {
                if ($contentVariant->getContent() === $html) {
                    $contentVariant->setContent($content);
                    break;
                }
            }
        }

        $manager->flush();
    }

    protected function createImage(ObjectManager $manager, User $user, string $filename): AttachmentFile
    {
        $locator = $this->container->get('file_locator');

        $imagePath = $locator->locate(
            sprintf('@OroCMSBundle/Migrations/Data/Demo/ORM/data/promo-slider/%s.png', $filename)
        );
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
            ->setOrganization($this->getOrganization($manager));
        $manager->persist($digitalAsset);

        $image = new AttachmentFile();
        $image->setDigitalAsset($digitalAsset);
        $manager->persist($image);
        $manager->flush();

        return $image;
    }

    private function getContentWidgetByName(ObjectManager $manager, string $name): ?ContentWidget
    {
        $qb = $manager->getRepository(ContentWidget::class)->createQueryBuilder('cw');

        return $qb
            ->andWhere('cw.name = :name AND cw.organization = :organization')
            ->setParameter('name', $name)
            ->setParameter('organization', $this->getOrganization($manager))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
