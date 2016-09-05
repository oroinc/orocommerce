<?php

namespace Oro\Bundle\FrontendNavigationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\NavigationBundle\Model\MenuUpdate as MenuUpdateModel;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * @ORM\Entity()
 * @ORM\Table(name="oro_front_nav_menu_update")
 */
class MenuUpdate extends MenuUpdateModel
{
    const OWNERSHIP_WEBSITE      = 3;
    const OWNERSHIP_ACCOUNT      = 4;
    const OWNERSHIP_ACCOUNT_USER = 5;

    /**
     * @var string
     *
     * @ORM\Column(name="image", type="string", length=255, nullable=true)
     */
    protected $image;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=100, nullable=true)
     */
    protected $description;

    /**
     * @var string
     *
     * @ORM\Column(name="condition", type="string", length=512, nullable=true)
     */
    protected $condition;

    /**
     * @var Website
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\WebsiteBundle\Entity\Website")
     * @ORM\JoinColumn(name="website_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")")
     */
    protected $website;

    /**
     * {@inheritdoc}
     */
    public function getExtras()
    {
        return [
            'image'       => $this->image,
            'description' => $this->description,
            'condition'   => $this->condition,
            'website'     => $this->website
        ];
    }

    /**
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param string $image
     * @return MenuUpdate
     */
    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return MenuUpdate
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * @param string $condition
     * @return MenuUpdate
     */
    public function setCondition($condition)
    {
        $this->condition = $condition;

        return $this;
    }

    /**
     * @return Website
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @param Website $website
     * @return MenuUpdate
     */
    public function setWebsite(Website $website)
    {
        $this->website = $website;

        return $this;
    }
}
