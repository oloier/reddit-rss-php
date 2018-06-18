<?php header("Content-type: application/rss+xml"); 

require_once("./class.embed.php");

# default to 7, unless told otherwise
    $itemNum = (!isset($_GET['num']))  ? '7'   : $_GET['num'];
    $time    = (!isset($_GET['time'])) ? 'day' : $_GET['time'];

# require a subreddit

    if (isset($_GET['r'])) {
        try {
        $subreddit = $_GET['r'];
        $jsonFile = getUrlData('https://www.reddit.com/r/'. $subreddit .'/top/.json?sort=top&t='. $time .'&limit='. $itemNum .'');
        } catch(exception $ex){}
    } else {
        $subreddit = 'gifs';
        $jsonFile = getUrlData('https://www.reddit.com/r/'. $subreddit .'/top/.json?sort=top&t='. $time .'&limit='. $itemNum .'');
        // die("missing subreddit name");
    }

# throw down the rss headers
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?> 
    <rss version=\"2.0\" 
        xmlns:dc=\"http://purl.org/dc/elements/1.1/\"
        xmlns:media=\"http://search.yahoo.com/mrss/\"
        xmlns:atom=\"http://www.w3.org/2005/Atom\"
        xmlns:content=\"http://purl.org/rss/1.0/modules/content/\"
        xmlns:sy=\"http://purl.org/rss/1.0/modules/syndication/\"
        xmlns:slash=\"http://purl.org/rss/1.0/modules/slash/\"
    > 
    <channel> 
        <title>/r/". $subreddit ."</title> 
        <link></link>
        <description>custom feed for /r/". $subreddit ."</description> 
        <language>en-us</language>
    \n";

$obj = json_decode($jsonFile);
$forceLimit = 1; # I REALLY want to enforce this

if (is_object($obj) && $forceLimit <= $itemNum) {
    foreach ($obj->data->children as $item) {
        if ($forceLimit <= $itemNum) {
            $data = $item->data;
            $commentsUrl = 'https://www.reddit.com'. $data->permalink;
            $mobileUrl = 'https://m.reddit.com'. $data->permalink;
            $title = $data->title;
            $articleUrl = $data->url;
            $selfText = $data->selftext_html;
            $domain = $data->domain;
            $author = $data->author;
            $pubDate = date(DATE_RSS, $data->created);
            # replace http with https for reddit urls, no longer needed?
            /*if (strpos($articleUrl, 'reddit.com') !== false && 
                strpos($articleUrl, 'https') == false) {
                $articleUrl = str_replace('http', 'https', $articleUrl);
            }*/
            
            echo "        <item>\n";
            echo "            <title>$title</title>\n";
            echo "            <link>$articleUrl</link>\n";
            echo "            <pubDate>$pubDate</pubDate>\n";

            $content = "<div><a href=\"$commentsUrl\">View Comments</a> &nbsp; &nbsp; &nbsp;<a href=\"$mobileUrl\">Mobile Comments</a></div>"; 
                // <a href=\"$alienUrl\">Open in AlienBlue</a>

            $content.= "<p><small><b>$domain</b> &ndash; posted by <em>$author</em></small></p>";

            if (strpos($domain, "self.") === false) {
                # embed if we have binary content
                $content.= getVideoEmbed($articleUrl);
                $content.= getImageEmbed($articleUrl);       
            } else {
                $content.= $selfText;
            }

            // $content = htmlspecialchars($content);
            echo "            ";
            echo "<content:encoded><![CDATA[$content]]></content:encoded>\n";  
            echo "        </item>\n";
            # iterate so we can enforce the limit htat reddit is failing with
            $forceLimit++;
        }
    }
}

echo "</channel></rss>\n"; 

// function pr($data){
//     echo "<pre>"; print_r($data); // or var_dump($data);
//     echo "</pre>";
// }
// pr($obj);

?>