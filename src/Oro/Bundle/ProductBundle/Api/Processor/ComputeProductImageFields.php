<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;

/**
 * Computes a value of "types" and "files" field for Product Image entity.
 */
class ComputeProductImageFields implements ProcessorInterface
{
    private const TYPES_FIELD = 'types';
    private const FILES_FIELD  = 'files';
    private const CONTENT_FIELD  = 'content';

    /** @var AttachmentManager */
    private $attachmentManager;

    /** @var ImageTypeProvider*/
    private $typeProvider;

    /** @var FileManager */
    private $fileManager;

    /**
     * @param AttachmentManager $attachmentManager
     * @param ImageTypeProvider $typeProvider
     */
    public function __construct(AttachmentManager $attachmentManager, ImageTypeProvider $typeProvider, FileManager $fileManager)
    {
        $this->attachmentManager = $attachmentManager;
        $this->typeProvider = $typeProvider;
        $this->fileManager = $fileManager;
    }

    function debug_to_console($data) {
        $output = $data;
        if (is_array($output))
            $output = implode(',', $output);
    
        echo "<script>console.log('Debug Objects: " . $output . "' );</script>";
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();

        if (!$context->isFieldRequestedForCollection(self::TYPES_FIELD, $data)
            && !$context->isFieldRequestedForCollection(self::FILES_FIELD, $data)
        ) {
            return;
        }

        $typePathFieldName = $context->getResultFieldName(self::TYPES_FIELD);
        $filesFieldName = $context->getResultFieldName(self::FILES_FIELD);

        foreach ($data as $key => $item) {
            $types = [];
            foreach ($item[$typePathFieldName] as $type) {
                $types[] = $type['type'];
            }

            if ($context->isFieldRequestedForCollection(self::TYPES_FIELD, $data)) {
                $data[$key][self::TYPES_FIELD] = $types;
            }
            if ($context->isFieldRequestedForCollection($filesFieldName, $data)) {
                $image = $item['image'];
                $data[$key][$filesFieldName] = $this->getImageUrls($image['id'], $image['filename'], $types);
            }
        }

        $context->setData($data);
        return;
    }

    /**
     * @param int $imageId
     * @param string $filename
     * @param array $imageTypes
     * @return array
     */
    private function getImageUrls(int $imageId, string $filename, array $imageTypes): array
    {
        if (empty($imageTypes) || empty($filename)) {
            return [];
        }

        $allTypes = $this->typeProvider->getImageTypes();
        $result = [];
        foreach ($imageTypes as $imageType) {
            $typeDimensions = $allTypes[$imageType]->getDimensions();
            foreach ($typeDimensions as $dimensionName => $dimensionConfig) {
                if (!array_key_exists($dimensionName, $result)) {
                    $result[$dimensionName] = [
                        'url' => $this->attachmentManager->getFilteredImageUrlByIdAndFilename(
                            $imageId,
                            $filename,
                            $dimensionName
                        ),
                        'maxWidth' => $dimensionConfig->getWidth(),
                        'maxHeight' => $dimensionConfig->getHeight(),
                        'dimension' => $dimensionName
                    ];
                }
                $result[$dimensionName]['types'][] = $imageType;
            }
        }

        return array_values($result);
    }
}
