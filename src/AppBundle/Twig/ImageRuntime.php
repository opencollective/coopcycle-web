<?php

namespace AppBundle\Twig;

use AppBundle\Entity\TaskImage;
use Intervention\Image\ImageManagerStatic;
use League\Flysystem\Filesystem;
use Twig\Extension\RuntimeExtensionInterface;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class ImageRuntime implements RuntimeExtensionInterface
{
    private $uploaderHelper;
    private $taskImagesFilesystem;

    public function __construct(
        UploaderHelper $uploaderHelper,
        PropertyMappingFactory $propertyMappingFactory,
        Filesystem $taskImagesFilesystem)
    {
        $this->uploaderHelper = $uploaderHelper;
        $this->propertyMappingFactory = $propertyMappingFactory;
        $this->taskImagesFilesystem = $taskImagesFilesystem;
    }

    public function toBase64($image)
    {
        if ($image instanceof TaskImage) {

            $propertyMapping = $this->propertyMappingFactory->fromField($image, 'file');
            $directoryName = $propertyMapping->getDirectoryNamer()->directoryName($image, $propertyMapping);
            $imagePath = sprintf('%s/%s', $directoryName, $image->getImageName());

            return (string) ImageManagerStatic::make($this->taskImagesFilesystem->read($imagePath))->encode('data-url');
        }

        // return 'yo';
        // $companyLogo = $this->settingsManager->get('company_logo');

        // if (!empty($companyLogo)) {
        //     return $this->packages->getUrl(sprintf('images/assets/%s', $companyLogo));
        // }
    }
}
