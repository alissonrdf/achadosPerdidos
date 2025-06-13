<?php
use PHPUnit\Framework\TestCase;

class ImageUtilsTest extends TestCase
{
    public function testProcessImageCreatesWebp()
    {
        // Create temporary JPEG image
        $tmpJpeg = tempnam(sys_get_temp_dir(), 'img_') . '.jpg';
        $image = imagecreatetruecolor(1000, 500);
        imagefilledrectangle($image, 0, 0, 999, 499, imagecolorallocate($image, 255, 0, 0));
        imagejpeg($image, $tmpJpeg);
        imagedestroy($image);

        $file = [
            'tmp_name' => $tmpJpeg,
            'name' => 'test.jpg',
            'type' => 'image/jpeg'
        ];

        $target = tempnam(sys_get_temp_dir(), 'target_') . '.jpg';
        $webpPath = processImage($file, $target, 800, 800, 80);

        $this->assertFileExists($webpPath);
        $this->assertStringEndsWith('.webp', $webpPath);

        $info = getimagesize($webpPath);
        $this->assertSame('image/webp', $info['mime']);
        $this->assertLessThanOrEqual(800, $info[0]);
        $this->assertLessThanOrEqual(800, $info[1]);

        unlink($tmpJpeg);
        unlink($webpPath);
    }

    public function testProcessImagePreservesPngTransparency()
    {
        // Create temporary PNG image with transparency
        $tmpPng = tempnam(sys_get_temp_dir(), 'img_') . '.png';
        $image = imagecreatetruecolor(100, 100);
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $transparent);
        imagepng($image, $tmpPng);
        imagedestroy($image);

        $file = [
            'tmp_name' => $tmpPng,
            'name' => 'test.png',
            'type' => 'image/png'
        ];

        $target = tempnam(sys_get_temp_dir(), 'target_') . '.png';
        $webpPath = processImage($file, $target, 100, 100, 80);

        $this->assertFileExists($webpPath);

        $webpImage = imagecreatefromwebp($webpPath);
        $color = imagecolorat($webpImage, 0, 0);
        $alpha = ($color & 0x7F000000) >> 24;
        imagedestroy($webpImage);

        unlink($tmpPng);
        unlink($webpPath);

        $this->assertSame(127, $alpha);
    }

    public function testGenerateSafeImageName()
    {
        $name = generateSafeImageName('My Document #1');
        $this->assertStringEndsWith('.webp', $name);
        $this->assertStringNotContainsString(' ', $name);
    }
}
