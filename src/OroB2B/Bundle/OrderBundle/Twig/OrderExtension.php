<?php

namespace OroB2B\Bundle\OrderBundle\Twig;

use OroB2B\Bundle\OrderBundle\Formatter\SourceDocumentFormatter;

class OrderExtension extends \Twig_Extension
{
    const NAME = 'orob2b_order_order';

    /**
     * @var SourceDocumentFormatter
     */
    protected $sourceDocumentFormatter;

    /**
     * @param SourceDocumentFormatter $sourceDocumentFormatter
     */
    public function __construct(SourceDocumentFormatter $sourceDocumentFormatter)
    {
        $this->sourceDocumentFormatter = $sourceDocumentFormatter;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter(
                'orob2b_order_format_source_document',
                [$this, 'formatSourceDocument'],
                ['is_safe' => ['html']]
            )
        ];
    }

    /**
     * @param string $sourceEntityClass
     * @param int $sourceEntityId
     * @param string $sourceEntityIdentifier
     *
     * @return string
     */
    public function formatSourceDocument($sourceEntityClass, $sourceEntityId, $sourceEntityIdentifier)
    {
        return $this->sourceDocumentFormatter->format($sourceEntityClass, $sourceEntityId, $sourceEntityIdentifier);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
