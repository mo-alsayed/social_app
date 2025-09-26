<?php
// Create directories
$directories = [
    'assets/images/profiles',
    'assets/images/covers',
    'assets/uploads'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Create default profile image
$profile = imagecreate(200, 200);
$blue = imagecolorallocate($profile, 24, 119, 242);
$white = imagecolorallocate($profile, 255, 255, 255);
imagefill($profile, 0, 0, $blue);
imagefilledellipse($profile, 100, 100, 180, 180, $white);
// Add user icon
imagettftext($profile, 40, 0, 70, 120, $blue, realpath('assets/fonts/arial.ttf') ?: null, '👤');
imagejpeg($profile, 'assets/images/profiles/default_profile.png', 90);
imagedestroy($profile);

// Create default cover image
$cover = imagecreate(800, 300);
$dark_blue = imagecolorallocate($cover, 20, 100, 200);
$light_blue = imagecolorallocate($cover, 100, 160, 240);
// Gradient effect
for ($y = 0; $y < 300; $y++) {
    $r = 20 + ($y / 300) * 80;
    $g = 100 + ($y / 300) * 60;
    $b = 200 + ($y / 300) * 40;
    $color = imagecolorallocate($cover, $r, $g, $b);
    imageline($cover, 0, $y, 800, $y, $color);
}
// Add text
$text_color = imagecolorallocate($cover, 255, 255, 255);
imagettftext($cover, 20, 0, 320, 160, $text_color, realpath('assets/fonts/arial.ttf') ?: null, 'SocialFeed');
imagejpeg($cover, 'assets/images/covers/default_cover.jpg', 90);
imagedestroy($cover);

echo "Default images created successfully!";
?>