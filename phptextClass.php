<?php
class phptextClass
{
    public function phpcaptcha($textColor, $backgroundColor, $imgWidth, $imgHeight, $noiceLines = 0, $noiceDots = 0, $noiceColor = '#162453')
    {
        $text = $this->random();
        $_SESSION['captcha_code'] = $text;

        $im = imagecreatetruecolor($imgWidth, $imgHeight);
        
        $bgRGB = $this->hexToRGB($backgroundColor);
        $bgColor = imagecolorallocate($im, $bgRGB['r'], $bgRGB['g'], $bgRGB['b']);
        imagefill($im, 0, 0, $bgColor);

        $tcRGB = $this->hexToRGB($textColor);
        $tColor = imagecolorallocate($im, $tcRGB['r'], $tcRGB['g'], $tcRGB['b']);

        // Draw noise dots
        for ($i = 0; $i < $noiceDots; $i++) {
            imagefilledellipse($im, mt_rand(0, $imgWidth), mt_rand(0, $imgHeight), 2, 2, $tColor);
        }

        // Drop shadow for text (subtle)
        $shadowColor = imagecolorallocate($im, 200, 200, 200);
        
        // Use built-in font as fallback is safer
        $fontSize = 5;
        $fontWidth = imagefontwidth($fontSize);
        $fontHeight = imagefontheight($fontSize);
        $textWidth = strlen($text) * $fontWidth;
        $x = ($imgWidth - $textWidth) / 2;
        $y = ($imgHeight - $fontHeight) / 2;

        imagestring($im, $fontSize, $x + 1, $y + 1, $text, $shadowColor);
        imagestring($im, $fontSize, $x, $y, $text, $tColor);

        if (ob_get_level()) ob_clean();
        header('Content-Type: image/png');
        header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        header('Pragma: no-cache');
        imagepng($im);
        imagedestroy($im);
    }

    protected function random($characters = 6, $letters = '23456789bcdfghjkmnpqrstvwxyz')
    {
        $str = '';
        for ($i = 0; $i < $characters; $i++) {
            $str .= substr($letters, mt_rand(0, strlen($letters) - 1), 1);
        }
        return $str;
    }

    protected function hexToRGB($colour)
    {
        if ($colour[0] == '#') {
            $colour = substr($colour, 1);
        }
        if (strlen($colour) == 6) {
            list($r, $g, $b) = array($colour[0] . $colour[1], $colour[2] . $colour[3], $colour[4] . $colour[5]);
        } elseif (strlen($colour) == 3) {
            list($r, $g, $b) = array($colour[0] . $colour[0], $colour[1] . $colour[1], $colour[2] . $colour[2]);
        } else {
            return array('r' => 0, 'g' => 0, 'b' => 0);
        }
        return array('r' => hexdec($r), 'g' => hexdec($g), 'b' => hexdec($b));
    }
}