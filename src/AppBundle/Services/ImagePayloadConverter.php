<?php

namespace AppBundle\Services;

use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class ImagePayloadConverter
 */
class ImagePayloadConverter
{
    /**
     * @var string
     */
    protected $imagesDir;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * ImagePayloadConverter constructor.
     *
     * @param \Psr\Log\LoggerInterface $logger
     * @param string $imagesDir
     */
    public function __construct(LoggerInterface $logger, $imagesDir)
    {
        $this->logger = $logger;
        $this->imagesDir = $imagesDir;
    }

    /**
     * Writes image contents as a physical file.
     *
     * @param string $contents
     *   Base64 encoded image contents.
     * @param string $filePath
     *   Image path.
     *
     * @return bool
     */
    public function writeImage($contents, $filePath) {
        $fs = new Filesystem();

        $path = $this->imagesDir.'/'.$filePath;

        try {
            $fs->mkdir(dirname($path));
        }
        catch (IOException $exception) {
            $this->logger->error("Failed to prepare directory with exception '{$exception->getMessage()}'");

            return false;
        }

        try {
            $fs->dumpFile($path, base64_decode($contents));

            if (function_exists('getimagesize') && getimagesize($path)) {
                return true;
            }

            $fs->remove($path);
        }
        catch (IOException $exception) {
            $this->logger->error("Failed to store image payload with exception '{$exception->getMessage()}'");
        }

        return false;
    }
}
