<?php
/**
 * MergeImagesHelper: class providing functions for merging 2 images (a frame and a photo)
 * These functions are to put a frame photo (with transparent parts) on an other photo (named "background photo") and merge them.
 * Return: a new photo created by merging a background photo and a frame photo (cropped by the bound of the frame photo) 
 * Application example: merge your own Facebook profile photo with a frame of any magazine to make your own magazine style cover page
 * 
 * @author ngoducthuan85
 * @final 2015/06/04
 */

class MergeImagesHelper
{
    public $background; 	// Background photo (e.g., your profile photo)
    public $frame; 			// Frame photo (e.g., any frame of a magazine) 
    public $backgroundW;	// Width of the background photo (in pixels)
    public $backgroundH;	// Height of the background photo (in pixels)
    public $frameW;			// Width of the frame photo (in pixels)
    public $frameH;			// Height of the frame photo (in pixels)

    /**
     * Constructor
     * @param $backgroundImagePath
     * @param $frameImagePath
     */
    public function __construct($backgroundImagePath, $frameImagePath, $backgroundTopH, $backgroundDisplayH) {
        $this->background   = $this->getImageFromUrl($backgroundImagePath); // Create the background photo from URL
        /*
         * NOTE:
         * For many magazines, the top of the frame usually are the name of the magazine.
         * In that case, the face in your profile photo usually is covered by the upper part of the frame.
         * In order to avoid that, you can add some blank space in the profile photo (background photo) beforehand
         * by using following function
         */
        //$this->background   = $this->getImageWithTopBlank($backgroundImagePath, $backgroundTopH, $backgroundDisplayH);
        
        $this->frame        = $this->getImageFromUrl($frameImagePath); 	// Create the frame photo from URL 
        $this->backgroundW  = $this->getWidth($this->background);		// Get the width of the background photo
        $this->backgroundH  = $this->getHeight($this->background);		// Get the height of the background photo
        $this->frameW       = $this->getWidth($this->frame);			// Get the width of the frame photo
        $this->frameH       = $this->getHeight($this->frame);			// Get the height of the frame photo
    }

    public function __destruct() {
        imagedestroy($this->background);
        imagedestroy($this->frame);
    }

    /**
     * Create image from path (supported image types: jpg, jpeg, bmp, gif, png)
     */
    public function createImageFromPath($imagePath)
    {
        $type = strtolower(substr(strrchr($imagePath,"."),1));
        if($type == 'jpeg') $type = 'jpg';
        switch($type){
            case 'bmp': $image = imagecreatefromwbmp($imagePath); break;
            case 'gif': $image = imagecreatefromgif($imagePath); break;
            case 'jpg': $image = imagecreatefromjpeg($imagePath); break;
            case 'png': $image = imagecreatefrompng($imagePath); break;
            default : return false; // Do not support other types
        }
        return $image;
    }

    /**
     * For many magazines, the top of the frame usually are the name of the magazine.
     * In that case, the face in your profile photo usually is covered by the upper part of the frame.
     * In order to avoid that, you can add some blank space in the profile photo (background photo) beforehand
     * by using following function
     */
    public function getImageWithTopBlank($backgroundImagePath, $backgroundTopH, $backgroundDisplayH)
    {
        $background     = $this->getImageFromUrl($backgroundImagePath);
        $orgW           = $this->getWidth($background);
        $orgH           = $this->getHeight($background);
        $actualTopH     = $orgH / $backgroundDisplayH * $backgroundTopH;

        $newBackground  = imagecreatetruecolor($orgW, $orgH + $actualTopH);
        $color          = imagecolorallocate($newBackground, 192, 192, 192);
        imagefilltoborder($newBackground, 0, 0, $color, $color);
        imagecopy($newBackground, $background, 0, $actualTopH, 0, 0, $orgW, $orgH);
        return $newBackground;
    }

    public function getImageFromUrl($link)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch,CURLOPT_URL,$link);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $contents = curl_exec($ch);
        curl_close($ch);
        $result = imagecreatefromstring($contents);
        return $result;
    }

    public function getContentsStringFromUrl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $contents = curl_exec($ch);
        curl_close($ch);
        return $contents;
    }

    public function getWidth($image)
    {
        return imagesx($image);
    }

    public function getHeight($image)
    {
        return imagesy($image);
    }

    /**
     * Crop the background to be fit with the frame
     */
    public function cropDisplayedImage($backgroundDisplayW, $backgroundDisplayH,
                                $frameDisplayX, $frameDisplayY, $frameDisplayW, $frameDisplayH)
    {
        $zoomRatio      = $this->backgroundW / $backgroundDisplayW;
        $srcX           = $zoomRatio * $frameDisplayX;
        $srcY           = $zoomRatio * $frameDisplayY;
        $srcW           = $zoomRatio * $frameDisplayW;
        $srcH           = $zoomRatio * $frameDisplayH;

        // Scale the croped image to be in the same size of the frame
        $photoFrame    = imagecreatetruecolor($this->frameW,$this->frameH);
        imagecopyresampled($photoFrame, $this->background, 0, 0, $srcX, $srcY, $this->frameW,$this->frameH, $srcW, $srcH);
        return $photoFrame;
    }

    /**
     * Merge Background Image and Frame Image
     */
    public function mergeImages($backgroundDisplayW, $backgroundDisplayH,
                                $frameDisplayX, $frameDisplayY, $frameDisplayW, $frameDisplayH)
    {
        // Crop a background to merge with the frame
        $mergedImg = $this->cropDisplayedImage($backgroundDisplayW, $backgroundDisplayH,
                                        $frameDisplayX, $frameDisplayY, $frameDisplayW, $frameDisplayH);
        imagecopy($mergedImg, $this->frame, 0, 0, 0, 0, $this->frameW,$this->frameH);
        return $mergedImg;
    }

    /**
     * Scale to Fit Background and Frame
     */
    public function scaleToFit()
    {
        // Scale backgroun
        $scaledImg = imagecreatetruecolor($this->frameW,$this->frameH);
        imagecopyresampled($scaledImg, $this->background, 0, 0, 0, 0, $this->frameW,$this->frameH, $this->backgroundW, $this->backgroundH);

        imagecopy($scaledImg, $this->frame, 0, 0, 0, 0, $this->frameW,$this->frameH);
        return $scaledImg;
    }
}
?>