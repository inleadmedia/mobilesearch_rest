<?php

namespace AppBundle\Controller;

use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\File;

use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;

class ImageController extends Controller
{
    protected $filesStorageDir = '../web/storage/images';
    protected $response;

    /**
     * @Route("/files/{agency}/{filename}")
     */
    function imageAction(Request $request, $agency, $filename)
    {
        $this->response = new Response();

        $filePath = $this->filesStorageDir . '/' . $agency . '/' . $filename;
        $resize = $request->query->get('resize');

        $dimensions = $this->getSizeFromParam($resize);
        if (!empty($dimensions) && $this->checkThumbnailSubdir($resize, $agency)) {
            $resizedFilePath = $this->filesStorageDir . '/' . $agency . '/' . $resize . '/' . $filename;

            $fs = new Filesystem();
            if ($fs->exists($resizedFilePath)) {
                $filePath = $resizedFilePath;
            }
            elseif ($this->resizeImage($filePath, $resizedFilePath, $dimensions))
            {
                $filePath = $resizedFilePath;
            }
        }

        $this->serveImage($filePath);

        return $this->response;
    }

    protected function resizeImage($source, $target, array $wantedDimensions)
    {
        $imagine = new Imagine();
        try
        {
            $image = $imagine->open($source);
            $imageSize = $image->getSize();
            $originalSize = array(
                'width' => $imageSize->getWidth(),
                'height' => $imageSize->getHeight(),
            );
            $targetBoundingBox = $this->getResizeDimensions($originalSize, $wantedDimensions);
            $image
                ->resize($targetBoundingBox)
                ->save($target);
        }
        catch(Imagine\Exception\Exception $e)
        {
            return FALSE;
        }

        return TRUE;
    }

    protected function getResizeDimensions(array $originalSize, array $targetSize)
    {
        $width = $targetSize['width'];
        $height = $targetSize['height'];

        if (empty($height))
        {
            $height = $originalSize['height'] / ($originalSize['width'] / $targetSize['width']);
        }
        elseif (empty($width))
        {
            $width = $originalSize['width'] / ($originalSize['height'] / $targetSize['height']);
        }

        $boundingBox = new Box($width, $height);

        return $boundingBox;
    }

    protected function serveImage($path)
    {
        try
        {
            $file = new File($path);
            $this->response->headers->set('Content-Type', $file->getMimeType());
            $this->response->setStatusCode(Response::HTTP_OK);
            $this->response->setContent(file_get_contents($path));
        }
        catch (FileNotFoundException $e)
        {
            $this->response->setStatusCode(Response::HTTP_NOT_FOUND);
            $this->response->setContent('File not found.');
        }
    }

    protected function checkThumbnailSubdir($name, $agency, $create = TRUE)
    {
        $fs = new Filesystem();
        $path = $this->filesStorageDir . '/' . $agency . '/' . $name;
        $exists = $fs->exists($path);

        if (!$exists && $create) {
            try
            {
                $fs->mkdir($path);
                $exists = TRUE;
            }
            catch (IOException $e)
            {
                return FALSE;
            }
        }

        return $exists;
    }

    protected function getSizeFromParam($resizeParam)
    {
        $dimensions = array();
        if (!empty($resizeParam) && preg_match('/^(\d+)?x(\d+)?$/', $resizeParam)) {
            $size = explode('x', $resizeParam);
            if (!empty($size[0]) || !empty($size[1]))
            {
                $dimensions['width'] = $size[0];
                $dimensions['height'] = $size[1];
            }
        }

        return $dimensions;
    }
}