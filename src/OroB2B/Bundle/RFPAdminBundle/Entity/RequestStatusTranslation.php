<?php

namespace OroB2B\Bundle\RFPAdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Gedmo\Translatable\Entity\MappedSuperclass\AbstractPersonalTranslation;

/**
 * @ORM\Table(name="orob2b_rfp_status_translation", indexes={
 *      @ORM\Index(name="orob2b_rfp_status_trans_idx", columns={"locale", "object_id", "field"})
 * })
 * @ORM\Entity(repositoryClass="Gedmo\Translatable\Entity\Repository\TranslationRepository")
 */
class RequestStatusTranslation extends AbstractPersonalTranslation
{
    /**
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\RFPAdminBundle\Entity\RequestStatus", inversedBy="translations")
     * @ORM\JoinColumn(name="object_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $object;
}
