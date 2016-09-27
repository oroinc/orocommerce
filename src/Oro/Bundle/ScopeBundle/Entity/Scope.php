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
 * @ORM\Entity
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
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
