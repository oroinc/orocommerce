<?php

namespace Oro\Bundle\CMSBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\CMSBundle\Model\ExtendImageSlide;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
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
 */
class ImageSlide extends ExtendImageSlide implements OrganizationAwareInterface
{
    use OrganizationAwareTrait;

    public const TEXT_ALIGNMENT_CODE = 'img_slide_text_align';

    public const TEXT_ALIGNMENT_CENTER = 'center';
    public const TEXT_ALIGNMENT_LEFT = 'left';
    public const TEXT_ALIGNMENT_RIGHT = 'right';
    public const TEXT_ALIGNMENT_TOP_LEFT = 'top_left';
    public const TEXT_ALIGNMENT_TOP_CENTER = 'top_center';
    public const TEXT_ALIGNMENT_TOP_RIGHT = 'top_right';
    public const TEXT_ALIGNMENT_BOTTOM_LEFT = 'bottom_left';
    public const TEXT_ALIGNMENT_BUTTOM_CENTER = 'buttom_center';
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
     * @ORM\ManyToOne(
     *     targetEntity="Oro\Bundle\CMSBundle\Entity\ContentWidget",
     *     inversedBy="imageSlides",
     *     cascade={"persist"}
     * )
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
     * @ORM\Column(name="order", type="integer", options={"default"=0})
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $order;

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
     * @var File
     *
     * @ORM\OneToOne(targetEntity="Oro\Bundle\AttachmentBundle\Entity\File", cascade={"all"})
     * @ORM\JoinColumn(name="main_image_id", referencedColumnName="id", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "attachment"={
     *              "acl_protected"=false
     *          },
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $mainImage;

    /**
     * @var File
     *
     * @ORM\OneToOne(targetEntity="Oro\Bundle\AttachmentBundle\Entity\File", cascade={"all"})
     * @ORM\JoinColumn(name="medium_image_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     * @ConfigField(
     *      defaultValues={
     *          "attachment"={
     *              "acl_protected"=false
     *          },
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $mediumImage;

    /**
     * @var File
     *
     * @ORM\OneToOne(targetEntity="Oro\Bundle\AttachmentBundle\Entity\File", cascade={"all"})
     * @ORM\JoinColumn(name="small_image_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     * @ConfigField(
     *      defaultValues={
     *          "attachment"={
     *              "acl_protected"=false
     *          },
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $smallImage;

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return null|ContentWidget
     */
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

    /**
     * @return null|int
     */
    public function getOrder(): ?int
    {
        return $this->order;
    }

    /**
     * @param int $order
     * @return $this
     */
    public function setOrder(int $order): self
    {
        $this->order = $order;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return null|bool
     */
    public function isDisplayInSameWindow(): ?bool
    {
        return $this->displayInSameWindow;
    }

    /**
     * @param bool $displayInSameWindow
     * @return $this
     */
    public function setDisplayInSameWindow(bool $displayInSameWindow): self
    {
        $this->displayInSameWindow = $displayInSameWindow;
        
        return $this;
    }

    /**
     * @return null|string
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;
        
        return $this;
    }

    /**
     * @return null|string
     */
    public function getText(): ?string
    {
        return $this->text;
    }

    /**
     * @param string $text
     * @return $this
     */
    public function setText(string $text): self
    {
        $this->text = $text;
        
        return $this;
    }

    /**
     * @return null|File
     */
    public function getMainImage(): ?File
    {
        return $this->mainImage;
    }

    /**
     * @param File $mainImage
     * @return $this
     */
    public function setMainImage(File $mainImage): self
    {
        $this->mainImage = $mainImage;
        
        return $this;
    }

    /**
     * @return null|File
     */
    public function getMediumImage(): ?File
    {
        return $this->mediumImage;
    }

    /**
     * @param File $mediumImage
     * @return $this
     */
    public function setMediumImage(File $mediumImage): self
    {
        $this->mediumImage = $mediumImage;
        
        return $this;
    }

    /**
     * @return null|File
     */
    public function getSmallImage(): ?File
    {
        return $this->smallImage;
    }

    /**
     * @param File $smallImage
     * @return $this
     */
    public function setSmallImage(File $smallImage): self
    {
        $this->smallImage = $smallImage;
        
        return $this;
    }
}
