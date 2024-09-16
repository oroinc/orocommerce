<?php

namespace Oro\Bundle\CMSBundle\WYSIWYG;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Component\Validator\Constraints\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Creates files from digital asset used in WYSIWYG fields.
 */
class DigitalAssetTwigFunctionProcessor implements WYSIWYGTwigFunctionProcessorInterface
{
    private AclHelper $aclHelper;
    private ValidatorInterface $validator;

    public function __construct(AclHelper $aclHelper, ValidatorInterface $validator)
    {
        $this->aclHelper = $aclHelper;
        $this->validator = $validator;
    }

    /**
     * {@inheritDoc}
     */
    public function getApplicableMapping(): array
    {
        return [
            self::FIELD_CONTENT_TYPE => ['wysiwyg_image', 'wysiwyg_file'],
            self::FIELD_STYLES_TYPE => ['wysiwyg_image', 'wysiwyg_file'],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function processTwigFunctions(WYSIWYGProcessedDTO $processedDTO, array $twigFunctionCalls): bool
    {
        $ownerEntityId = $processedDTO->requireOwnerEntityId();
        if (!\is_numeric($ownerEntityId)) {
            return false;
        }

        $ownerEntityId = (int)$ownerEntityId;
        $ownerEntityClass = $processedDTO->requireOwnerEntityClass();
        $ownerEntityField = $processedDTO->requireOwnerEntityFieldName();

        $actualFileCalls = $this->getFileCalls($twigFunctionCalls);

        $em = $processedDTO->getProcessedEntity()->getEntityManager();
        $repository = $em->getRepository(DigitalAsset::class);

        $isFlushNeeded = false;
        $digitalAssets = [];
        $currentFiles = $repository->findForEntityField($ownerEntityClass, $ownerEntityId, $ownerEntityField);
        foreach ($currentFiles as $file) {
            /** @var DigitalAsset $digitalAsset */
            $digitalAsset = $file->getDigitalAsset();
            $digitalAssets[$digitalAsset->getId()] = $digitalAsset;

            if (!isset($actualFileCalls[$file->getUuid()])) {
                $em->remove($file);
                $isFlushNeeded = true;
            } else {
                unset($actualFileCalls[$file->getUuid()]);
            }
        }

        if ($actualFileCalls) {
            $notLoadedIds = \array_unique(\array_diff(\array_values($actualFileCalls), \array_keys($digitalAssets)));
            if ($notLoadedIds) {
                $digitalAssets += $repository->findByIds($notLoadedIds, $this->aclHelper);
            }

            foreach ($actualFileCalls as $uuid => $digitalAssetId) {
                if (isset($digitalAssets[$digitalAssetId])) {
                    $sourceFile = $digitalAssets[$digitalAssetId]->getSourceFile();

                    $newFile = clone $sourceFile;
                    $newFile->setParentEntityClass($ownerEntityClass);
                    $newFile->setParentEntityId($ownerEntityId);
                    $newFile->setParentEntityFieldName($ownerEntityField);
                    $newFile->setDigitalAsset($digitalAssets[$digitalAssetId]);
                    $newFile->setUuid($uuid);

                    $em->persist($newFile);
                    $isFlushNeeded = true;
                }
            }
        }

        return $isFlushNeeded;
    }

    /**
     * {@inheritDoc}
     */
    public function onPreRemove(WYSIWYGProcessedDTO $processedDTO): bool
    {
        $ownerEntityId = $processedDTO->requireOwnerEntityId();
        if (!\is_numeric($ownerEntityId)) {
            return false;
        }

        $em = $processedDTO->getProcessedEntity()->getEntityManager();
        $currentFiles = $em->getRepository(File::class)->findForEntityField(
            $processedDTO->requireOwnerEntityClass(),
            (int)$ownerEntityId
        );
        if (!$currentFiles) {
            return false;
        }

        foreach ($currentFiles as $file) {
            $em->remove($file);
        }

        return true;
    }

    /**
     * @param array $twigFunctionCalls
     *
     * @return array [uuid => digital asset id, ...]
     */
    private function getFileCalls(array $twigFunctionCalls): array
    {
        $actualCalls = [];
        if ($twigFunctionCalls) {
            $constraint = new Uuid(['versions' => [Uuid::V4_RANDOM], 'strict' => true]);
            foreach ($twigFunctionCalls as $calls) {
                foreach ($calls as $callArguments) {
                    foreach ($callArguments as [$digitalAssetId, $uuid]) {
                        if ($digitalAssetId) {
                            $uuid = strtolower(trim($uuid));
                            if ($this->validator->validate($uuid, $constraint)->count() === 0) {
                                $actualCalls[$uuid] = (int)$digitalAssetId;
                            }
                        }
                    }
                }
            }
        }

        return $actualCalls;
    }
}
