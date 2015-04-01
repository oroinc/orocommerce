<?php

namespace OroB2B\Bundle\RFPBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Gedmo\Translatable\Entity\MappedSuperclass\AbstractTranslation;

/**
 * @ORM\Table(name="orob2b_rfp_status_translation", indexes={
 *      @ORM\Index(name="orob2b_rfp_status_trans_idx", columns={"locale", "object_class", "field", "foreign_key"})
 * })
 * @ORM\Entity(repositoryClass="Gedmo\Translatable\Entity\Repository\TranslationRepository")
 */
class RequestStatusTranslation extends AbstractTranslation
{
    /**
     * @var string $foreignKey
     *
     * @ORM\Column(name="foreign_key", type="string", length=64)
     */
    protected $foreignKey;

    /**
     * @var string $content
     *
     * @ORM\Column(type="string", length=255)
     */
    protected $content;
}
