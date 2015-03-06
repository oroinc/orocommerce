<?php

namespace OroB2B\Bundle\FallbackBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

/**
 * @ORM\Table(name="orob2b_fallback_locale_value")
 * @ORM\Entity
 * @Config
 */
class LocalizedFallbackValue
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
     * @var string|null
     *
     * @ORM\Column(name="fallback", type="string", length=64, nullable=true)
     */
    protected $fallback;

    /**
     * @var string|null
     *
     * @ORM\Column(name="string", type="string", length=255, nullable=true)
     */
    protected $string;

    /**
     * @var string|null
     *
     * @ORM\Column(name="text", type="text", nullable=true)
     */
    protected $text;

    /**
     * @var Locale|null
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\WebsiteBundle\Entity\Locale")
     * @ORM\JoinColumn(name="locale_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $locale;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $fallback
     * @return $this
     */
    public function setFallback($fallback)
    {
        $this->fallback = $fallback;

        return $this;
    }

    /**
     * @return string
     */
    public function getFallback()
    {
        return $this->fallback;
    }

    /**
     * @param string $string
     * @return $this
     */
    public function setString($string)
    {
        $this->string = $string;

        return $this;
    }

    /**
     * @return string
     */
    public function getString()
    {
        return $this->string;
    }

    /**
     * @param string $text
     * @return $this
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @return Locale|null
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param Locale|null $locale
     * @return $this
     */
    public function setLocale(Locale $locale = null)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if ($this->string) {
            return $this->string;
        } elseif ($this->text) {
            return $this->text;
        } else {
            return '';
        }
    }
}
