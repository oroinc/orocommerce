<?php

namespace Oro\Bundle\ScopeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\ScopeBundle\Model\ExtendScope;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * Class Scope
 * @Config()
 *
 * @ORM\Table("oro_scope")
 * @ORM\Entity(repositoryClass="Oro\Bundle\ScopeBundle\Entity\Repository\ScopeRepository")
 */
class Scope extends ExtendScope
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * Non persistent attribute can be used when needed for example in twig templates
     *
     * @var string
     */
    private $label;

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
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }
}
