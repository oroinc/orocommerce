<?php

namespace Oro\Bundle\ScopeBundle\Entity;

/**
 * Class Scope
 *
 * @ORM\Table("oro_scope")
 * @ORM\Entity
 */
class Scope
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
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
