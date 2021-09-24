<?php

namespace Oro\Bundle\CMSBundle\Command;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGStyleType;
use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\Entity\TextContentVariant;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\EntityUrlGenerator;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Oro\Bundle\SecurityBundle\Tools\AbstractFieldsSanitizer;
use Oro\Bundle\SecurityBundle\Tools\FieldsSanitizerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Sanitizes WYSIWYG style fields by stripping HTML tags from them.
 */
class SanitizeWysiwygStyleFieldsCommand extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected static $defaultName = 'oro:cms:wysiwyg:sanitize:styles';

    private ManagerRegistry $managerRegistry;

    /** @var iterable<FieldsSanitizerInterface> */
    private iterable $fieldsSanitizers;

    private EntityConfigManager $entityConfigManager;

    private ConfigManager $configManager;

    private TranslatorInterface $translator;

    private EntityUrlGenerator $entityUrlGenerator;

    private int $chunkSize;

    private array $occurrencesCount = [];

    private array $sectionIsAdded = [];

    /**
     * @param ManagerRegistry $managerRegistry
     * @param iterable<FieldsSanitizerInterface> $fieldsSanitizers
     * @param EntityConfigManager $entityConfigManager
     * @param ConfigManager $configManager
     * @param TranslatorInterface $translator
     * @param EntityUrlGenerator $entityUrlGenerator
     * @param int $chunkSize
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        iterable $fieldsSanitizers,
        EntityConfigManager $entityConfigManager,
        ConfigManager $configManager,
        TranslatorInterface $translator,
        EntityUrlGenerator $entityUrlGenerator,
        int $chunkSize = 1000
    ) {
        parent::__construct();

        $this->managerRegistry = $managerRegistry;
        $this->fieldsSanitizers = $fieldsSanitizers;
        $this->entityConfigManager = $entityConfigManager;
        $this->configManager = $configManager;
        $this->translator = $translator;
        $this->entityUrlGenerator = $entityUrlGenerator;
        $this->chunkSize = $chunkSize;
        $this->logger = new NullLogger();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', false, InputOption::VALUE_NONE, 'Force the execution')
            ->addOption('dry-run', false, InputOption::VALUE_NONE, 'List the entities to be affected')
            ->addOption('entity-class', '', InputOption::VALUE_OPTIONAL, 'Restrict the scanning scope by entity class')
            ->setDescription('Sanitizes WYSIWYG style fields.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command scans entities with WYSIWYG fields and removes tags from WYSIWYG style fields.

  <info>php %command.full_name%</info>

The <info>--force</info> option is just a safety switch. The command will exit if this option is not used.

  <info>php %command.full_name% --force</info>

The <info>--dry-run</info> option can be used to list the links to edit pages of the entities which have tags in 
WYSIWYG style field.

  <info>php %command.full_name% --dry-run</info>

The <info>--entity-class</info> option restricts the scanning scope by the specified entity class.

  <info>php %command.full_name% --entity-class='Oro\CMSBundle\Entity\Page'</info>
HELP
            )
            ->addUsage('--force ')
            ->addUsage('--dry-run');
    }


    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->sectionIsAdded = $this->occurrencesCount = [];
        $force = (bool)$input->getOption('force');
        $dryRun = (bool)$input->getOption('dry-run');
        $restrictByClassName = (string)$input->getOption('entity-class');

        $symfonyStyle = new SymfonyStyle($input, $output);
        if (!$force && !$dryRun) {
            $symfonyStyle->caution('Database backup is recommended before executing this command.');

            $symfonyStyle->text(
                [
                    'To force execution run command with <info>--force</info> option:',
                    sprintf('    <info>%s --force</info>', $this->getName()),
                ]
            );

            return 1;
        }

        $symfonyStyle->text('Scanning ...');

        /** @var EntityManager $manager */
        $manager = $this->managerRegistry->getManager();

        /** @var ClassMetadata|ClassMetadataInfo $classMetadata */
        foreach ($manager->getMetadataFactory()->getAllMetadata() as $classMetadata) {
            if (!$this->isClassMetadataApplicable($classMetadata, $restrictByClassName)) {
                continue;
            }

            $className = $classMetadata->getName();

            foreach ($this->fieldsSanitizers as $fieldsSanitizer) {
                $affectedFieldsByEntityId = $fieldsSanitizer->sanitizeByFieldType(
                    $className,
                    WYSIWYGStyleType::TYPE,
                    AbstractFieldsSanitizer::MODE_STRIP_TAGS,
                    [],
                    !$dryRun,
                    $this->chunkSize
                );

                foreach ($affectedFieldsByEntityId as $entityId => $affectedFields) {
                    $this->outputAffectedFields($className, $entityId, $affectedFields, $symfonyStyle);
                }
            }
        }

        $this->outputSuccessMessage($symfonyStyle, $dryRun);

        return 0;
    }

    private function isClassMetadataApplicable(ClassMetadata $classMetadata, string $restrictByClassName): bool
    {
        if ($restrictByClassName && $classMetadata->getName() !== $restrictByClassName) {
            return false;
        }

        if (!$classMetadata instanceof ClassMetadataInfo || $classMetadata->isMappedSuperclass) {
            return false;
        }

        if (is_a($classMetadata->getName(), AbstractLocalizedFallbackValue::class, true)) {
            // Skips localized fallback value entities as they will be processed separately for each entity that has
            // relations on them.
            return false;
        }

        $idFields = $classMetadata->getIdentifierFieldNames();
        if (!$idFields || count($idFields) > 1) {
            // Skips entities with missing or composite identifiers.
            return false;
        }

        return true;
    }

    private function getEntityLabel(string $className): string
    {
        if ($this->entityConfigManager->hasConfig($className)) {
            $entityConfig = $this->entityConfigManager->getEntityConfig('entity', $className);
            $label = $this->translator->trans($entityConfig->get('plural_label'));
        } else {
            // Entity is not configurable, so use just plain entity name as a label.
            [, $label] = ConfigHelper::getModuleAndEntityNames($className);
        }

        return sprintf('%s (%s)', $label, $className);
    }

    /**
     * @param string $className
     * @param int|string $entityId
     * @param array $affectedFields
     * @param SymfonyStyle $symfonyStyle
     */
    private function outputAffectedFields(
        string $className,
        $entityId,
        array $affectedFields,
        SymfonyStyle $symfonyStyle
    ): void {
        if (!$affectedFields) {
            return;
        }

        $this->occurrencesCount[$className][$entityId] = ($this->occurrencesCount[$className][$entityId] ?? 0)
            + count($affectedFields);

        $this->mapTextContentVariantToContentBlock($className, $entityId, $affectedFields);

        if (empty($this->sectionIsAdded[$className])) {
            $symfonyStyle->section($this->getEntityLabel($className));
            $this->sectionIsAdded[$className] = true;
        }

        $translatedAffectedFields = [];
        foreach ($affectedFields as $fieldName) {
            $wysiwygFieldName = basename($fieldName, WYSIWYGStyleType::TYPE_SUFFIX);
            $translatedAffectedFields[$wysiwygFieldName] = $this->translator->trans(
                ConfigHelper::getTranslationKey('entity', 'label', $className, $wysiwygFieldName)
            );
        }

        $symfonyStyle->text(
            sprintf(
                '* %s, affected field(s): %s',
                $this->getEntityUrl($className, $entityId),
                implode(', ', $translatedAffectedFields)
            )
        );
    }

    /**
     * Changes class name, entity id and affected fields for Content Block if $className is
     * {@see \Oro\Bundle\CMSBundle\Entity\TextContentVariant} as this entity is a sub entity of ContentBlock.
     *
     * @param string $className
     * @param int|string $entityId
     * @param array $affectedFields
     */
    private function mapTextContentVariantToContentBlock(string &$className, &$entityId, array &$affectedFields): void
    {
        if ($className !== TextContentVariant::class) {
            return;
        }

        /** @var EntityManager $entityManager */
        $entityManager = $this->managerRegistry->getManagerForClass($className);
        $contentBlockId = $entityManager
            ->createQueryBuilder()
            ->select('IDENTITY(e.contentBlock)')
            ->from(TextContentVariant::class, 'e')
            ->where($entityManager->getExpressionBuilder()->eq('e.id', ':id'))
            ->setParameter('id', $entityId)
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SCALAR);

        if ($contentBlockId) {
            $affectedFields = array_map(
                static fn ($field) => $field === 'contentStyle' ? 'contentVariants' : $field,
                $affectedFields
            );
            $className = ContentBlock::class;
            $entityId = (int)array_shift($contentBlockId);
        }
    }

    /**
     * @param string $className
     * @param int|string $entityId
     *
     * @return string
     */
    private function getEntityUrl(string $className, $entityId): string
    {
        try {
            $url = $this->entityUrlGenerator->generate($className, 'update', ['id' => $entityId]);
        } catch (RouteNotFoundException | MissingMandatoryParametersException | InvalidParameterException $exception) {
            $this->logger->warning(
                sprintf(
                    'Failed to generate a URL for entity "%s" with id "%s" during results output of %s command',
                    $className,
                    $entityId,
                    self::getDefaultName()
                ),
                ['exception' => $exception]
            );
        }

        if (empty($url)) {
            return sprintf('<no link, id=%s>', $entityId);
        }

        return $this->configManager->get('oro_ui.secure_application_url') . $url;
    }

    private function outputSuccessMessage(SymfonyStyle $symfonyStyle, bool $dryRun): void
    {
        $occurrences = 0;
        $entities = 0;
        foreach ($this->occurrencesCount as $occurrencesByEntitiesIds) {
            $occurrences += array_sum($occurrencesByEntitiesIds);
            $entities += count($occurrencesByEntitiesIds);
        }

        if (!$this->occurrencesCount) {
            $successMessage = 'Entities that need sanitizing were not found.';
        } elseif ($dryRun) {
            $successMessage = sprintf(
                '%d occurrences across %d entities that need sanitizing were found.',
                $occurrences,
                $entities
            );
        } else {
            $successMessage = sprintf(
                '%d occurrences across %d entities were sanitized.',
                $occurrences,
                $entities
            );
        }

        $symfonyStyle->success($successMessage);
    }
}
