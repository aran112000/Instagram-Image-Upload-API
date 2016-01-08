# Instagram Image Upload API (PHP)
A simple PHP class allowing images to be uploaded to Instagram. This is based upon the following: http://lancenewman.me/posting-a-photo-to-instagram-without-a-phone/

## Example usage
```PHP
<?php
// TODO; Your Instagram Username and Password will need to be added at the top of this file:
require('instagram_post.php');

$upload_image_filename = 'test.jpg'; // TODO; Link to your image from here
$image_caption = 'My example image caption #InstagramImageAPI'; // TODO; Add your image caption here

$ig = new instagram_post();
if ($ig->doPostImage($upload_image_filename, $image_caption)) {
  echo 'Success, image uploaded to your Instagram account';
} else {
  echo 'Failed to upload';
}
```
