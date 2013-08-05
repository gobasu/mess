<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\file;

class ImageException extends \Exception{}
class ImageFileNotFoundException extends ImageException{}
class ImageUnsupportedTypeException extends ImageException{}
class ImageGDNotFoundException extends ImageException{}

class Image
{

    /**
     * Class constructor
     *
     * @param string $file
     */
    public function __construct($file, $transparency = true)
    {
        //check for gd
        if (!function_exists("gd_info")) {
            throw new ImageGDNotFoundException('GD not found in you PHP installation. Please install GD befor using this library');
        }
        $this->transparency = $transparency;

        $this->file = $file;

        if (!file_exists($this->file) || !is_readable($this->file)) {
            throw new ImageFileNotFoundException(sprintf('Image file `%s` is not readable', $this->file));
        }

        //try to findout image format by exif
        if (function_exists('exif_imagetype')) {
            $type = exif_imagetype($this->file);
            switch ($type) {
                case IMAGETYPE_GIF: {
                    $this->type = self::IMAGE_GIF;
                    break;
                }
                case IMAGETYPE_JPEG: {
                    $this->type = self::IMAGE_JPG;
                    break;
                }
                case IMAGETYPE_PNG: {
                    $this->type = self::IMAGE_PNG;
                    break;
                }
                default: {
                    throw new ImageUnsupportedTypeException('Image type is unspurrted');
                 }
            }
        }
        //silly function name xD to get type

        $size = getimagesize($this->file);

        if (!$this->type) {
            switch ($size['mime']) {
                case 'image/jpeg': {
                    $this->type = self::IMAGE_JPG;
                    break;
                }
                case 'image/png': {
                    $this->type = self::IMAGE_PNG;
                    break;
                }
                case 'image/gif': {
                    $this->type = self::IMAGE_GIF;
                    break;
                }
                default: {
                    throw new ImageUnsupportedTypeException('Image type is unspurrted');
                }
            }
        }

        switch ($this->type) {
            case self::IMAGE_JPG: {
                $this->image = imagecreatefromjpeg($this->file);
                $this->mimeType = 'image/jpeg';
                break;
            }
            case self::IMAGE_GIF: {
                $this->image = imagecreatefromgif($this->file);
                $this->mimeType = 'image/gif';
                if ($this->transparency) $this->preserveTransparency($this->image);
                break;
            }
            case self::IMAGE_PNG: {
                $this->image = imagecreatefrompng($this->file);
                $this->mimeType = 'image/png';
                if ($this->transparency) $this->preserveTransparency($this->image);
                break;
            }
        }

        $this->currentWidth = $size[0];
        $this->currentHeight = $size[1];

    }

    public function getExtension()
    {
        switch ($this->type) {
            case self::IMAGE_BMP:
                return 'bmp';
            case self::IMAGE_GIF:
                return 'gif';
            case self::IMAGE_JPG:
                return 'jpg';
            case self::IMAGE_PNG:
                return 'png';
        }
    }

    public function getMimeType()
    {
        return $this->mimeType;
    }

    public function __destruct()
    {
        if (is_resource($this->image)) {
            imagedestroy($this->image);
        }
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->currentWidth;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return $this->currentHeight;
    }

    /**
     * Resizes image
     *
     * @param int $maxWidth
     * @param int $maxHeight
     */
    public function resize($width = null, $height = null, $resizeType = self::RESIZE_MAXIMAL)
    {
        if (!$width && !$height) {
            throw new ImageException(__CLASS__ . '::' . __METHOD__ . '() expect width or/and height parameter(s) but none passed');
        }

        $image = $this->image;
        if ($resizeType == self::RESIZE_MAXIMAL) {
            $newSize = $this->calculateNewImageSize($width, $height);
        } else {
            $newSize = $this->calculateNewMinImageSize($width, $height);
        }

        $this->image = $this->createRawImage($newSize['width'], $newSize['height']);

        imagecopyresampled($this->image, $image, 0, 0, 0, 0, $newSize['width'], $newSize['height'], $this->currentWidth, $this->currentHeight);

        $this->currentWidth = $newSize['width'];
        $this->currentHeight = $newSize['height'];

        return $this;
    }


    private function calculateNewMinImageSize($minWidth, $minHeight)
    {
        if (!$minHeight || !$minWidth) {
            throw new ImageException('Expecting both width and height when using flag Image::RESIZE_MINIMAL');
        }

        $newSize = array('width' => $this->currentWidth, 'height' => $this->currentHeight);

        $newSize = $this->calculateNewImageSizeByWidth($minWidth);
        if ($minHeight > $newSize['height']) {
            $newSize = $this->calculateNewImageSizeByHeight($minHeight);
        }

        return $newSize;
    }

    /**
     * Crops image from given x and y to desirable size
     *
     * @param $startX
     * @param $startY
     * @param $width
     * @param $height
     * @return Image
     */
    public function crop($startX, $startY, $width, $height)
    {
        //make it secure
        $width > $this->currentWidth ? $width = $this->currentWidth : false;
        $height > $this->currentHeight ? $height = $this->currentHeight : false;

        $startX + $width > $this->currentWidth ? $startX = $this->currentWidth - $width : false;
        $startY + $height > $this->currentHeight ? $startY = $this->currentHeight - $width : false;

        $startY < 0 ? $startY = 0 : false;
        $startX < 0 ? $startX = 0 : false;

        $image = $this->image;
        $this->image = $this->createRawImage($width, $height);

        imagecopyresampled($this->image, $image, 0, 0, $startX, $startY, $width, $height, $width, $height);

        $this->currentHeight = $height;
        $this->currentWidth = $width;

        return $this;
    }

    /**
     * Rotates image CW (clockwise) or CCW(counter clockwise)
     *
     * @param string $rotate (CW, CCW)
     * @return Image
     */
    public function rotate($rotate = 'CW')
    {
        switch ($rotate) {
            case 'CW': {
                $this->image = imagerotate($this->image, -90, 0);
                break;
            }

            case 'CCW':
            default: {
                $this->image = imagerotate($this->image, 90, 0);
                break;
            }
        }
        $width = $this->currentWidth;
        $this->currentWidth = $this->currentHeight;
        $this->currentHeight = $width;
        return $this;

    }

    /**
     * Crops image from its center
     *
     * @param $width
     * @param $height
     * @return Image
     */
    public function cropFromCenter($width, $height)
    {
        $startX = round(($this->currentWidth - $width) / 2);
        $startY = round(($this->currentHeight - $height) / 2);
        return $this->crop($startX, $startY, $width, $height);
    }

    /**
     * Flips image
     *
     * @return Image
     */
    public function flip()
    {
        $xLength = imagesx($this->image);
        $yLength = imagesy($this->image);

        $image = $this->image;
        $this->image = $this->createRawImage($this->currentWidth, $this->currentHeight);
        for ($x = 0; $x < $xLength; $x++) {
            for ($y = 0; $y < $yLength; $y++) {
                imagecopy($this->image, $image, $xLength - $x - 1, $y, $x, $y, 1, 1);
            }
        }

        return $this;
    }

    /**
     * Saves image as $name (can include file path), with quality of # percent if file is a jpeg
     */
    public function save($compression = 100, $file = null)
    {

        switch ($this->type) {
            case self::IMAGE_GIF: {
                if ($file) {
                    imagegif($this->image, $file);
                } else {
                    imagegif($this->image, $this->file);
                }
                break;
            }
            case self::IMAGE_JPG: {

                if ($file) {
                    imagejpeg($this->image, $file, $compression);
                } else {
                    imagejpeg($this->image, $this->file, $compression);
                }
                break;
            }
            case self::IMAGE_PNG: {
                $compression = round($compression / 10);
                $compression >= 10 ? $compression = 9 : null;
                $compression = 9 - $compression;
                if ($file) {
                    imagepng($this->image, $file, $compression);
                } else {
                    imagepng($this->image, $this->file, $compression);
                }

                break;
            }
        }

    }

    /**
     * Displays current image
     *
     * @param int $compression
     */
    public function display($compression = 100)
    {
        switch ($this->type) {
            case self::IMAGE_GIF: {
                header('Content-type: image/gif');
                imagegif($this->image);
                break;
            }
            case self::IMAGE_JPG: {
                header('Content-type: image/jpeg');
                imagejpeg($this->image, null, $compression);
                break;
            }
            case self::IMAGE_PNG: {
                header('Content-type: image/png');
                $compression = round($compression / 10);
                $compression >= 10 ? $compression = 9 : null;
                $compression = 9 - $compression;
                imagepng($this->image, null, $compression);
                break;
            }
        }
    }


    /**
     * Calculates new image size based on width and height, while constraining to maxWidth and maxHeight
     *
     */
    private function calculateNewImageSize($maxWidth, $maxHeight)
    {
        $newSize = array('width' => $this->currentWidth, 'height' => $this->currentHeight);

        if ($maxWidth && $maxWidth > 0) {
            $newSize = $this->calculateNewImageSizeByWidth($maxWidth);

            if ($maxHeight && $maxHeight > 0 && $newSize['height'] > $maxHeight) {
                $newSize = $this->calculateNewImageSizeByHeight($maxHeight);
            }

            //$this->newDimensions = $newSize;
        }

        if ($maxHeight && $maxHeight > 0) {
            $newSize = $this->calculateNewImageSizeByHeight($maxHeight);

            if ($maxWidth && $maxWidth > 0 && $newSize['width'] > $maxWidth) {
                $newSize = $this->calculateNewImageSizeByWidth($maxWidth);
            }
        }

        return $newSize;
    }

    /**
     * @return array
     */
    private function calculateNewImageSizeByWidth($width)
    {
        $prop = $width / $this->currentWidth;
        $newHeight = $this->currentHeight * $prop;
        return array('width' => $width, 'height' => ceil($newHeight));
    }

    /**
     * @return array
     */
    private function calculateNewImageSizeByHeight($height)
    {
        $prop = $height / $this->currentHeight;
        $newWidth = $this->currentWidth * $prop;
        return array('width' => ceil($newWidth), 'height' => $height);
    }


    private function preserveTransparency(&$img)
    {
        //preserve image transparency
        imagealphablending($img, false);
        imagesavealpha($img, true);

    }


    private function createRawImage($width, $height)
    {
        if (function_exists('imagecreatetruecolor')) {
            $img = imagecreatetruecolor($width, $height);
        } else {
            $img = imagecreate($width, $height);
        }
        if ($this->type != self::IMAGE_PNG && $this->type != self::IMAGE_GIF) {
            return $img;
        }
        if (!$this->transparency) {
            return $img;
        }

        //preserve image transparency
        $this->preserveTransparency($img);
        $transparent = imagecolorallocatealpha($img, 255, 255, 255, 127);
        imagefilledrectangle($img, 0, 0, $width, $height, $transparent);
        return $img;
    }


    protected $file;
    protected $type;
    protected $image;
    protected $mimeType;
    private $transparency = true;

    const IMAGE_PNG = 1;
    const IMAGE_JPG = 2;
    const IMAGE_GIF = 3;
    //@todo:implement bmp
    const IMAGE_BMP = 4;

    const RESIZE_MINIMAL = 1;
    const RESIZE_MAXIMAL = 2;
}