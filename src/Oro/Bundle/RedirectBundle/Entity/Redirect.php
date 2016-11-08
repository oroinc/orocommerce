<?php

namespace Oro\Bundle\RedirectBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * @ORM\Table(name="oro_redirect", indexes={@ORM\Index(name="idx_oro_redirect_from_hash", columns={"from_hash"})})
 * @ORM\Entity(repositoryClass="Oro\Bundle\RedirectBundle\Entity\Repository\RedirectRepository")
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-share-sign"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          }
 *      }
 * )
 */
class Redirect
{
    const MOVED_PERMANENTLY = 301;
    const MOVED_TEMPORARY = 302;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="redirect_from", type="string", length=1024)
     */
    protected $from;

    /**
     * @var string
     *
     * @ORM\Column(name="from_hash", type="string", length=32)
     */
    protected $fromHash;
    
    /**
     * @var string
     *
     * @ORM\Column(name="redirect_to", type="string", length=1024)
     */
    protected $to;

    /**
     * @var integer
     *
     * @ORM\Column(name="redirect_type", type="integer")
     */
    protected $type;

    /**
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\WebsiteBundle\Entity\Website")
     * @ORM\JoinColumn(name="website_id", referencedColumnName="id")
     *
     * @var Website
     */
    protected $website;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @param string $from
     * @return $this
     */
    public function setFrom($from)
    {
        $this->from = $from;
        $this->fromHash = md5($this->from);
        
        return $this;
    }

    /**
     * @return string
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * @param string $to
     * @return $this
     */
    public function setTo($to)
    {
        $this->to = $to;
        
        return $this;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        
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
     * @return $this
     */
    public function setWebsite($website)
    {
        $this->website = $website;

        return $this;
    }
}
