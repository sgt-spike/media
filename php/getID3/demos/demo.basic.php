<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at https://github.com/JamesHeinrich/getID3       //
//            or https://www.getid3.org                        //
//            or http://getid3.sourceforge.net                 //
//                                                             //
// /demo/demo.basic.php - part of getID3()                     //
// Sample script showing most basic use of getID3()            //
//  see readme.txt for more details                            //
//                                                            ///
/////////////////////////////////////////////////////////////////

//die('For security reasons, this demo has been disabled. It can be enabled by removing line '.__LINE__.' in demos/'.basename(__FILE__));

// include getID3() library (can be in a different directory if full path is specified)
require_once('../getid3/getid3.php');

// Initialize getID3 engine
$getID3 = new getID3;
$filename = '../../../../../movies/10 Cloverfield Lane/10 Cloverfield Lane.m4v';

// Analyze file and store returned data in $ThisFileInfo
$ThisFileInfo = $getID3->analyze($filename);

/*
 Optional: copies data from all subarrays of [tags] into [comments] so
 metadata is all available in one location for all tag formats
 metainformation is always available under [tags] even if this is not called
*/
$getID3->CopyTagsToComments($ThisFileInfo);

/*
 Output desired information in whatever format you want
 Note: all entries in [comments] or [tags] are arrays of strings
 See structure.txt for information on what information is available where
 or check out the output of /demos/demo.browse.php for a particular file
 to see the full detail of what information is returned where in the array
 Note: all array keys may not always exist, you may want to check with isset()
 or empty() before deciding what to output
*/

echo $ThisFileInfo['comments_html']['artist'][0] . '<br>'; // artist from any/all available tag formats
echo $ThisFileInfo['comments_html']['title'][0] . '<br>';
echo $ThisFileInfo['comments_html']['creation_date'][0] . '<br>';
echo $ThisFileInfo['comments_html']['description'][0] . '<br>';
echo $ThisFileInfo['comments_html']['description_long'][0] . '<br>';

// echo $ThisFileInfo['tags']['id3v2']['title'][0];  // title from ID3v2
// echo $ThisFileInfo['audio']['bitrate'];           // audio bitrate
// echo $ThisFileInfo['playtime_string'];            // playtime in minutes:seconds, formatted string
echo $ThisFileInfo['comments']['title'][0] . '<br>';
echo $ThisFileInfo['comments']['creation_date'][0] . '<br>';
echo $ThisFileInfo['comments']['description'][0] . '<br>';
echo $ThisFileInfo['comments']['description_long'][0] . '<br>';
// echo $ThisFileInfo['comments']['picture'][0]['data'][1];
// echo $ThisFileInfo['comments']['picture'][0]['data'][2];
echo '<img src=\"data:image/jpeg;base64,'.base64_encode($ThisFileInfo['comments']['picture'][0]['data'][0]).'" width=\"361\" height=\"500\">';
/* if you want to see all the tag data (from all tag formats), uncomment this line: */
//echo '<pre>'.htmlentities(print_r($ThisFileInfo['comments'], true), ENT_SUBSTITUTE).'</pre>';

/* if you want to see ALL the output, uncomment this line: */
//echo '<pre>'.htmlentities(print_r($ThisFileInfo, true), ENT_SUBSTITUTE).'</pre>';


