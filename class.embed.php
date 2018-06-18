<?php

define('IMGUR_CLIENT_ID', '');
define('IMGUR_CLIENT_SECRET', '');

# primary method to utilize image hosts' api and embed image via html
function getImageEmbed($link)
{
    // $link = fixUrl($link);
    $imgSites = array(
        "directURL" => '/https?:\/\/\S+(?:png|jpg|gif|svg)\b/',
        "redditUp"  =>  '/https?:\/\/([a-z0-9]+[.])*reddituploads\.[^\/]*\/[^`]*/is',
        "imgurAlb"  => '/https?:\/\/([a-z0-9]+[.])*imgur\.[^\/]*\/(a)\/([^?]*)/is',
        "imgurGal"  => '/https?:\/\/([a-z0-9]+[.])*imgur\.[^\/]*\/(gallery)\/([^?]*)/is',
        "imgur"     => '/https?:\/\/([a-z0-9]+[.])*imgur\.[^\/]*\/([^?]*)/is', 
        "flickr"    => '/https?:\/\/[w\.]*flickr\.com\/photos\/([^?]*)/is', 
        "twitpic"   => '/https?:\/\/[w\.]*twitpic\.com\/([^?]*)/is', 
        "devart"    => '/https?:\/\/[^\/]*\.*deviantart\.[^\/]*\/([^?]*)/is', 
        "instagram" => '/https?:\/\/[w\.]*instagram\.[^\/]*\/([^?]*)/is',
        "gfycat"    => '/https?:\/\/[w\.]*gfycat\.[^\/]*\/([^?]*)/is'
    );

    foreach ($imgSites as $site => $regexp) {
        preg_match($regexp, $link, $match);

        if (!empty($match)) {

            switch ($site) {

                case "directURL":
                    $img = "<img src=\"$link\" style=\"max-width:100%;\" alt=\"\" />";
                break;
                
                case "redditUp":
                    $img = "<img src=\"$link\" style=\"max-width:100%;\" alt=\"\" />";
                break;

                case "imgurAlb":
                    $alid = basename($link);
                    $jsonImgur = "https://api.imgur.com/3/album/$alid";
                    $img = renderImgurJson($jsonImgur);
                break;

                case "imgurGal":
                    $galid = basename($link);
                    $jsonImgur = "https://api.imgur.com/3/gallery/$galid";
                    $img = renderImgurJson($jsonImgur);
                break;

                case "imgur":
                    $imgid = basename($link);
                    if (strpos($imgid, '.') !== false) {
                        $imgPath = explode('.', $imgid);
                        $imgid = $imgPath[0];
                    }
                    $jsonImgur = "https://api.imgur.com/3/image/$imgid";
                    $img = renderImgurJson($jsonImgur);
                break;
                
                case "flickr":
                    $jsonFlickr = "http://www.flickr.com/services/oembed/?format=json&url=".$link;
                    $img = getJsonResponse($jsonFlickr);
                break;

                case "instagram":
                    $jsonInstagram = "http://api.instagram.com/oembed?format=json&url=".$link;
                    $img = getJsonResponse($jsonInstagram);
                break;

                case "devart":
                    $jsonDeviant = "http://backend.deviantart.com/oembed?format=json&url=".$link;
                    $img = getJsonResponse($jsonDeviant);
                break;

                case "twitpic":
                    $code = $match[1];
                    $img = "<img src='http://twitpic.com/show/large/".$code.".jpg'>";
                break;

                case "gfycat":
                    $imgid = basename($link);
                    $jsonGfycat = "http://gfycat.com/cajax/get/".$imgid;
                    $img = renderGfycatJson($jsonGfycat);
                break;

                default:
                    $img = '';
                break;
            }

            return $img;
        }
    }
}

function fixUrl($url) 
{
    if (substr($url, 0, 7) == 'http://' || 
        substr($url, 0, 8) == 'https://') {
        return $url;
    } else return 'http://'. $url;
}

# curl function to get json response
function getUrlData($url)
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $url,
        CURLOPT_HTTPHEADER => array('Authorization: Client-ID '.IMGUR_CLIENT_ID),
        CURLOPT_USERAGENT => "Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2.16) Gecko/20110319 Firefox/3.6.16"
    ));
    $curlData = curl_exec($curl);
    curl_close($curl);
    return $curlData;
}

function getImgurUrl($apiurl) 
{
    $formatted = str_replace('api.', '', $apiurl);
    $formatted = str_replace('album', 'a', $formatted);
    $formatted = str_replace('/3', '', $formatted);
    return $formatted;
}

function getJsonResponse($url)
{
    $jsonResponse = getUrlData($url);
    $res = json_decode($jsonResponse, true);
    if(is_array($res) && !empty($res)) {
        return $res["html"];    
    }
}

function renderImgurJson($url)
{
    $jsonResponse = getUrlData($url);
    $res = json_decode($jsonResponse, true);

    # setup html output templating
    $htmlOut = function($img, $title, $desc, $count = 1, $nsfw = false, $mp4 = '') {
        if (!empty($title)) $html .= "<h4>$title</h4>";
        if (!empty($mp4)) {
            $mp4 = str_replace('mp4', 'mp4', $mp4);
            $html .= '<video autoplay="true" controls="controls" width="800" height="600" loop="true"><source src="'.$mp4.'" type="video/mp4"></video>';
            //  <source src="'.$mp4.'" type="video/mp4">
        } elseif (!empty($img)) {
            $html .= "<a href=\"$img\" target=\"_blank\">";
            if (!$nsfw) {
                $html .= "<img src=\"$img\" alt=\"\" style=\"max-width:100%;\"/>";
            } else {
                $html .= "<div><big><b>NSFW &ndash; </b>$img</big></div>";
            }
        }
        $html .= "</a>";

        if (!empty($desc)) $html .= "<blockquote>$desc</blockquote>";
        return $html;
    };

    if(is_array($res) && !empty($res)) {

        # single images
        $data = $res['data'];
        $count = $data['images_count'];

        # for galleries
        if (!empty($data['images'])) {
            $images = $data['images'];    
        }

        # for video
        if (!empty($data['mp4'])) {
            $mp4 = $data['mp4'];    
        }

        # if we have a gallery, display each image and desc.
        if (is_array($images) && !empty($images)) {
            $i = 0;
            foreach ($images as $key => $value) {
                $img  = $value['link'];
                $title = $value['title'];
                $desc = $value['description'];

                # if we're exceeding 15 images, kill loop and show more link
                if (++$i == 15) {
                    $remain = ($count - $i) + 1; 
                    $truDat = getImgurUrl($url); # edit api url to normal
                    # append a more link with a count

                    $id  = $value['id'];
                    $htmlStuff .= "<a href=\"$truDat#$id\"><h4>$remain more images...</h4></a>";
                    break;
                }

                if ($value['nsfw'] == '1' || strpos($desc, 'nsfw') !== false) {
                    $nsfw = true;
                }

                $htmlStuff .= $htmlOut($img, $title, $desc, $count, $nsfw);
            }
        # if it's an individual file, just display that (and desc.)
        } elseif (is_array($data) && !empty($data)) {
            $img  = $data['link'];
            $title = $value['title'];
            $desc = $data['description'];
            $nsfw = $data['nsfw'];
            $htmlStuff = $htmlOut($img, $title, $desc, $count, $nsfw, $mp4);
        }

    }
    // print_r($res);
    return $htmlStuff;
}

function renderGfycatJson($url)
{
    $jsonResponse = getUrlData($url);
    $res = json_decode($jsonResponse, true);
    if (is_array($res) && !empty($res)) {
        $data = $res["gfyItem"];    
    }

    if(is_array($data) && !empty($data)) {
        $mp4 = $data['mp4Url'];
        $mp4 = $data['mp4Url'];
        // $img = 'http://lorempixel.com/800/600/abstract/VIDEO-VIDEO-VIDEO/';
        $htmlStuff = '<video autoplay="true" controls="controls" loop="true" width="800" height="600"><source src="'.$mp4.'" type="video/mp4"><source src="'.$mp4.'" type="video/mp4"></video>';
        
    }
    // print_r($res);
    return $htmlStuff;
}

# oembed is just the bee's knees
function getVideoEmbed($url) 
{
    $oembed = '';
    $content = '';
    if (strpos($url, 'youtu.be') !== false || 
        strpos($url, 'youtube.com') !== false) {
        $badYt = '?feature=youtu.be&v=';
        # youtube oembed hates this URL scheme, so kill it
        $url = (strpos($url, $badYt) == false) ? $url : str_replace($badYt, '?v=', $url);
        $oembed = 'https://www.youtube.com/oembed?url=';
    } 
    elseif (strpos($url, 'vimeo.com') !== false) {
        $oembed = 'https://vimeo.com/api/oembed.json?url=';
    } 
    elseif (strpos($url, 'dailymotion.com') !== false) {
        $oembed = 'https://www.dailymotion.com/services/oembed?format=json&url=';
    } 
    elseif (strpos($url, 'streamable.com') !== false) {
        $oembed = 'https://api.streamable.com/oembed.json?url=';
    } 
    try {
        # file_get_contents no good with SSL
        // $jsonFile = file_get_contents("$oembed$url");
        $ch = curl_init("$oembed$url");
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux i686; rv:20.0) Gecko/20121230 Firefox/20.0');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $jsonFile = curl_exec($ch);
        $obj = json_decode($jsonFile);
        if (isset($obj->html)) $content = $obj->html;
    } catch(exception $ex){
        // one day I'll do stuff here
        echo htmlspecialchars("$oembed$url");
        echo $ex;
    }

    # load into DOM object and change default iframe dimensions
    $dom = new DOMDocument;
    if (@$dom->loadHTML($content)) {

        $xpath = new DOMXPath($dom);

        # get any elements with a width attribute
        $widthelements = $xpath->query('//*[@width]');
        foreach ($widthelements as $el) {
            # theoldreader.com strips any inline style attributes...
            // $el->setAttribute('style', 'width:100%; height:40vw');
            $el->setAttribute('width', '640');
        }

        # get any elements with a height attribute
        $heightelements = $xpath->query('//*[@height]');
        foreach ($heightelements as $el) {
            $el->setAttribute('height', '360');
        }

        $content = $dom->saveHTML();
    }
    return $content;
}

?>