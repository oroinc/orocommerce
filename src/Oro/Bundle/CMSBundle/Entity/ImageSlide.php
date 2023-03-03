<?php

namespace Oro\Bundle\CMSBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\Ownership\OrganizationAwareTrait;

/**
 * Holds image slide data.
 *
 * @ORM\Entity()
 * @ORM\Table(name="oro_cms_image_slide")
 * @Config(
 *      defaultValues={
 *          "ownership"={
 *              "owner_type"="ORGANIZATION",
 *              "owner_field_name"="organization",
 *              "owner_column_name"="organization_id"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          }
 *     }
 * )
 * @method null|File getMainImage()
 * @method ImageSlide setMainImage(File $image)
 * @method null|File getMediumImage()
 * @method ImageSlide setMediumImage(File $image)
 * @method null|File getSmallImage()
 * @method ImageSlide setSmallImage(File $image)
 */
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

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var ContentWidget
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CMSBundle\Entity\ContentWidget")
     * @ORM\JoinColumn(name="content_widget_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $contentWidget;

    /**
     * @var int
     *
     * @ORM\Column(name="slide_order", type="integer", options={"default"=0})
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $slideOrder = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $url;

    /**
     * @var boolean
     *
     * @ORM\Column(name="display_in_same_window", type="boolean", nullable=false, options={"default"=true})
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $displayInSameWindow = true;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(name="text", type="text", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $text;

    /**
     * @var string
     *
     * @ORM\Column(name="text_alignment", type="string", length=20, nullable=false, options={"default"="center"})
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $textAlignment = self::TEXT_ALIGNMENT_CENTER;

    /**
     * @return int
     */
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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param null|string $title
     * @return $this
     */
    public function setTitle(?string $title): self
    {
        $this->title = $title;

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
}
