<?php

namespace Oro\Bundle\CMSBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroCMSBundle_Entity_ImageSlide;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\Ownership\OrganizationAwareTrait;

/**
 * Holds image slide data.
 *
 * @method null|File getExtraLargeImage()
 * @method ImageSlide setExtraLargeImage(File $image)
 * @method null|File getExtraLargeImage2x()
 * @method ImageSlide setExtraLargeImage2x(File $image)
 * @method null|File getExtraLargeImage3x()
 * @method ImageSlide setExtraLargeImage3x(File $image)
 * @method null|File getLargeImage()
 * @method ImageSlide setLargeImage(File $image)
 * @method null|File getLargeImage2x()
 * @method ImageSlide setLargeImage2x(File $image)
 * @method null|File getLargeImage3x()
 * @method ImageSlide setLargeImage3x(File $image)
 * @method null|File getMediumImage()
 * @method ImageSlide setMediumImage(File $image)
 * @method null|File getMediumImage2x()
 * @method ImageSlide setMediumImage2x(File $image)
 * @method null|File getMediumImage3x()
 * @method ImageSlide setMediumImage3x(File $image)
 * @method null|File getSmallImage()
 * @method ImageSlide setSmallImage(File $image)
 * @method null|File getSmallImage2x()
 * @method ImageSlide setSmallImage2x(File $image)
 * @method null|File getSmallImage3x()
 * @method ImageSlide setSmallImage3x(File $image)
 * @mixin OroCMSBundle_Entity_ImageSlide
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_cms_image_slide')]
#[Config(
    defaultValues: [
        'ownership' => [
            'owner_type' => 'ORGANIZATION',
            'owner_field_name' => 'organization',
            'owner_column_name' => 'organization_id'
        ],
        'security' => ['type' => 'ACL', 'group_name' => ''],
        'dataaudit' => ['auditable' => true]
    ]
)]
class ImageSlide implements OrganizationAwareInterface, ExtendEntityInterface
{
    use OrganizationAwareTrait;
    use ExtendEntityTrait;

    public const TEXT_ALIGNMENT_CENTER = 'center';
    public const TEXT_ALIGNMENT_LEFT = 'left';
    public const TEXT_ALIGNMENT_RIGHT = 'right';
    public const TEXT_ALIGNMENT_TOP_LEFT = 'top_left';
    public const TEXT_ALIGNMENT_TOP_CENTER = 'top_center';
    public const TEXT_ALIGNMENT_TOP_RIGHT = 'top_right';
    public const TEXT_ALIGNMENT_BOTTOM_LEFT = 'bottom_left';
    public const TEXT_ALIGNMENT_BOTTOM_CENTER = 'bottom_center';
    public const TEXT_ALIGNMENT_BOTTOM_RIGHT = 'bottom_right';

    public const string LOADING_LAZY = 'lazy';
    public const string LOADING_EAGER = 'eager';

    public const string FETCH_PRIORITY_AUTO = 'auto';
    public const string FETCH_PRIORITY_HIGH = 'high';
    public const string FETCH_PRIORITY_LOW = 'low';

    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ContentWidget::class)]
    #[ORM\JoinColumn(name: 'content_widget_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?ContentWidget $contentWidget = null;

    #[ORM\Column(name: 'slide_order', type: Types::INTEGER, options: ['default' => 0])]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?int $slideOrder = 0;

    #[ORM\Column(name: 'url', type: Types::STRING, length: 255, nullable: false)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?string $url = null;

    #[ORM\Column(name: 'display_in_same_window', type: Types::BOOLEAN, nullable: false, options: ['default' => true])]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?bool $displayInSameWindow = true;

    #[ORM\Column(name: 'alt_image_text', type: Types::STRING, length: 255, nullable: false)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?string $altImageText = null;

    #[ORM\Column(name: 'text', type: Types::TEXT, nullable: true)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?string $text = null;

    #[ORM\Column(
        name: 'text_alignment',
        type: Types::STRING,
        length: 20,
        nullable: false,
        options: ['default' => 'center']
    )]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected string $textAlignment = self::TEXT_ALIGNMENT_CENTER;

    #[ORM\Column(name: 'header', type: Types::STRING, nullable: true)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?string $header = null;

    #[ORM\Column(
        name: 'loading',
        type: Types::STRING,
        length: 10,
        nullable: false,
        options: ['default' => self::LOADING_LAZY]
    )]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected string $loading = self::LOADING_LAZY;

    #[ORM\Column(
        name: 'fetch_priority',
        type: Types::STRING,
        length: 10,
        nullable: false,
        options: ['default' => self::FETCH_PRIORITY_AUTO]
    )]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected string $fetchPriority = self::FETCH_PRIORITY_AUTO;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContentWidget(): ?ContentWidget
    {
        return $this->contentWidget;
    }

    /**
     * @param ContentWidget $contentWidget
     * @return $this
     */
    public function setContentWidget(ContentWidget $contentWidget): self
    {
        $this->contentWidget = $contentWidget;

        return $this;
    }

    public function getSlideOrder(): ?int
    {
        return $this->slideOrder;
    }

    /**
     * @param null|int $slideOrder
     * @return $this
     */
    public function setSlideOrder(?int $slideOrder): self
    {
        $this->slideOrder = $slideOrder;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @param null|string $url
     * @return $this
     */
    public function setUrl(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function isDisplayInSameWindow(): ?bool
    {
        return $this->displayInSameWindow;
    }

    /**
     * @param null|bool $displayInSameWindow
     * @return $this
     */
    public function setDisplayInSameWindow(?bool $displayInSameWindow): self
    {
        $this->displayInSameWindow = $displayInSameWindow;

        return $this;
    }

    public function getAltImageText(): ?string
    {
        return $this->altImageText;
    }

    /**
     * @param null|string $altImageText
     *
     * @return $this
     */
    public function setAltImageText(?string $altImageText): self
    {
        $this->altImageText = $altImageText;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    /**
     * @param null|string $text
     * @return $this
     */
    public function setText(?string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getTextAlignment(): ?string
    {
        return $this->textAlignment;
    }

    /**
     * @param null|string $textAlignment
     * @return $this
     */
    public function setTextAlignment(?string $textAlignment): self
    {
        $this->textAlignment = $textAlignment;

        return $this;
    }

    public function getHeader(): ?string
    {
        return $this->header;
    }

    public function setHeader(?string $header): self
    {
        $this->header = $header;

        return $this;
    }

    public function getLoading(): string
    {
        return $this->loading;
    }

    public function setLoading(string $loading): self
    {
        $this->loading = $loading;

        return $this;
    }

    public function getFetchPriority(): string
    {
        return $this->fetchPriority;
    }

    public function setFetchPriority(string $fetchPriority): self
    {
        $this->fetchPriority = $fetchPriority;

        return $this;
    }

    /**
     * Fallback for the old themes support
     */
    public function getMainImage(): ?File
    {
        return $this->getLargeImage();
    }

    /**
     * Fallback for the old themes support
     */
    public function getTitle(): ?string
    {
        return $this->getAltImageText();
    }
}
