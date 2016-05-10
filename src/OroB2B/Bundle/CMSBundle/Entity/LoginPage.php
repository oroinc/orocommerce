<?php

namespace OroB2B\Bundle\CMSBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

use OroB2B\Bundle\CMSBundle\Model\ExtendLoginPage;

/**
 * @ORM\Table(name="orob2b_cms_login_page")
 * @ORM\Entity()
 * @Config(
 *      routeName="orob2b_cms_loginpage_index",
 *      routeUpdate="orob2b_cms_loginpage_update",
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-sign-in"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          }
 *      }
 * )
 */
class LoginPage extends ExtendLoginPage
{
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
     * @ORM\Column(name="top_content", type="text", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $topContent;

    /**
     * @var string
     *
     * @ORM\Column(name="bottom_content", type="text", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $bottomContent;

    /**
     * @var string
     *
     * @ORM\Column(name="css", type="text", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $css;

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTopContent()
    {
        return $this->topContent;
    }

    /**
     * @param string|null $topContent
     * @return $this
     */
    public function setTopContent($topContent = null)
    {
        $this->topContent = $topContent;

        return $this;
    }

    /**
     * @return string
     */
    public function getBottomContent()
    {
        return $this->bottomContent;
    }

    /**
     * @param string|null $bottomContent
     * @return $this
     */
    public function setBottomContent($bottomContent = null)
    {
        $this->bottomContent = $bottomContent;

        return $this;
    }

    /**
     * @return string
     */
    public function getCss()
    {
        return $this->css;
    }

    /**
     * @param string|null $css
     * @return $this
     */
    public function setCss($css = null)
    {
        $this->css = $css;

        return $this;
    }
}
