<?php
declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Migrations\Data\ORM;

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
class LoadImageSlider extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;
    use UserUtilityTrait;

    public const HOME_PAGE_SLIDER_ALIAS = 'home-page-slider';

    public const SLIDES = [
        [
            'url' => '/product/',
            'displayInSameWindow' => true,
            'title' => 'Seasonal Sale',
            'textAlignment' => ImageSlide::TEXT_ALIGNMENT_RIGHT,
            'text' => '<h2 style="color:#34495e;text-transform:uppercase;">Seasonal Sale</h2>
<h4></h4>
<p style="color:#34495e;">Get <strong><span style="color:#e67e23;">25 Percent Off the Order Total</span></strong>
 With a Coupon Code <span style="color:#e67e23;"><b>SALE25</b></span></p>
<p></p>
<p style="color:#34495e;">Explore our bestselling collections of industrial, medical, and furniture supplies.</p>
<p></p>
<p></p>
<p><a href="/product/">Browse</a></p>',
            'mainImage' => 'promo-slider-4',
            'mediumImage' => 'promo-slider-medium-4',
            'smallImage' => 'promo-slider-small-4',
        ],
        [
            'url' => '/navigation-root/new-arrivals/lighting-products',
            'displayInSameWindow' => true,
            'title' => 'Bright New Day In Lighting',
            'textAlignment' => ImageSlide::TEXT_ALIGNMENT_LEFT,
            'text' => '<h3 style="text-transform:uppercase;color:#34495e;">Bright New Day In Lighting</h3>
<p style="color:#34495e;">Explore our new-season collection of models and brands</p>
<p><a href="/navigation-root/new-arrivals/lighting-products">Browse</a></p>',
            'mainImage' => 'promo-slider-5',
            'mediumImage' => 'promo-slider-medium-5',
            'smallImage' => 'promo-slider-small-6',
        ],
        [
            'url' => '/medical/medical-apparel',
            'displayInSameWindow' => true,
            'title' => 'Best-Priced Medical Supplies',
            'textAlignment' => ImageSlide::TEXT_ALIGNMENT_RIGHT,
            'text' => '<h3 style="text-transform:uppercase;text-align:left;">Best-Priced Medical Supplies</h3>
<p>Find and buy quality medical equipment and home healthcare supplies</p>
<p style="text-align:left;"><a href="/medical/medical-apparel">Browse</a></p>',
            'mainImage' => 'promo-slider-6',
            'mediumImage' => 'promo-slider-medium-6',
            'smallImage' => 'promo-slider-small-6',
        ],
    ];

    public function getDependencies(): array
    {
        return [
            LoadAdminUserData::class
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $user = $this->getFirstUser($manager);

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
            ]
        );
        $manager->persist($widget);
        $manager->flush();
        $organization = $this->hasReference('default_organization')
            ? $this->getReference('default_organization')
            : $manager->getRepository(Organization::class)->getFirst();

        foreach (static::SLIDES as $order => $data) {
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
            $slide->setOrganization($organization);

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
            ->setOrganization($this->getOrganization($manager));
        $manager->persist($digitalAsset);

        $image = new AttachmentFile();
        $image->setDigitalAsset($digitalAsset);
        $manager->persist($image);
        $manager->flush();

        return $image;
    }
}
