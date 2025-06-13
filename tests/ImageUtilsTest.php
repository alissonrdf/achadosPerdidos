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

    public function testGenerateSafeImageName()
    {
        $name = generateSafeImageName('My Document #1');
        $this->assertStringEndsWith('.webp', $name);
        $this->assertStringNotContainsString(' ', $name);
    }
}
