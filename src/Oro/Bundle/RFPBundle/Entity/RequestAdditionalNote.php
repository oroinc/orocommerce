<?php

namespace Oro\Bundle\RFPBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;

/**
 * Additional Note for Request.
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_rfp_request_add_note')]
#[Config]
class RequestAdditionalNote implements DatesAwareInterface, ExtendEntityInterface
{
    use DatesAwareTrait;
    use ExtendEntityTrait;

    const TYPE_CUSTOMER_NOTE = 'customer_note';
    const TYPE_SELLER_NOTE = 'seller_note';

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Request::class, inversedBy: 'requestAdditionalNotes')]
    #[ORM\JoinColumn(name: 'request_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Request $request = null;

    #[ORM\Column(name: 'type', type: Types::STRING, length: 100)]
    protected ?string $type = null;

    #[ORM\Column(name: 'author', type: Types::STRING, length: 100)]
    protected ?string $author = null;

    #[ORM\Column(name: 'user_id', type: Types::INTEGER)]
    protected ?int $userId = null;

    #[ORM\Column(name: 'text', type: Types::TEXT)]
    protected ?string $text = null;

    /**
     * @return array
     */
    public function getAllowedTypes()
    {
        return [self::TYPE_CUSTOMER_NOTE, self::TYPE_SELLER_NOTE];
    }

    /**
     * @param string $type
     * @return bool
     */
    public function isTypeAllowed($type)
    {
        return in_array($type, $this->getAllowedTypes(), true);
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param Request|null $request
     * @return $this
     */
    public function setRequest(?Request $request = null)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param string $type
     * @return $this
     * @throws \LogicException
     */
    public function setType($type)
    {
        if (!$this->isTypeAllowed($type)) {
            throw new \LogicException(sprintf('Type "%s" is not allowed', $type));
        }

        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $author
     * @return $this
     */
    public function setAuthor($author)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param int $userId
     * @return $this
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
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
}
