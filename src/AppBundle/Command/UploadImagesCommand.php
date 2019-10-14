<?php

namespace AppBundle\Command;

use League\Flysystem\Filesystem;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UploadImagesCommand extends ContainerAwareCommand
{
    private $finder;
    private $io;

    public function __construct(
        Filesystem $localRestaurantImagesFilesystem,
        Filesystem $remoteRestaurantImagesFilesystem)
    {
        $this->localRestaurantImagesFilesystem = $localRestaurantImagesFilesystem;
        $this->remoteRestaurantImagesFilesystem = $remoteRestaurantImagesFilesystem;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('coopcycle:images:upload')
            ->setDescription('Upload images.');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $files = $this->localRestaurantImagesFilesystem->listContents('', true);

        foreach ($files as $file) {
            if ($file['type'] === 'file') {
                $this->io->text(sprintf('Uploading file %s', $file['path']));
                $stream = $this->localRestaurantImagesFilesystem->readStream($file['path']);
                $this->remoteRestaurantImagesFilesystem->putStream($file['path'], $stream);
            }
        }
    }
}
