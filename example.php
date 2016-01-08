<?php
require('instagram_post.php');

$upload_image_filename = 'test.jpg'; // TODO; Link to your image from here
$image_caption = 'My example image caption #InstagramImageAPI'; // TODO; Add your image caption here

$ig = new instagram_post();
if ($ig->doPostImage($upload_image_filename, $image_caption)) {
  echo 'Success';
} else {
  echo 'Failed to upload';
}
