<?php

namespace Oro\Bundle\CMSBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\Ownership\AuditableOrganizationAwareTrait;
use Oro\Bundle\CMSBundle\Model\ExtendPage;

/**
 * @ORM\Table(name="oro_cms_page")
 * @ORM\Entity()
 * @Config(
 *      routeName="oro_cms_page_index",
 *      routeView="oro_cms_page_view",
 *      routeUpdate="oro_cms_page_update",
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-book"
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          },
 *         "ownership"={
 *              "owner_type"="ORGANIZATION",
 *              "owner_field_name"="organization",
 *              "owner_column_name"="organization_id"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          },
 *          "form"={
 *              "form_type"="oro_cms_page_select",
 *              "grid_name"="cms-page-select-grid"
 *          }
 *      }
 * )
 */
class Page extends ExtendPage implements DatesAwareInterface
{
    use AuditableOrganizationAwareTrait;
    use DatesAwareTrait;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Collection|LocalizedFallbackValue[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(
     *      name="oro_cms_page_title",
     *      joinColumns={
     *          @ORM\JoinColumn(name="page_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="localized_value_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $titles;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $content;

    /**
     * @var Collection|LocalizedFallbackValue[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(
     *      name="oro_cms_page_slug",
     *      joinColumns={
     *          @ORM\JoinColumn(name="page_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="localized_value_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $slugs;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();

        $this->slugs = new ArrayCollection();
        $this->titles = new ArrayCollection();
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getTitles()
    {
        return $this->titles;
    }

    /**
     * @param LocalizedFallbackValue $title
     *
     * @return $this
     */
    public function addTitle(LocalizedFallbackValue $title)
    {
        if (!$this->titles->contains($title)) {
            $this->titles->add($title);
        }

        return $this;
    }

    /**
     * @param LocalizedFallbackValue $title
     *
     * @return $this
     */
    public function removeTitle(LocalizedFallbackValue $title)
    {
        if ($this->titles->contains($title)) {
            $this->titles->removeElement($title);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     * @return $this
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getSlugs()
    {
        return $this->slugs;
    }

    /**
     * @param LocalizedFallbackValue $slug
     *
     * @return $this
     */
    public function addSlug(LocalizedFallbackValue $slug)
    {
        if (!$this->slugs->contains($slug)) {
            $this->slugs->add($slug);
        }

        return $this;
    }

    /**
     * @param LocalizedFallbackValue $slug
     *
     * @return $this
     */
    public function removeSlug(LocalizedFallbackValue $slug)
    {
        if ($this->slugs->contains($slug)) {
            $this->slugs->removeElement($slug);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getDefaultTitle();
    }
}
