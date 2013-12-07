<?php
/*
 *
 * === JPlaylister ===
 * A JPlayer Playlist Generator Based on JPlayer (2.2)
 *
 * JPlaylister is a playlist generator for JPlayer (http://www.happyworm.com/jquery/jplayer/) by Nick Chapman of Chapman IT
 *  and Cornbread Web Development.  Base page from JPlayer Demo page (with playlist and downloadable content),
 *  additions primarily in php, with a some in HTML/JS changes
 *
 * This page demonstrates the use of the JPlaylister (formerly JPlayer Playlister or JPlayer Audio Playlist Generator) 
 *  which looks in a specified folder/directory, and tries to create a JPlayer playlist based on what it finds
 *
 *	A few capabilities have been added to the standard JPlayer Implementation:
 *
 *		1- All files in the specified target directory, and any subfolders, will be added to a jplayer playlist
 *			> The process can be refined by disallowing certain files or folders or specifing allowed filetypes
 *			> Multiple file formats for the same (file-titled) song will be collapsed into one jPlayer Playlist Line Item.
 *				This allows jPlayer to determine which file to use based on the browser the user is surfing -- HTML5 compatibility is preferred
 *		2- Media (song) information is parsed from ID3[/other media] tags via the getID3 php class, or less elegantly from the filename
 *		3- Basic and Advanced Filtering (single or multiple subfolder filters)
 *		4- Playlist sorting options are built in: 
 *			mtime (file modified time -- default), file name, ID3 artist name, ID3 track title, ID3 track number, random.
 *			Sorting preferences based on configurable setting or user request (URL argument)
 *		5- Overrides to many default configuration options are allowed bia URL argument:
 *			autoplay, sorting, basic filtering, 'root' folder
 *		6- Album art can be displayed as a poster image from embedded art or by referencing a standard naming convention
 *		7- Caching has been added to speed the load of lengthy playlists.  This is a new feature which may require some tweaking.
 *			Some url arguments which force global and/or current playlist cache refreshes can be used.
 *
 */
/*
* === Changelog ===
*
* v0.3 > v0.4
* _________________
*
*	1- added filter by folder options 
*		-listed folders with checkboxes for self calling filter
*		-modified main function to add filtered folders to 'disallowed' when filter applied
*
*	2- updated to work with jplayer 2.0 (many changes // mostly in JS functions and source files)
*
*
* v0.4 > v0.5
* _________________
*
* 	1- enabled config variable and/or URL argument for overriding default autoplay (this was long overdue)
*		usage: set $autoplay var ['true'/'false'] or pass argument via URL ([?/&]autoplay=[false/true])
*
*	2- refined sorting options and added the $id3sort config variable
*		usage: [assuming $useID3=true] set $ID3sort to 'track' or 'artist' to define sorting terms
*
*	3- added config variable and/or URL argument for disabling non essential information (show only playlist)
*		usage: pass argument via URL ([?/&]makesparse=true) or set $skipdesc=true;
*
*	4- updated to accomodate JPlayer v2.1 as a drop-in solution
*
*
* v0.5 > v0.6
* _________________
*
*	1- revised based on JPlayer v2.1
*
*	2- added configurable $playermode option to allow for audio only or av mode // created a splash graphic (poster) for default use
*		usage: set to 'audio' for an audio only playlist (default)
*		or 'av' for audio (with poster) and video playlist
*
*	3- added configurable $showfromtext option // 'showfrom' text can be easily disabled
*		usage: set to 'true' to display information about which folder a song resides in (default)
*		or 'false' to ignore that information
*
*	4- added configurable $allowdownload option // when disabled, downloads are disabled
*		usage: set to 'true' to allow downloads (default) or 'false' to disable
*
*	5- tested with two themes (blue.monday and pink.flag) // i like blue.monday for audio or video, and pink.flag only for video
*
*	6- updated to getID3 v1.9.3
*
*
* v0.6 > v0.65
* _________________
*
*	1- added code to display embedded (via getID3) or name-based album art as poster image
*
*	2- added code to derive the jPlayer's 'supplied' argument based on occurring and allowed file extensions (fixed firefox issue)
*
*
* v0.65 > v0.67
* _________________
*
*	1- fixed 'bug' where poster images would only display IF playlist included a video file
*
*	2- fixed inconsistencies with $playermode:
*		audio mode was showing video area if video files found during scan -- now audio mode disables video files from being found
*
*	3- fixed bug with $useID3=false (player wasn't working bc extra " after poster image set)
*
*	4- fixed bug with default sorting method logic
*
*	5- added id3 sort by 'track' method and abilty to force two digit track numbers (for more proper sorting)
*
*	6- added code to make assumption about ambiguous extensions (.mp4, .ogg) >> (m4v/m4a, oga/ogv) which addresses some problems
*
*	7- fixed resulting(?) bug with making assumptions from ambiguous filenames -- may cause trouble if playing mixed ogv/oga OGG files or m4a/m4v MP4s
*
*	8- fixed bug with filtering // changing 'submit' button text caused filtering to stop working.  changed logic to resolve.
*
*	9- added logic to collapse multiple files (mysong.ogg, mysong.mp3, mysong.mp4) to one line item fed properly to jPlayer
*
*	10-fixed bug with Firefox where some unicode filenames would not play (Flash fallback issue) // used JS function encodeURI()
*
*	11-worked around fixed ff bug where video played by flash fallback requires (not relative paths)
*
*	12-added code so that passing ?debug=true URL argument enables debug mode
*
*	13-cleaned code handling of $folders and $folderpaths (from $fileinfo[])
*
*	14-in filter/linkable folder list, added variables to 1)display item count per folder and 2)hide folders with '0' allowed items (often, 'parent' folders) 
*
*	15-removed orphaned 'by' when using $getID3=false (hence, no 'artist' exists) and referenced jp-artist css style for 'from' text, which is now appended to the title line
*
*	16-simplified/revised 'sorting' code (no separate $ID3sort -- pick sorting method that works for your files) - $sortby takes all args
*
*	17-added $assumetracksorting option to force track # sorting (assuming album play) when $sortby='title' and folder contents are less than defined variable
*
*	18-added basic time-based caching for playlists (made it configurable by php setting and URL argument)
*		when creating the cached playlist, expect a 5%-15% performance hit (increase) vs loading the playlist from the filesystem
*		when loading a cached playlist, expect a drastic reduction in file processing time 
*			$useID3=true > reduced by a factor of ~550 [from .18 to .0003 seconds] vs loading from the filesystem
*			$useID3=false > reduced by a factor of ~10 [from .002 to .0002 seconds] vs loading from the filesystem
*				*while $useID3=false is significantly faster than $useID3=true, it is drastically slower 
*				(and, less capable if your files have ID3 information) than loading a previously cached playlist
*				Keep in mind that initial cache file creation/store takes longer time...
*
*	19-added url arguments ?clearcache=true and ?updateplaylist=true  >> the first clears all cached playlists and the second clears only the 'current' one
*
*	20-revised code based on JPlayer v2.2 >> minor js/jquery.jplayer.min.js modifications to correct centering album art issue
*
*/
/*
*  TBD Short Term (Next Version?)
* _________________
*
*	hide unsupported filetypes for certain browsers (don't create playlist item for non supported files if the proper alternative (to allow play) is not supplied)
*		for instance, don't display ogg/oga (if only option) for IE since it can't play it natively or through a plugin
*		^^ this looks like way too much work.  have to allow for browser versions and all that.  no.
*
*	scrobble to last.fm (work through real api OR use existing code) // ref zina jplayer playlister
*
*	find api for pulling artwork? last.fm  // http://legroom.net/software/getalbumart
*
*	update 'shuffle' option to use jplayer's built-in function
*
*	add sub sort?  can we sort by album (or, file location / folder), then track number within album?
*
*	add code to keep track in middle of playlist (helpful for large playlists // ref nick code)
*		[done] add ability to (easily) size the window // roughly three tracks high?
*
*	using error logs, fix errors.
*
*	separate out changelog (already done on page?), tbd, and future
*
*
*
*
*  TBD Long Term (/lower priority)
* _________________
*
* 	add title 'right-click to download' as title text for file download // add option to change extension name to 'download'?
*		>> looks like will require jplayer.playlist.js modification
*
*	enable drag and drop sorting
*
*	[firefox specifically // JPlayer Issue?  Verify on jplayer demo and that it exists after 2.2 update]
*	on 'fit screen' click, whatever is playing paused.  happens on collapse, too.
* 
*/
/*
 *	Development Variables -- For Developer use, normally
 */
$ver='v0.67';
$verfulltitle='JPlaylister (JPlayer Playlister)';
$vershorttitle='JPlaylister';
$versubtitle='JPlayer Audio/Video Playlist Generator';
$verreleasedate='2013.03.17';
$verdev=false; //should be set to false for proper, normal use
/*
 *	Configuration Variables
 */
//set media directory (ex. 'songs', 'music', 'media/songs')
$relMusicDir='media'; //this directory is where the playlister starts looking for files -- subfolders are recursed
//set default playlist sorting order options
$sortby='mtime';//set to 'title' for track title sorting, 'artist' for artist sorting, 'track' for track # sorting,
				//'mtime' for recent to oldest sorting, 'name' for filename sorting or 'random' for random order
	$leadingzero=false; //[requres $sortby='track'] if true, format track number as two digits long for more consistent display
	$assumetracksorting=true; //set to true to sort by track (instead of title) when folder contents less than $tracksortingnum (assuming album play)
		$tracksortingnum=20; //[requires $assumetracksorting=true] -- overrides other sorting methods to sort by track for smaller containing folders
//set to true to easily disable the right floating div -- alternately, you can just delete it
$skipdesc=false; //set to true to remove the descriptive right column (also possible using [?/&]makesparse=true argument)
//set playlist action, display, and information options
$autoplay='false'; //set to 'false' to disable autoplay, 'true' to enable (also possible using [?/&]autoplay=[false/true] argument)
$useID3=true; //set to false for filename information only, much faster page loads, and no album art extraction
	// !! $useID3, while incredibly convenient, increases page load time dramatically (from .0065 to .2 seconds on a 20 song test playlist -- a factor of 30 increase)
$usealbumart=true; // set to false to disable album art display (will still look for $poster location for default)
$showfromtext='true'; // if 'true', from [folder location information] will be shown under the playlist items
$allowdownload='true'; //if not 'true', download will not be allowed (as easily)
$playermode='av'; //set to 'audio' for audio only, 'av' for album art/poster/video display -- filetypes defined in function
	$posterlocation='graphics/jplaylister_poster2.jpg'; //change to set 'default' poster if in av/video mode ![requires $playermode='av']
$displayallfolders=TRUE; //set to FALSE to hide folders with no playable items in them (hides the 'artist' link in an 'artist/album' structure)
$displayfoldercounts=TRUE; //set to FALSE to hide # of songs directly in the link/filter folder
//$plheight=' style="height:400px; overflow: auto;"'; // enable to set playlist height css -- disabled for normal use, dynamic mode
$cacheplaylist=false; // When set to true, JS Playlist and Filtering HTML are stored in/pulled from cache files (decreased page load time, hopefully)
	$cachetime=(60*60); //default: 1 hr (3600 seconds) -- set to # of seconds to reference cached playlist (based on when it was last updated)
	$clearcache; //this variable isn't used, but passing ?clearcache URL argument causes the cache folder to be cleared of cached playlists
	$updateplaylist; //this variables isn't used, but passing the ?updateplaylist URL argument causes the current playlist to be rebuilt on page load 
					 //(effectively, disabling cacheing for this load and updating the stored playlist)
//file extension handling
$extensionsarray=array('mp4','ogg'); //these file extensions are treated as the following for jplayer to properly process
$treatextensionsarray=array('m4v','oga'); //if your mp4 files are audio and ogg files video, you can change to 'm4a' and 'ogv'
//html
$charset='utf-8'; //formerly, this was 'iso-8859-1'
//set for debug
$debug=false; //set to true for tons of debug echoes
//allow $debug override
if($_GET['debug']=='true')
	$debug=true;
//time grab page start
$loadtime['pagestart']=microtime(true);
/*** NOTE ***
	There are also configurable settings within the recursiveGetSongs() function at the end of this page
		These variables determine which file types (via the file extension) are added to the playlist
		and allows the specification of any folders which should be ignored
****/
// END STANDARD CONFIGURATION
/*
 *   Filtering
 */
//check for POST filter information >> passed from the 'apply filter' button
if(isset($_POST[submit])){
	if(count($_POST["filter"])==0){
		//error -- all music filtered out
		echo 'uh...i can\'t make a playlist if you filter everything out.  i\'m playing everything!';
		//reset filter -- make them pay
		$filtered=(array)$_POST["filter"];
	}
	else{
		//compare reference vs filter
		$filtered=array_diff((array)$_POST["filterref"], (array)$_POST["filter"]);
	}
	if($debug==true){
		echo'<br />filtering: ';
		var_dump($filtered);
	}
}
//check for GET filter information >> passed via the URL from clicking on a subfolder
if($_GET['filtered']!=''){
	$filtered=explode(',',$_GET['filtered']);
}
//prep for sorting links
if($filtered!=''){
	$filteredarg='&amp;filtered='.implode(',', $filtered);
}
/*
 * Check for passed variables
 */
//set $relMusicDir based on name if passed (this is a variant of a filter option)
if($_GET["name"]!='')
	$relMusicDir=$relMusicDir.'/'.$_GET["name"];
//check for name by request
if($_GET["name"]!=''){
	$name=$_GET["name"];
	$namearg='&name='.$name;
}
//check for sort by request
if($_REQUEST["sortby"]!=''){
	$sortby=$_REQUEST["sortby"];
	$sortbyarg='&sortby='.$sortby;
}
//check for makesparse request
if($_REQUEST["makesparse"]!=''){
	$makesparse=$_REQUEST["makesparse"];
	$makesparsearg='&makesparse='.$makesparse;
}
//check for disable/enable autoplay arg
if(isset($_GET["autoplay"])){
	$autoplay=$_GET["autoplay"];
}
//check for disable/enable autoplay arg
if(isset($_GET["cacheplaylist"])){
	if($_GET["cacheplaylist"]=='false')$cacheplaylist=false;
	else if($_GET["cacheplaylist"]=='true')$cacheplaylist=true;
}
/*
 * Determine whether playing a subset (subfolder) of music, or all // Set display information
 */
if($_GET["name"]=='')
	$from='Displaying music from all folders and subfolders';
else{
	$from='Displaying music from '.clean($_GET["name"], 'display', $debug);
	$playalllink='<br /><a href="./">&laquo; Back to Play All</a>';
}
/*
 * Prep to get media (artist / track) information for playlist
 */
//set temp vars
$fileinfo=array();
$fileinfo['count']=0;
//require getID3 code if using it
if($useID3==true){
	//get php version in parts for comparison
	$phpvparts=explode('.',phpversion());
	//display error if php version is lower than needed for GETID3
	if($phpvparts[0]<5 OR ( $phpvparts[0]=5 AND $phpvparts[1]<1 AND $phpvparts[2]<5)){
		echo 'GETID3 1.9.3 (used for ID3 tag information parsing) requires PHP 5.0.5 or higher.  Get older version or upgrade PHP for ID3 usage.  <br />*disabling id3*';
	}
	else{
		//for ID3 (not just filename) information, include getID3 php class
		require_once('getid3/getid3.php');
		// Initialize getID3 engine
		$getID3 = new getID3;
	}
}
//time grab function start
$loadtime['functionstart']=microtime(true);
/*
 *	Check for Caching Option
 */
//Cache has been disabled -- operate normally
if($cacheplaylist==false){
	/*
	 * 	Call function to get information for all songs in target directory (and sub-directories) and store in an array
	 */
	 $fileinfo=recursiveGetSongs($relMusicDir, $fileinfo, $useID3, $getID3, null,$debug, $filtered,$playermode,$usealbumart);
}
//Otherwise, assume caching is on 
else{
	//set cache filename based on $relMusicDir (base dir and specified dir) and $filtered (if used)
	$cfn='cache/'.str_replace(array('/',' '),'',$relMusicDir).'__'.str_replace(array('/',' '),'',@implode('_', $filtered)).'.cache';
	//debug
	if($debug==true)echo'<br />trying to pull cache file -- filemtime: '.@filemtime($cfn).' | time: '.time().' | cachetime: '.$cachetime;
	/*
	 *  Check for URL arguments and handle accordingly
	 */
	//check for ?clearcache argument -- if set, clear cache folder
	if(isset($_GET[clearcache])){
		//delete all cache files
		foreach(glob('cache/*.cache') as $file) {
			if(is_dir($file))
				rrmdir($file);
			else
				unlink($file);
		}
	}
	//if ?updateplaylist URL argument is set, delete and re-create playlist
	else if(isset($_GET[updateplaylist])){
		unlink($cfn);
	}
	//end URL argument check/handle
	//see if file exists and mtime is older than allowed
	if( file_exists($cfn) AND ( ( filemtime($cfn)-time() < $cachetime ) ) ){
		//debug
		if($debug==true)echo' -- SUCCESSFULLY pulled from file cache';
		//fetch file contents, and unserialize into an array
		$fileinfo=unserialize(file_get_contents($cfn));
	}
	//otherwise, get files from filesystem and cache results
	else{
		//Call function to get information for all songs in target directory (and sub-directories) and store in an array
		$fileinfo=recursiveGetSongs($relMusicDir, $fileinfo, $useID3, $getID3, null,$debug, $filtered,$playermode,$usealbumart);
		//Cache variable to file
		$myFile = $cfn;
		$fh = fopen($myFile, 'w') or die("can't open file");
		$stringData = serialize($fileinfo); //serialize array for file storage
		fwrite($fh, $stringData);
		fclose($fh);
		//debug
		if($debug==true)echo '<br />wrote cache file: '.$cfn;
	}
}
//END CACHING
//time grab function end
$loadtime['functionend']=microtime(true);
//debug -- show results
if($debug==true) var_dump($fileinfo);
/*
 *	Prepare jplayer filetype inclusions as determined by the available file extensions
 */
//fix problem -- when no 'video' is found, a video extension is supplied regardless to ensure album art is displayed if found
if(!@array_key_exists('m4v', array_keys($fileinfo['extensions'])) AND $playermode!='audio'){ //throws warning -- irrelevant if no files are found
	$fileinfo['extensions']['m4v']=1;
	if($debug==true)
		echo'<br />no m4v found!  will add it: ';
}
//create 'supplied' extension list from the keys of $fileinfo['extensions']
$supplied='supplied: "'.implode(', ',array_keys($fileinfo['extensions'])).'"';
//replace 'mp4' and 'ogg', if they exist, with preferred disambiguated extension (as defined by configuration variable)
$supplied=str_replace($extensionsarray, $treatextensionsarray, $supplied);
//debug
if($debug==true)echo$supplied;
/*
 *	Make subfolder links and filtering options
 */
//if($fileinfo['folders']!=''){
if(!empty($fileinfo['folders'])){
	//clear variables
	$comma='';
	$count=0;
	//get folder and path information
	$folders=$fileinfo['folders'];
	$folderpaths=$fileinfo['folderpaths'];
	//begin the html to display folders filter	
	$foldershtml='<form name="filter" action="'.$_SERVER[PHP_SELF].'" method="post">Filter playlist by subfolder<ul>';
	//check for sortby -- if set, store as hidden variable
	if(isset($_GET[sortby])){
		$foldershtml.='<input type="hidden" name="sortby" value="'.$_GET[sortby].'" />';
	}
	//check for makesparse -- if set, store as hidden variable
	if(isset($makesparse)){
		$foldershtml.='<input type="hidden" name="makesparse" value="'.$makesparse.'" />';
	}
	//DEBUG
	if($debug==true){
		var_dump($folders);
		var_dump($filtered);
	}
	//merge filtered and non filtered for display // sort for alphabetization
	if($filtered!=''){
		$folders=array_merge($folders, $filtered);
		sort($folders);
	}
	if($debug==true){
		echo'<br /><br />after merge and sort';
		var_dump($folders);
	}
	//make linkable / filterable folders
	foreach ($folders as $value) {
		if($folderpaths[$count]=='./')$folderpaths[$count]='';
		//handle displaying folder counts
		if($displayfoldercounts==TRUE){
			if(!isset($fileinfo[$value.'_count']))
				$thiscount=' (0)'; //fixes 'null' with '0'
			else
				$thiscount=' ('.$fileinfo[$value.'_count'].')';  //shows # of files directly in folder (excluding subfolders)
		}
		//handle hiding display of folders with 0 counts
		if($displayallfolders==FALSE){
			if(isset($fileinfo[$value.'_count'])){
				$showthis=true;
			}
			else{
				$showthis=false;
			}		
		}
		//otherwise, show all
		else
			$showthis=true;
		$value=clean($value, null, $debug);
		if($filtered!='' AND in_array($value, $filtered))$checked='';
		else $checked=' checked="yes"';
		if($debug==true){echo$checked;}
		if($_GET['name']!=$value AND $value!='' AND $showthis==true)$foldershtml.=$comma.'<li><input type="checkbox" name="filter[]" '
			.'value="'.clean($_GET["name"].'/'.$value, 'link', $debug).'"'.$checked.'>'
			.'<a href="./?name='.clean($_GET["name"].'/'.$value, 'link', $debug).$sortbyarg.$makesparsearg.'">'.clean($value, 'display', $debug).$thiscount.'</a>'
			.'<input type="hidden" name="filterref[]" value="'.clean($_GET["name"].'/'.$value, 'link', $debug).'" />'
			.'</li>';
		//inc counter
		$count++;
	}
	$foldershtml.='</ul><input type="submit" name="submit" value="Apply Filter" /></form>';
}
//clear comma
$comma='';
//debug
if($debug==true)echo'---<br />filename: '.$files[0]
	.'<br />ID3 artist - title: '.$tags[0]
	.'<br />file count: '.$count;
//unset subarrays to avoid breaking playlist creation
unset($fileinfo['folders']);
unset($fileinfo['folderpaths']);
unset($fileinfo['extensions']);
/*
 *	Preparing Sorting Links / Notifications
 */
//sort by mtime (most recent first)
if($sortby=='mtime'){
	$fileinfo=subval_sort($fileinfo, 'modified', 'd');
	$sortedbytext='Sorted by date added';
}
else if($sortby=='name'){	
	$fileinfo=subval_sort($fileinfo, 'filename');
	$sortedbytext='Sorted by file name';
}
//randomize array if requested (TBD: replace this with jPlayer random function)
else if($sortby=='random'){
	shuffle($fileinfo);
	$sortedbytext='Playlist randomized';
}
else if($sortby=='artist'){
	$sortedbytext='Sorted by artist name';
	$fileinfo=subval_sort($fileinfo, 'artist');
}
else if($sortby=='track' OR ($sortby=='title' AND $assumetracksorting==true AND count($fileinfo)-3<$tracksortingnum) ){
	$sortby='track';
	$sortedbytext='Sorted by track number';
	$fileinfo=subval_sort($fileinfo, 'track');
}
else if($sortby=='title'){
	$sortedbytext='Sorted by track name/title';
	$fileinfo=subval_sort($fileinfo, 'title');
}
else{
	echo'<br />Unknown sort method<br />';
}
//show sorting information and options
$sortoptions='Sort by: <a href="./?sortby=mtime'.$namearg.$filteredarg.$makesparsearg.'">Date Added</a> | '
	.'<a href="./?sortby=name'.$namearg.$filteredarg.$makesparsearg.'">File Name</a> | '
	.'<a href="./?sortby=title'.$namearg.$filteredarg.$makesparsearg.'">Title</a> | '
	.'<a href="./?sortby=artist'.$namearg.$filteredarg.$makesparsearg.'">Artist</a> | '
	.'<a href="./?sortby=track'.$namearg.$filteredarg.$makesparsearg.'">Track #</a> | '
	.'<a href="./?sortby=random'.$namearg.$filteredarg.$makesparsearg.'">Shuffle Songs</a>';
//time grab html start
$loadtime['htmlstart']=microtime(true);
?>
<!DOCTYPE HTML>
<!--[if lt IE 7]> <html class="ie6" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="ie7" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="ie8" lang="en"> <![endif]-->
<!--[if gt IE 8]><!-->
<html class="no-js" lang="en">
<!--<![endif]-->
<head>
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-46256151-2', 'enricofuerte.nl');
  ga('send', 'pageview');

</script>
<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
<title>Enrico Fuerte</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta name="keywords" content="EnricoFuerte, Erik Sterk, Techno, Music, Netherlands, Enrico, Fuerte, DJ, Producer" />
<meta name="description" content="Enrico Fuerte techno-DJ Producer from the Netherlands.">
<!-- Favicon -->
<link rel="shortcut icon" href="favicon.ico">
<!-- Apple Touch Icons -->
<link rel="apple-touch-icon" href="apple-touch-icon-iphone.png" /> 
<link rel="apple-touch-icon" sizes="72x72" href="apple-touch-icon-ipad.png" /> 
<link rel="apple-touch-icon" sizes="114x114" href="apple-touch-icon-iphone4.png" />
<link rel="apple-touch-icon" sizes="144x144" href="apple-touch-icon-ipad3.png" />
<!-- Main Stylesheet -->
<link rel="stylesheet" href="css/stylesheet.css">
<!-- Font Awesome Style -->
<link rel="stylesheet" href="font-awesome/css/font-awesome.css">
<!-- Quicksand Gallery Styles -->
<link media="screen" type="text/css" href="css/lightbox.css" rel="stylesheet" />
<link media="screen" type="text/css" href="css/portfoliostyles.css" rel="stylesheet" />
<!-- Custom Scrollbar Styles -->
<!-- this cssfile can be found in the jScrollPane package -->
	<link rel="stylesheet" type="text/css" href="css/jquery.mCustomScrollbar.css" />	
<!-- JPlayer styles -->
<link href="skin/blue.monday/jplayer.blue.monday.css" rel="stylesheet" type="text/css" />
<!-- Google Fonts -->
<link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css'>
<link href='http://fonts.googleapis.com/css?family=Audiowide' rel='stylesheet' type='text/css'> 
<!-- All JavaScript at the bottom, except this Modernizr build.
     Modernizr enables HTML5 elements & feature detects for optimal performance.
     Create your own custom Modernizr build: www.modernizr.com/download/ -->
<script src="js/modernizr-2.6.1.min.js"></script>
<!--[if IE 7]>
<script src="http://ie7-js.googlecode.com/svn/version/2.1(beta4)/IE8.js"></script>
<![endif]-->
<!-- Open Graph Social Media Meta -->
<meta property="og:url" content="http://www."/>
<meta property="og:title" content="Enrico Fuerte"/>
<meta property="og:site_name" content="Enrico Fuerte"/>
<meta property="og:description" content="Techno DJ Producer"/>
<meta property="og:type" content="website"/>
<meta property="og:image" content="images/Icon Enrico fuerte image.png"/>
</head>
<body>
<!-- Header -->
<header>
  <div class="container">
    <div class="row">
<!-- Logo -->
	  <figure class="grid12"><img src="/images/Icon Enrico fuerte image.png" alt="Enrico Fuerte"></figure>
<!-- Main Navigation -->
        <nav id="menu" class="nav-collapse collapse">
          <ul class="nav">
            <li><a href="#intro" class="intro">home</a></li>
            <li><a href="#about" class="about"><i class="icon-file-text"></i><span>About</span></a></li>
            <li><a href="#upcoming" class="upcoming"><i class="icon-microphone"></i><span>Releases</span></a></li>
            <li><a href="#booking" class="booking"><i class="icon-smile"></i><span>Booking</span></a></li>
            <li><a href="#media" class="media"><i class="icon-file"></i><span>Gallery</span></a></li>
            <li><a href="#contact" class="contact"><i class="icon-compass"></i><span>Contact</span></a></li>
          </ul>
        </nav>
<!-- Book Me Button --> 
        <a href="#" class="button booking-scroll" id="book-me">Book Me</a></div>
    </div>
  </div>
</header>
<!-- End Header -->
<!-- First Section Intro -->
<div id="intro-bg"></div>
<section id="intro" class="odd">
  <article id="intro-content" class="container">
    <div id="intro-inner" class="row">
      <div class="grid11">
        <h1><span class="part1">Enrico Fuerte&nbsp;</span><span class="part2">:Techno&nbsp;</span><span class="part3">from The Netherlands&nbsp;</span><br><span class="part4">DJ Performance&nbsp;</span><span class="part5">& Productions</span> </h1>
      </div>
    </div>
  </article>
  <a href="#" class="about-scroll"><i class="icon-chevron-down"></i></a>
</section>
<!-- End first section Intro -->
<!-- Section About -->
<section id="about" class="even">
  <div class="case">
     <div class="container">
      <div class="row">
<!-- About Picture and Text -->
            <figure class="grid15"><img src="/images/Enrico1.jpg" alt="Enrico Fuerte"></figure>
          <article class="grid8">
          <h2 class="case-header">Enrico Fuerte</h2>
          <p class="lead">Techno DJ Producer</p>
             <p align="justify"><a href="#booking" class="booking">Biography</a> As a young kid Enrico was always busy with all sorts of music, played by his father on tapes in the car or at home. After he heard the well-known track 'Oxygen' by Jean Michel Jarre he was sold on electronic music.Recording radio shows with electronic music, or putting all the tracks together in one mix on his tape player, became a hobby at an early age. Years later Enrico decided to play the tracks he liked the most, and these became his first recordings. Shopping for vinyl in and outside of Europe through the internet or in record stores was how he frequently spent his time to find the right tracks for his DJ sets. As his music evolved the sound became more and more Techno related and especially the dark and explosive kind of Techno. Sending some demo's to a few party organisations was an initiative that worked out very well. Enrico got a few good gigs alongside a couple of very big names in the industry and played in some big clubs, like Escape in Amsterdam and Cafe d'Anvers in Antwerp, Belgium.</p>
          </article>
<!-- About Media Player -->
          <article class="grid8">
<?php
//prep jplayer controls
	if($playermode=='audio'){
		$jpinstance = <<<EOF
		<div id="jquery_jplayer_1" class="jp-jplayer" style="height: 0px;"></div>
		<div id="jp_container_1" class="jp-audio">
			<div class="jp-type-playlist">
				<div class="jp-gui jp-interface">
					<ul class="jp-controls">
						<li><a href="javascript:;" class="jp-previous" tabindex="1">previous</a></li>
						<li><a href="javascript:;" class="jp-play" tabindex="1">play</a></li>
						<li><a href="javascript:;" class="jp-pause" tabindex="1">pause</a></li>
						<li><a href="javascript:;" class="jp-next" tabindex="1">next</a></li>
						<li><a href="javascript:;" class="jp-stop" tabindex="1">stop</a></li>
						<li><a href="javascript:;" class="jp-mute" tabindex="1" title="mute">mute</a></li>
						<li><a href="javascript:;" class="jp-unmute" tabindex="1" title="unmute">unmute</a></li>
						<li><a href="javascript:;" class="jp-volume-max" tabindex="1" title="max volume">max volume</a></li>
					</ul>
					<div class="jp-progress">
						<div class="jp-seek-bar">
							<div class="jp-play-bar"></div>
						</div>
					</div>
					<div class="jp-volume-bar">
						<div class="jp-volume-bar-value"></div>
					</div>
					<div class="jp-time-holder">
						<div class="jp-current-time"></div>
						<div class="jp-duration"></div>
					</div>
					<ul class="jp-toggles">
						<li><a href="javascript:;" class="jp-shuffle" tabindex="1" title="shuffle">shuffle</a></li>
						<li><a href="javascript:;" class="jp-shuffle-off" tabindex="1" title="shuffle off">shuffle off</a></li>
						<li><a href="javascript:;" class="jp-repeat" tabindex="1" title="repeat">repeat</a></li>
						<li><a href="javascript:;" class="jp-repeat-off" tabindex="1" title="repeat off">repeat off</a></li>
					</ul>
				</div>
        
				<div class="jp-playlist"$plheight>
					<ul>
						<li></li>
					</ul>
				</div>
				<div class="jp-no-solution">
					<span>Update Required</span>
					To play the media you will need to either update your browser to a recent version or update your <a href="http://get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>.
				</div>
			</div>
		</div>
EOF;
	}
	else{
		$jpinstance = <<<EOF
		<div id="jp_container_1" class="jp-video jp-video-270p">
			<div class="jp-type-playlist">
				<div id="jquery_jplayer_1" class="jp-jplayer"></div>
				<div class="jp-gui">
					<div class="jp-video-play">
						<a href="javascript:;" class="jp-video-play-icon" tabindex="1">play</a>
					</div>
					<div class="jp-interface">
						<div class="jp-progress">
							<div class="jp-seek-bar">
								<div class="jp-play-bar"></div>
							</div>
						</div>
						<div class="jp-current-time"></div>
						<div class="jp-duration"></div>
						<!-- added style below to fix floating issue (default was clear:both by css) not important to code function -->
						<div class="jp-controls-holder" style="clear:left;">
							<ul class="jp-controls">
								<li><a href="javascript:;" class="jp-previous" tabindex="1">previous</a></li>
								<li><a href="javascript:;" class="jp-play" tabindex="1">play</a></li>
								<li><a href="javascript:;" class="jp-pause" tabindex="1">pause</a></li>
								<li><a href="javascript:;" class="jp-next" tabindex="1">next</a></li>
								<li><a href="javascript:;" class="jp-stop" tabindex="1">stop</a></li>
								<li><a href="javascript:;" class="jp-mute" tabindex="1" title="mute">mute</a></li>
								<li><a href="javascript:;" class="jp-unmute" tabindex="1" title="unmute">unmute</a></li>
								<li><a href="javascript:;" class="jp-volume-max" tabindex="1" title="max volume">max volume</a></li>
							</ul>
							<div class="jp-volume-bar">
								<div class="jp-volume-bar-value"></div>
							</div>
							<ul class="jp-toggles">
								<li><a href="javascript:;" class="jp-full-screen" tabindex="1" title="full screen">full screen</a></li>
								<li><a href="javascript:;" class="jp-restore-screen" tabindex="1" title="restore screen">restore screen</a></li>
								<li><a href="javascript:;" class="jp-shuffle" tabindex="1" title="shuffle">shuffle</a></li>
								<li><a href="javascript:;" class="jp-shuffle-off" tabindex="1" title="shuffle off">shuffle off</a></li>
								<li><a href="javascript:;" class="jp-repeat" tabindex="1" title="repeat">repeat</a></li>
								<li><a href="javascript:;" class="jp-repeat-off" tabindex="1" title="repeat off">repeat off</a></li>
							</ul>
						</div>
						<div class="jp-title">
							<ul>
								<li></li>
							</ul>
						</div>
					</div>
				</div>
				<div class="jp-playlist"$plheight>
					<ul class="scroll-pane">
						<!-- The method Playlist.displayPlaylist() uses this unordered list -->
						<li></li>
					</ul>
				</div>
				<div class="jp-no-solution">
					<span>Update Required</span>
					To play the media you will need to either update your browser to a recent version or update your <a href="http://get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>.
				</div>
			</div>
		</div>
EOF;
	}
?>
<div>
	<!-- show sorting options -->
	<?php 
	//show jplayer instance code
	echo $jpinstance;
	?>
</div>
<!-- End Media Player -->
    </article>
        </div>
      </div>
    </div>
  <!-- End About Section 1 -->
  <!-- About Section Projects -->
  <!-- Project1 -->  
  <article id="project1" class="case odd">
    <div class="container"> 
      <div class="row">
        <figure class="grid8"><img src="/images/Enrico2.jpg" alt="Enrico Fuerte"></figure>
        <div class="grid4 case-content">
          <div class="inside">
            <h2 class="case-header">Techno</h2>
            <p class="lead">Producing from Holland</p>
            <p align="justify">Early in 2011 Enrico wanted to expand his musical boundaries, which is why he became a producer as well as a DJ. With no production experience at all, Enrico puts his skill and knowledge of all the kinds of music he likes to play into the tracks he produces, and still creates his own personal Techno sound. Within one year he has had releases on albums and 3 EP's on a couple of labels he liked and supported when he was a DJ. As he gains more knowledge and experience in producing music, his tracks are getting more and more support and feedback from many well-known DJ's. </p>
            <ul class="project-list small-text">
              <li><i class="icon-tag"></i>Techno</li>
              <li><i class="icon-link"></i><a href="http://www.enricofuerte.com/" target="_blank">www.enricofuerte.com</a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </article>
  <!-- End Project1 -->
  <!-- Project2 -->
  <article id="project2" class="case even"> 
    <div class="container"> 
      <div class="row">
        <figure align="right" class="grid8"><img src="images/Dummy2.jpg" alt="Enrico Fuerte"></figure>
        <div class="grid4 case-content">
          <div class="inside">
            <h2 class="case-header">Performance</h2>
            <p class="lead">Creating the experience</p>
            <p align="justify">Creating a performance is all about emotion on the stage for Enrico. Interacting with the crowd , feeling the mood and energy they send to the stage, make them want more, and keep them dancing - that is what an Enrico Fuerte performance is all about.</p>
            <ul class="project-list small-text">
              <li><i class="icon-tag"></i>Techno, Dark, Performance</li>
              <li><i class="icon-link"></i><a href="http://www.enricofuerte.com/" target="_blank">www.enricofuerte.com</a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </article>
  <!-- End Project2 -->
<!-- End Section About -->
<!-- Section Upcoming -->
<section id="upcoming" class="even" data-0="background-position:0px 280px;" data-10000="background-position:0px -120px;">
  <div class="container">
    <div class="row">
      <article class="grid11">
        <h2>Releases - EP's - Remixes</h2>
      </article>
    </div>
	
	<div class="row">
      <article class="grid4 small-text">
       <p><figure class="grid12"><img src="images/Studio.jpg" alt="Mi-estudio"></figure></p> 
      </article>
      <article class="grid4 small-text">
      <p><iframe width="100%" height="166" scrolling="no" frameborder="no" src="https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/tracks/106854028"></iframe> </p>  
      <p></p>
       </article>
      <article class="grid4 small-text">
      <h2>Mi Estudio - Enrico Fuerte </h2>
	    
      </article>
    </div>
	
    <div class="row">
      <article class="grid4 small-text">
        <p href="http://www.beatport.com/track/krakeel-enrico-fuerte-remix/4742957"> <figure class="grid12"><img src="images/Krakeel.jpg" alt="Krakeel"></figure>
      </article>
      <article class="grid4 small-text">
        <p> <iframe width="100%" height="166" scrolling="no" frameborder="no" src="https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/tracks/112444495"></iframe></p>
       <p></p>
      </article>
      <article class="grid4 small-text">
        <h2>Kemmi Kamachi - Krakeel (Enrico Fuerte Remix)</h2></p>
		
      </article>
    </div>
	
    <div class="row">
      <article class="grid4 small-text">
       <p href="http://www.beatport.com/release/propaganda/1040918"><figure class="grid12"><img src="images/Propaganda.jpg" alt="Propaganda"></figure> 
      </article>
      <article class="grid4 small-text">
      <p><iframe width="100%" height="166" scrolling="no" frameborder="no" src="https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/tracks/64127275"></iframe> </p>  
      <p></p>
       </article>
      <article class="grid4 small-text">
      <h2>Propaganda - Enrico Fuerte </h2></p>
	    
      </article>
    </div>
	
	<div class="row">
      <article class="grid4 small-text">
       <p href="http://www.beatport.com/release/planet-x/1096490"><figure class="grid12"><img src="images/Planetx.jpg" alt="PlanetX"></figure> 
      </article>
      <article class="grid4 small-text">
      <p><iframe width="100%" height="166" scrolling="no" frameborder="no" src="https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/tracks/79277312"></iframe> </p>  
      <p></p>
       </article>
      <article class="grid4 small-text">
      <h2>Planet-X - Enrico Fuerte </h2></p>
	    
      </article>
    </div>
	
	
	<div class="row">
      <article class="grid4 small-text">
       <p href="http://www.beatport.com/release/saw-dust/1107135"><figure class="grid12"><img src="images/Sawdust.jpg" alt="Sawdust"></figure> 
      </article>
      <article class="grid4 small-text">
      <p><iframe width="100%" height="166" scrolling="no" frameborder="no" src="https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/tracks/96751788"></iframe> </p>  
      <p></p>
       </article>
      <article class="grid4 small-text">
      <h2>Sawdust - Enrico Fuerte </h2></p>
	    
      </article>
    </div>
	
	<div class="row">
      <article class="grid4 small-text">
       <p href="http://www.beatport.com/release/need-a-light-ep/902368"><figure class="grid12"><img src="images/Light.jpg" alt="Need a Light"></figure> 
      </article>
      <article class="grid4 small-text">
      <p><iframe width="100%" height="166" scrolling="no" frameborder="no" src="https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/tracks/34521581"></iframe> </p>  
      <p></p>
       </article>
      <article class="grid4 small-text">
      <h2>Need a Light - Enrico Fuerte </h2></p>
	    
      </article>
    </div>
	
  </div>
</section>
<!-- End Section Upcoming -->
<!-- Section Booking -->
 
  <section id="booking" class="case odd">
    <div class="container"> 
      <div class="row">
        <figure class="grid15"><img src="/images/Badkuip.jpg" alt="UPCOMING 07-12-2013 Badkuip Eindhoven"></figure>
        <div class="grid4 case-content">
          <div class="grid8">
            <h2 class="case-header">Booking</h2>
			<br>
			<br>
            <p class="lead">Information</p>
            <p align="justify"> All booking requests can be directed to Eindhoven Techno Agency. For information requests, please use the contactform below. For releases, remixes and podcasts, stay update by social media.  </p>
            <ul class="project-list small-text">
              <li><i class="icon-tag"></i>Information</li>
			  <br>
			  <br>
			  <p class="lead">Press</p>
            <p align="justify">For press, please send email to press@enricofuerte.nl or download the presskit. <a href="Presskit2013.pdf" target="_blank">Presskit</a></p>
            <ul class="project-list small-text">
			  <li><i class="icon-tag"></i>Press</li>
			  <br>
			  <br>
			  <p class="lead">Upcoming this Month</p>
            <p align="justify"> 07-12-2013 De Badcuyp Eindhoven </p>
            <ul class="project-list small-text">
			  <li><i class="icon-tag"></i>Agenda</li>
			  <br>
			  <br>
              				
<div>
<center>
<table border="0">
<tr>
<td> <a href="https://www.facebook.com/enrico.fuerte.7" <figure class="grid14"><img src="/images/facebook-icon.png" alt="Enrico Fuerte"></figure></a> </td>
<td> <a href="https://soundcloud.com/enrico-fuerte" <figure class="grid14"><img src="/images/soundcloud-icon.png" alt="Enrico Fuerte"></figure></a> </td>
<td> <a href="http://www.beatport.com/artist/enrico-fuerte/256504" <figure class="grid14"><img src="/images/beatport.jpg" alt="Enrico Fuerte"></figure></a> </td>
<td> <a href="https://itunes.apple.com/nl/artist/enrico-fuerte/id515450418?uo=4" <figure class="grid14"><img src="/images/itunes.jpg" alt="Enrico Fuerte"></figure></a> </td>
<td> <a href="https://twitter.com/enricofuerte" <figure class="grid14"><img src="/images/twitter.gif" alt="Enrico Fuerte"></figure></a> </td>
<td> <a href="#contact" <figure class="grid14"><img src="/images/link.png" alt="Enrico Fuerte"></figure></a> </td>
</tr>
</table>
</center>
</div>
			</ul>
          </div>
        </div>
      </div>
    </div>
  </article>

  

<!-- Section Media (Gallery) -->
<section id="media" class="odd">
  <div class="container">
  	  <div class="row">
      <div class="grid13">

        <h2>Photographs & Artwork</h2>   
                <!-- Quicksand Gallery -->
                <nav id="filter"></nav>
                 <section id="container_gallery" > 
        	       <ul id="stage">
            	     
                <li class="holder smooth" data-tags="Studio"><img src="images/foto1.jpg" alt="" />                    
                  <div class="go-top">
                    <p>
                      Live @XT3 Techno Radio Amsterdam, The Netherlands 2012
                  </p>  
                  <a href="images/foto1.jpg" data-rel="lightbox"><span class="view-big"><i class="icon-zoom-in"></i></span></a>
                 </div>                  
                </li>
                  <li class="holder smooth" data-tags="Studio"><img src="images/Krakeel.jpg" alt="" />
                  <div class="go-top">
                    <p>
                     Kemmi Kamachi - Krakeel EP (Enrico Fuerte Remix) 2013
                  </p>  
                  <a href="images/Krakeel.jpg" data-rel="lightbox"><span class="view-big"><i class="icon-zoom-in"></i></span></a>
                 </div> 
                  </li>
                  <li class="holder smooth" data-tags="Studio"><img src="images/Propaganda.jpg" alt="" />
                  <div class="go-top">
                    <p>
                      Enrico Fuerte - Propaganda EP 2012
                  </p>  
                  <a href="images/Propaganda.jpg" data-rel="lightbox"><span class="view-big"><i class="icon-zoom-in"></i></span></a>
                 </div> 
                  </li>
                  <li class="holder smooth" data-tags="Misc"><img src="images/foto10klein.jpg" alt="" />                 
                  <div class="go-top">
                    <p>
                      Live @ FYWFE Festival 2013
                  </p>  
                  <a href="images/foto10.jpg" data-rel="lightbox"><span class="view-big"><i class="icon-zoom-in"></i></span></a>
                 </div>                                     
                  </li>
                  <li class="holder smooth" data-tags="Club"><img src="images/foto7.jpg" alt="" />
                  <div class="go-top">
                    <p>
                      Enrico Fuerte Live @ Eiwit 2013
                  </p>  
                  <a href="images/foto7.jpg" data-rel="lightbox"><span class="view-big"><i class="icon-zoom-in"></i></span></a>
                 </div>  
                  </li>
                  <li class="holder smooth" data-tags="Studio"><img src="images/foto8.jpg" alt="" />
                  <div class="go-top">
                    <p>
                      FYWFE 2013 - Line Up
                  </p>  
                  <a href="images/foto8.jpg" data-rel="lightbox"><span class="view-big"><i class="icon-zoom-in"></i></span></a>
                 </div>
                  </li>
                  <li class="holder smooth" data-tags="Misc"><img src="images/foto4.jpg" alt="" />
                  <div class="go-top">
                    <p>
                    Half om Half 2012 TAC - Line Up
                  </p>  
                  <a href="images/foto4.jpg" data-rel="lightbox"><span class="view-big"><i class="icon-zoom-in"></i></span></a>
                 </div>
                  </li>
                 <li class="holder smooth" data-tags="Studio"><img src="images/foto5.jpg" alt="" />
                  <div class="go-top">
                    <p>
                      Label Night Cafe d'Anvers 2013 - Line Up
                  </p>  
                  <a href="images/foto5.jpg" data-rel="lightbox"><span class="view-big"><i class="icon-zoom-in"></i></span></a>
                 </div>
                 </li>
				 <li class="holder smooth" data-tags="Studio"><img src="images/foto6.jpg" alt="" />
                  <div class="go-top">
                    <p>
                    Fnoob Techno Volume 2 2013
                  </p>  
                  <a href="images/foto6.jpg" data-rel="lightbox"><span class="view-big"><i class="icon-zoom-in"></i></span></a>
                 </div>
                  </li>
				  <li class="holder smooth" data-tags="Club"><img src="images/foto2.jpg" alt="" />
                  <div class="go-top">
                    <p>
                    Connected Heerlen Dave Miller & Enrico Fuerte 2013
                  </p>  
                  <a href="images/foto2.jpg" data-rel="lightbox"><span class="view-big"><i class="icon-zoom-in"></i></span></a>
                 </div>
                  </li>
				  <li class="holder smooth" data-tags="Studio"><img src="images/foto3.jpg" alt="" />
                  <div class="go-top">
                    <p>
                    Impact Music Artist: Enrico Fuerte 2012
                  </p>  
                  <a href="images/foto3.jpg" data-rel="lightbox"><span class="view-big"><i class="icon-zoom-in"></i></span></a>
                 </div>
                  </li>
				  <li class="holder smooth" data-tags="Misc"><img src="images/foto11.jpg" alt="" />
                  <div class="go-top">
                    <p>
                    Enrico Fuerte Live @FYWFE 2013
                  </p>  
                  <a href="images/foto11.jpg" data-rel="lightbox"><span class="view-big"><i class="icon-zoom-in"></i></span></a>
                 </div>
                  </li>
                </ul>
            </section>
      </div>
    </div>       
  </div>
</section>
<!-- End Section Media(Gallery) -->
<!-- Section Contact -->
<section id="contact" class="odd">
  <div class="container">
    <div class="row">
      <div class="grid8 offset2">
        <h2>Contact</h2><br />
		<p align="justify"><h3>If you want to get in contact with Enrico Fuerte, Fill out the form below, or send an <a href="mailto:info@enricofuerte.com">eMail</a></h3><br /></p>
      </div>
    </div>
    <div class="row">
      <div class="grid6">
      	<div id="contact_form">
        <!-- Form Code Start -->		
        <div id="note"></div>
			<div id="fields">
				<form id="ajax-contact-form" action="mailto:WEBMASTER_EMAIL">
				<input type="text" name="name" value="Your name" onfocus="if(this.value==this.defaultValue)this.value=''" onblur="if(this.value=='')this.value=this.defaultValue"/><br />
				<input type="text" name="email" value="Your email" onfocus="if(this.value==this.defaultValue)this.value=''" onblur="if(this.value=='')this.value=this.defaultValue"/><br />
				<input type="text" name="subject" value="Subject" onfocus="if(this.value==this.defaultValue)this.value=''" onblur="if(this.value=='')this.value=this.defaultValue"/><br />
				<textarea name="message" rows="5" cols="25" onfocus="if(this.value==this.defaultValue)this.value=''" onblur="if(this.value=='')this.value=this.defaultValue">Your Message</textarea><br />
				<input class="btn" type="submit" name="submit" value="Send Message" />
				</form>
			</div>			 
				</div>
					<!-- End Contact Form -->
      </div>
      <!-- Adress and Information -->
      <div class="grid6">
        <div class="row"> 
          <div class="grid6">
            <address class="row small-text">
              <div class="grid3">
                <p><i class="icon-location-arrow"></i> Eindhoven <br>
                  The Netherlands</p>
				</div>
                <div class="grid3">
                <p><br>
                  <i class="icon-envelope"></i><a href="mailto:info@enricofuerte.com">info@enricofuerte.com</a></p>
              </div>
            </address>
            <div class="row">
              <div class="grid6">
              <!-- Google Maps -->
               <div class="gmaps">
                <iframe src="https://maps.google.nl/maps?f=q&amp;source=s_q&amp;hl=nl&amp;geocode=&amp;q=5611+Eindhoven&amp;aq=0&amp;oq=5611&amp;sll=52.469397,5.509644&amp;sspn=2.814373,4.938354&amp;ie=UTF8&amp;hq=&amp;hnear=5611+Eindhoven,+Noord-Brabant&amp;t=m&amp;z=7&amp;iwloc=A&amp;output=embed"></iframe>
               </div>
               <div class="facebook-like">
                <div class="fb-like" data-href="http://amp.themecon.net" data-send="true" data-width="400" data-show-faces="false" data-font="verdana"></div>
               </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<!--- End Section Contact -->
<!-- Footer -->
<div class="footer-container">
  <footer>
    <div class="container">
      <div class="row">
        <div class="grid6 first"> &copy; 2013 Pepijn Meijer / All Rights Reserved </div>
        <div class="grid6">
          <ul class="social">
            <li><a href="https://www.facebook.com/enrico.fuerte.7" class="fb" target="_blank"><i class="icon-facebook"></i></a></li>
          </ul>
        </div>
      </div>
    </div>
  </footer>
</div>
<!-- End Footer -->
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script> 
	<!-- the ScrollBar Design script -->
<script src="js/jquery.mCustomScrollbar.concat.min.js"></script>
<script>
    (function($){
        $(window).load(function(){
            $(".jp-playlist").mCustomScrollbar();
        });
    })(jQuery);
</script>	
<!-- Scroll to scripts -->
<script src="js/waypoints.min.js"></script>
<script src="js/jquery.scrollTo.js"></script> 
<script src="js/jquery.nav.js"></script>
<!-- Parallax script -->
<script src="js/skrollr.js"></script>
<script type="text/javascript">
	skrollr.init({
		forceHeight: false
	});
</script>
<!-- Quicksand -->
<script src="js/lightbox.js"></script>
<script src="js/jquery.quicksand.js"></script>
<script src="js/script_quicksand.js"></script>

<!--[if lt IE 9]>
<script src="js/PIE_IE678.js"></script>
<script src="js/respond.min.js"></script>
<![endif]-->
<script type="text/javascript" src="js/jquery.jplayer.min.js"></script>
<script type="text/javascript" src="js/jplayer.playlist.min.js"></script>
<script type="text/javascript">
//<![CDATA[
$(document).ready(function(){
	new jPlayerPlaylist({
		jPlayer: "#jquery_jplayer_1",
		cssSelectorAncestor: "#jp_container_1"
	}, [
		/* ORIGINAL PLAYLIST -- FOR REFERENCE */
		/*
		{
			title:"Hidden",
			artist:"Miaow",
			mp3:"http://www.jplayer.org/audio/mp3/Miaow-02-Hidden.mp3",
			oga:"http://www.jplayer.org/audio/ogg/Miaow-02-Hidden.ogg",
			poster: "http://www.jplayer.org/audio/poster/Miaow_640x360.png"
		},
		{
			title:"Big Buck Bunny Trailer",
			artist:"Blender Foundation",
			m4v:"http://www.jplayer.org/video/m4v/Big_Buck_Bunny_Trailer.m4v",
			ogv:"http://www.jplayer.org/video/ogv/Big_Buck_Bunny_Trailer.ogv",
			webmv: "http://www.jplayer.org/video/webm/Big_Buck_Bunny_Trailer.webm",
			poster:"http://www.jplayer.org/video/poster/Big_Buck_Bunny_Trailer_480x270.png"
		},
		*/
		<?php
		//set spacer variable for cleaner playlist layout
		$plspacer="\n\t\t\t";
		$counter=0;
		//get current full internet path and use for playlist -- addresses flash fallback bug for some video (FireFox)
		$curfullpath='http://'.$_SERVER['SERVER_NAME'].substr($_SERVER['PHP_SELF'],0,strrpos($_SERVER['PHP_SELF'],'/')).'/';
		//time grab playlist start
		$loadtime['playliststart']=microtime(true);
		foreach ($fileinfo as $value) {
			//modify 'show from' text if configuration variable set
			if($showfromtext=='true'){
				if( $_GET["name"]=='' AND $value["from"]!='root' ){
					$strippedrmd=str_replace(array('.', '/'), array('', ''), $relMusicDir);
					$showfrom='  ['.str_replace($strippedrmd.'&raquo;', '', $value['from']).']';
				}
				else
					$showfrom='<br />'.$relmusicdir; //explicitely state
			}
			else
				$showfrom='';
			//allow download if variable set
			if($allowdownload=='true'){
				//works for files with three character extensions only
				$dl=','.$plspacer.'free:true,'.$plspacer;
			}
			else{
				$dl=','.$plspacer;
			}
			//set file location
			$extcomma='';
			$fl='';
			//make $fl based on each $value[$extension][] item -- allows mysong.mp3 and mysong.ogg to be one line item // Thanks, jPlayer
			if (is_array($value["extension"])){
				foreach($value["extension"] as $extension => $throwaway){
					$fl.=$extcomma.str_replace($extensionsarray,$treatextensionsarray,$extension).':encodeURI("'.$curfullpath.substr($value[path],0,strrpos($value[path],".")).'.'.$extension.'")'; //configure arrays in config -- URIencode fixes Firefox issue with special char filenames
					$extcomma=','."\n\t\t\t";
				}
			}
			//use conventional method -- seems necessary to keep this in
			else
				$fl=str_replace($extensionsarray,$treatextensionsarray,substr($value[path],-3)).':encodeURI("'.$curfullpath.$value[path].'")'; //configure arrays in config
			//check for poster
			if( $playermode!='audio' AND isset($value['art']) ){
				$poster=','.$plspacer.'poster:"'.str_replace('?','%3F',$value['art']).'"';//fix from mm.com work
			}
			else if ( $playermode!='audio' AND !isset($value['art']) ){
				$poster=','.$plspacer.'poster:"'.$posterlocation.'"';
			}
			else{
				$poster='';
			}
			//if array is valid, start digging for information
			if (is_array($value)){
				if($useID3==TRUE){
					//prepare to display track number if configured and available
					if( $sortby=='track' AND isset($value['track']) ){
						//set track number for display
						if($leadingzero==true)
							//format with leading zero
							$track=str_pad((int) $value['track'],2,"0",STR_PAD_LEFT).' - '; //new way with padding
						else
							$track=$value['track'].' - ';
					}
					else
						$track='';
					//if artist AND title are empty, use filename
					if( (!isset($value['artist'])) AND (!isset($value['title'])) )
						//echo$comma.'{'.$plspacer.'title:"'.$value['filename'].'",'.$plspacer.'artist:"'.$showfrom.'"'.$dl.$fl.$poster.'}';
						echo$comma.'{'.$plspacer.'title:"'.$value['filename'].'<span class=\"jp-artist\">'.$showfrom.'</span>"'.$dl.$fl.$poster.'}';
					//otherwise, use artist and title correctly
					else if( (isset($value['filename'])) OR (isset($value['path'])) )
						echo$comma.'{'.$plspacer.'artist:"'.$value['artist'].$showfrom.'",'.$plspacer.'title:"'.$track.$value['title'].'"'.$dl.$fl.$poster.'}';
				}
				else 
					//assume filename only
					//echo$comma.'{'.$plspacer.'title:"'.$value['fn'].'",'.$plspacer.'artist:"'.$showfrom.'"'.$dl.$fl.$poster.'}'; //optimized below
					echo$comma.'{'.$plspacer.'title:"'.$value['filename'].'<span class=\"jp-artist\">'.$showfrom.'</span>"'.$dl.$fl.$poster.'}';
				$comma=','."\n\t\t";
			}
		}
		//time grab playlist end
		$loadtime['playlistend']=microtime(true);
		?>
	], {
		playlistOptions: {
		<?php
		//set autoplay
		if($autoplay=='true')echo'autoPlay: true';
		?>
		},
		swfPath: "js",
		<?php echo $supplied; ?>		
	});
});
//]]>
</script>
<script src="js/script.js"></script>
<!-- a helper script for validating the contact form and the booking form -->
<script type="text/javascript">                                 
// we will add our javascript code here
jQuery(document).ready(function($) {

	$("#ajax-contact-form").submit(function() {
		var str = $(this).serialize();

		$.ajax({
			type: "POST",
			url: "includes/contact-process.php",
			data: str,
			success: function(msg) {
    			// Message Sent? Show the 'Thank You' message and hide the form
    			if(msg == 'OK') {
    				result = '<div class="notification_ok">Your message has been sent. Thank you!</div>';
    				$("#fields").hide();
    			} else {
    				result = msg;
    			}
    			$('#note').html(result);
			}
		});
		return false;
	});
});
</script>
<script type="text/javascript"> 
jQuery(document).ready(function($) {

	$("#ajax-contact-form-booking").submit(function() {
		var str = $(this).serialize();

		$.ajax({
			type: "POST",
			url: "includes/contact-process-booking.php",
			data: str,
			success: function(msg) {
    			// Message Sent? Show the 'Thank You' message and hide the form
    			if(msg == 'OK') {
    				result = '<div class="notification_ok">Your message has been sent. Thank you!</div>';
    				$("#fields-booking").hide();
    			} else {
    				result = msg;
    			}
    			$('#note-booking').html(result);
			}
		});
		return false;
	});
});
</script>
<!-- Facebook like script -->
<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/en_EN/all.js#xfbml=1";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
    <!-- Asynchronous Google Analytics snippet. Change UA-XXXXX-X to be your site's ID. -->
    <script>
    var _gaq=[['_setAccount','UA-XXXXX-X'],['_trackPageview']];
    (function(d,t){var g=d.createElement(t),s=d.getElementsByTagName(t)[0];
    g.src=('https:'==location.protocol?'//ssl':'//www')+'.google-analytics.com/ga.js';
    s.parentNode.insertBefore(g,s)}(document,'script'));
    </script>
</body>
</html>
<?php
//time grab page/html end
$loadtime['pageend']=microtime(true);
$loadtime['htmlend']=microtime(true);
//debug -- dump page load information
if($debug==TRUE){
	echo 'Page Load Time: '.($loadtime['pageend']-$loadtime['pagestart'])
		.'<br />'
		.'Function Process Time: '.($loadtime['functionend']-$loadtime['functionstart'])
		.'<br />'
		.'Playlist Create Time: '.($loadtime['playlistend']-$loadtime['playliststart'])
		.'<br />'
		.'HTML Load Time: '.($loadtime['htmlend']-$loadtime['htmlstart']);
		//var_dump($loadtime);
}
/*
* =====================
* FUNctions
* =====================
*/
/*
* array subvalue sort -- from: http://www.firsttube.com/read/sorting-a-multi-dimensional-array-with-php/
*
* this function lets me sort the multidimensional array containing song/artist information by the file modified time, a subvalue
*/
function subval_sort($a,$subkey,$order='a') {
	foreach($a as $k=>$v) {
		$b[$k] = strtolower($v[$subkey]);
	}
	if($order=='a'){
		asort($b);
	}
	else if($order=='d'){
		arsort($b);
	}
	foreach($b as $key=>$val) {
		$c[] = $a[$key];
	}
	return $c;
}
/*
* function written to clean up my messy code (too many slashes ... display '/' as '&raquo' (>>) for user friendliness )
*/
function clean($dirty, $type='general', $debug=false){
	//debug
	if($debug==true)
		echo'<br />value before clean: '.$dirty.' (first character: '.substr($dirty, 0, 1).')';
	/*
	* General cleaning -- remove '/' at front and end
	*/
	if(substr($dirty, 0, 1)=='/'){
		//echo'<br />found leading /';
		$dirty=substr($dirty, 1, strlen($dirty)-1);
	}
	if(substr($dirty, -1)=='/')
		$dirty=substr($dirty, 0, strlen($dirty)-1);
	//prepare the subfolder display information by type
	if($type=='link')
		$dirty=str_replace(array('//','&raquo;'), array('/', '/'), $dirty);	
	else if($type=='display')
		$dirty=str_replace(array('///','//','/'), array('&raquo;','&raquo;', '&raquo;'), $dirty);	
	else
		$dirty=str_replace('&raquo;', '/', $dirty);
	if($debug==true)echo' | after clean: '.$dirty;
	//return
	return $dirty;
}
function makelink($linkme, $debug=false){
	$link=str_replace('&raquo;', '/', $linkme);
	$link=str_replace('//', '/', $link);
	return $link;
}
function recursiveGetSongs($directory, $fileinfo, $useID3, $getID3, $parent=null, $debug, $filtered=null, $playermode='audio', $usealbumart=true){
	/*
	 * configure function here:
	 *
	 * _usage_
	 *	> the disallowed array should include any folders or files you don't want displayed
	 *	> the allowedfiletypes array should include any file extentions you want to play
	 */
	$disallowed=array('..', '.', 'js', 'skin', 'z_jquery-ui-1.7.1.custom', 'getid3');
	//album art configuration
	$albumartdefaultname='album_art.jpg'; //set as text string ('front.jpg', 'album_art.jpg') which will be displayed as art
	//for audio player mode, only look for defined audio files
	if($playermode=='audio'){
		$allowedfiletypes=array('mp3', 'ogg', 'oga', 'm4a', 'wma', 'webma');
	}
	//set file types for av or video player
	else{
		$allowedfiletypes=array('mp3', 'ogg', 'oga', 'm4a', 'wma', 'webma', 'm4v', 'ogv', 'mp4', 'webmv', 'webm');
	}
	/* END CONFIGURATION */
	if($filtered!=null){
		$disallowed=array_merge((array)$filtered, (array)$disallowed);
	}
	//simple error fix
	if($directory=='./')
		$directory='.';
	//debug
	if ($debug==true)echo'Dir to open: '.$directory;
	//open directory
	$dir = opendir($directory); 
	while ($read = readdir($dir)){
		//if ( !in_array($read, $disallowed) AND ( $filter!=null AND in_array($read, $filter) ) )
		if ( !in_array($read, $disallowed) )
		{ 
			if($debug==true)echo $read.'<br />';
			//if is not dir, handle file
			if ( !is_dir($directory.'/'.$read) ){
				if($debug==true)echo '^^ not dir | dir: '.$directory.'<br />';
				//if( in_array(substr($read, -3, 3), $allowedfiletypes) ){ //THIS SHOULD BE REVISED -- USE STRPOS LAST '.' in string to determine substring length
				if( in_array(substr($read, strrpos($read,'.')+1), $allowedfiletypes) ){ //ATTEMPTED REVISION
					//strip extension from read // allows multiple file variants for single playlist item (allowing oga AND mp3, etc.)
					$readext=substr($read, strrpos($read,'.'));
					$read=substr($read, 0, strrpos($read,'.'));
					//if $read exists as a key, add $readext to extensions array //removed this as indicated below [and bypass further testing]
					if(array_key_exists($read,$fileinfo)){
						$fileinfo[$read]['extension'][substr($readext,1)]=1;
					}
					//else if($useID3==TRUE){ //this was used...it prevented secondary files from contributing to file information / album art
					if($useID3==TRUE){
						//fetch ID3 info
						$FullFileName = realpath($directory.'/'.$read.$readext);
						if($debug==TRUE)echo'<br />FFN &raquo; '.$FullFileName;
						$ThisFileInfo = $getID3->analyze($FullFileName);
						getid3_lib::CopyTagsToComments($ThisFileInfo);
						//store ID3 info
						$fileinfo[$read]['artist']=$ThisFileInfo['comments_html']['artist'][0];
						$fileinfo[$read]['album']=$ThisFileInfo['comments_html']['album'][0];
						$fileinfo[$read]['title']=$ThisFileInfo['comments_html']['title'][0];
						$fileinfo[$read]['track']=$ThisFileInfo['comments_html']['track'][0];
						$fileinfo[$read]['filename']=$ThisFileInfo['filename'];
						$fileinfo[$read]['filenamealt']=$read.$readext; //alternate filename for hebrew problem
						$fileinfo[$read]['modified']=date ("YmdHis", filemtime($directory.'/'.$read.$readext));
						$fileinfo[$read]['extension'][substr($readext,1)]=1;
						//echo htmlspecialchars($fileinfo[$read]['filename']).' | '.$read.'<br />';
						/*
						 * ALBUM ART //ID3
						 */
						if($usealbumart==true){
							//set alternate (custom) naming convention for album art
							$albumartaltname=$fileinfo[$read]['artist'].'_'.$fileinfo[$read]['album'].'.jpg'; //allows alternate naming convention
							//echo $directory.'/'.html_entity_decode($albumartaltname).'<br />';
							//look for album art in media directory based on default name
							if($usealbumart==true AND file_exists($directory.'/'.$albumartdefaultname)){
								$fileinfo[$read]['art']=$directory.'/'.$albumartdefaultname;
								if($debug==true)
									echo'album art already exists for '.$fileinfo[$read]['filename'].' @ '.$fileinfo[$read]['art'].'<br />';
							}
							//look for album art in media directory based on alternate name (allow two folder-based locations/naming options)
							else if($usealbumart==true AND file_exists($directory.'/'.$albumartaltname)){
								$fileinfo[$read]['art']=$directory.'/'.$albumartaltname;
								if($debug==true)
									echo'album art already exists for '.$fileinfo[$read]['filename'].' @ '.$fileinfo[$read]['art'].'<br />';
							}
							//look for previously extracted album art based on naming conventions below
							//if embedded art exists -- extract it
							else if($usealbumart==true AND isset($ThisFileInfo['comments']['picture'][0]['data'])){
								//determine filename -- if album name exists, use artist_album -- else use filename
								if( isset($fileinfo[$read]['album']) AND isset($fileinfo[$read]['artist']) )
									$fn='graphics/artstore/'.$fileinfo[$read]['artist'].'_'.$fileinfo[$read]['album'].'.jpg';
								else
									$fn='graphics/artstore/'.$fileinfo[$read]['filename'].'.jpg';
								//if fn doesn't exist, create it
								if (!file_exists($fn)){
									//create image
									$img=imagecreatefromstring($ThisFileInfo['comments']['picture'][0]['data']);
									imagejpeg($img, $fn);
									if($debug==true)
										echo'file created: <img src="'.$fn.'" />';
									imagedestroy($img);
								}
								else if($debug==true){
									//file already exists, pass fn back for poster usage
									echo'file exists: <img src="'.$fn.'" />';
								}
								//set fn as array item
								$fileinfo[$read]['art']=$fn;
							}
						}
						/*
						 *	End Album Art check and extraction
						 */
						if($debug==true)
							echo "<br />$read was last modified: " . date ("YmdHis", filemtime($directory.'/'.$read.$readext));
						$fileinfo[$read]['path']=$directory.'/'.$read.$readext;
						if($debug==true)echo'<span style="margin-left: 10px;">path:'.$fileinfo[$read]['path'].' > fn: '.$fileinfo[$read]['filename'].'</span><br /><br />';
						if($parent!=null)
							$fileinfo[$read]['from']=str_replace(array('./', '//', '/'), array('', '&raquo;', '&raquo;'), $directory); // was =$parent
						else
							$fileinfo[$read]['from']='root'; //testing this
						if($debug==true){
							echo'<br />'.$fileinfo[$read]['from'].'<br />';
							echo$ThisFileInfo['filename'].' '.$fileinfo[$read]['path'].'<br />'; 
						}
						//capture file extension
						$fileinfo['extensions'][substr($readext,1)]=1;
					} //end ID3 file handling
					else{
						//store file information
						$fileinfo[$read]['path']=$directory.'/'.$read.$readext;
						//$fileinfo[$read]['fn']=$read.$readext;
						$fileinfo[$read]['filename']=$read.$readext;
						if($parent!=null)
							$fileinfo[$read]['from']=str_replace(array('./', '//', '/'), array('', '&raquo;', '&raquo;'), $directory);
						$fileinfo[$read]['modified']=date ("YmdHis", filemtime($directory.'/'.$read.$readext));
						$fileinfo[$read]['extension'][substr($readext,1)]=1;
						//capture file extensions
						$fileinfo['extensions'][substr($readext,1)]=1;
						/*
						 * ALBUM ART TESTING //NON ID3
						 */
						if($usealbumart==true){
							$albumartaltname=$read.'.jpg'; //allows alternate naming convention
							//look for album art in media directory based on default name
							if($usealbumart==true AND file_exists($directory.'/'.$albumartdefaultname)){
								$fileinfo[$read]['art']=$directory.'/'.$albumartdefaultname;
								if($debug==true)
									echo'album art already exists for '.$fileinfo[$read]['filename'].' @ '.$fileinfo[$read]['art'].'<br />';
							}
							//look for album art in media directory based on alternate name (allow two folder-based locations/naming options)
							else if($usealbumart==true AND file_exists($directory.'/'.$albumartaltname)){
								$fileinfo[$read]['art']=$directory.'/'.$albumartaltname;
								if($debug==true)
									echo'album art already exists for '.$fileinfo[$read]['filename'].' @ '.$fileinfo[$read]['art'].'<br />';
							}
						} //END ALBUM ART HANDLING
					} //END non ID3 file handling
					//inc counters (total and per folder)
					$fileinfo['count']=$fileinfo['count']+1; // had ++ and it didn't work
					$fileinfo[$fileinfo['lfolder'].'_count']=$fileinfo[$fileinfo['lfolder'].'_count']+1;
				} //end handle allowed filetypes
				else
					;//do nothing -- not allowed filetype
			}
			//else, must be a folder (as determined above), recurse folder
			else{
				//debug
				if($debug==true)echo '^^ DIR<br />';
				//capture subfolders which are used for generated filters / folder links
				if($parent!='')$fileinfo['folders'][]=$parent.'&raquo;'.$read;
				else $fileinfo['folders'][]=$read;
				$fileinfo['lfolder']=end($fileinfo['folders']); //store last item noted as a 'folder' for counting inner items
				//debug
				//echo'<br />lfolder='.$lfolder.'<br />';
				$fileinfo['folderpaths'][]=$directory.'/';
				$fileinfo=recursiveGetSongs($directory.'/'.$read, $fileinfo, $useID3, $getID3, $parent.'/'.$read, $debug, $filtered, $playermode, $usealbumart);
			}
		}
	}
	closedir($dir); 
	return $fileinfo;
}
?>