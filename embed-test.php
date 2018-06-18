<form method="post" action=""><input name="msg"/><input type="submit"/></form>
<br/><br/>

<?php

define('IMGUR_CLIENT_ID', '');
require_once('class.embed.php');

// $url = 'http://imgur.com/y2L9SmA'; # image
// $url = 'http://imgur.com/ibtrRFH'; # video / gifv
// $url = 'http://imgur.com/dhHwzdx';
// $url = 'http://imgur.com/a/Dii3H'; # gallery
// $url = 'http://imgur.com/a/gt97C'; # broken gallery...

# image embedding class for converting image links from common hosts to 
# rendered images or galleries. So slick, oh baby; Taken from W3Lessons.info
# http://w3lessons.info/2013/11/25/getting-images-from-flickr-instagram-twitpic-imgur-deviantart-url-using-php-jquery/

if(isset($_POST['msg']) && $_POST['msg'] != '') {
    $stuff = $_POST['msg'];
    echo getImageEmbed($stuff);
    echo getVideoEmbed($stuff);
}
