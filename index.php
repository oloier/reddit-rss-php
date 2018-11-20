<?php header("Content-type: application/rss+xml; charset=utf-8"); 

# default to 5, unless told otherwise
	$itemNum = (!isset($_GET['num']))  ? '5'   : $_GET['num'];
	$time    = (!isset($_GET['time'])) ? 'day' : $_GET['time'];

# curl function to get json response
	function getUrlData($url) {
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => $url,
			// CURLOPT_HTTPHEADER => array('Authorization: Client-ID '.IMGUR_CLIENT_ID),
			CURLOPT_USERAGENT => "Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2.16) Gecko/20110319 Firefox/3.6.16"
		));
		$curlData = curl_exec($curl);
		curl_close($curl);
		return $curlData;
	}

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
	<title>/r/$subreddit</title> 
	<link>https://www.reddit.com/r/$subreddit</link>
	<description>PHP custom feed for /r/". $subreddit ."</description> 
	<language>en-us</language>\n";

# small self-contained iframe video embedding
	function videoTemplate($url, $width = 640, $height = 420) {
		if ($height > 900) $height = 900;
		return sprintf('<iframe width="%s" height="%s" frameborder="0" src="https://oloier.com/php-rss/v.php?v=%s"></iframe>', 
			$width, $height, urlencode($url));
	}

$obj = json_decode($jsonFile);

if (is_object($obj) <= $itemNum) {
	foreach ($obj->data->children as $item) {
		$data = $item->data;
		$commentsUrl = 'https://www.reddit.com'. $data->permalink;
		$title = $data->title ?? '';
		$articleUrl = $data->url ?? '';
		$author = $data->author ?? '';
		$pubDate = date(DATE_RSS, $data->created);
		$selfText = $data->selftext_html ?? '';
		$secureEmbed = $data->secure_media_embed ?? '';
		$numComments = $data->num_comments ?? '';
		$postHint = $data->post_hint ?? 'self';
		$thumbUrl = $data->thumbnail ?? '';
		$thumbWidth = $data->thumbnail_width ?? '';
		$thumbHeight = $data->thumbnail_height ?? '';
		$domain = $data->domain ?? '';

		$annote = "<div><a href=\"$commentsUrl\">View $numComments Comments</a><small><b>$domain</b> &ndash; posted by <em>$author</em></small></div><p></p>";

		if (in_array($postHint, ['self', 'image', 'link']) || $thumbUrl == 'default')
			$thumbUrl = null;
			# rich:video is anything with oembed
			if ($postHint == 'rich:video') {
			$content = html_entity_decode($secureEmbed->content);
		}
		
		# hosted:video is any reddit-hosted video
		if ($postHint == 'hosted:video') {
			$rv = $data->secure_media->reddit_video;
			$content = videoTemplate($rv->fallback_url, $rv->width, $rv->height);
		}

		# reddit.self stuff; encoded HTML. Strip out 
		if ($postHint == 'self') {
			$content = html_entity_decode($selfText);
		}
		
		# direct image embedding
		if (($postHint && strpos($postHint, 'image') !== -1) || preg_match('/jpg|jpeg|png|gif|webp|/i', $articleUrl)) {
			$content .= sprintf('<img src="%s" alt=""/>', $articleUrl);
		}
		

		echo "\t\t<item>\n";
		echo "\t\t\t<title>$title</title>\n";
		echo "\t\t\t<link>$articleUrl</link>\n";
		echo "\t\t\t<pubDate>$pubDate</pubDate>\n";
		echo "\t\t\t";
		echo "<content:encoded><![CDATA[\n";
		echo "\t\t\t";
		echo $annote . "\n";
		echo "\t\t\t";
		echo $content ."\n";
		if ($thumbUrl) echo "<p>Thumbnail attachment:</p><img src=\"$thumbUrl\" width=$thumbWidth height=$thumbHeight/>\n";
		echo "\t\t\t";
		echo "]]></content:encoded>\n";  
		echo "\t\t";
		echo "</item>\n";
	}
}


echo "</channel></rss>\n"; 

?>
