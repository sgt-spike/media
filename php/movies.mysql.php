<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at https://github.com/JamesHeinrich/getID3       //
//            or https://www.getid3.org                        //
//            or http://getid3.sourceforge.net                 //
//                                                             //
// /demo/demo.mysqli.php - part of getID3()                     //
// Sample script for recursively scanning directories and      //
// storing the results in a database                           //
//  see readme.txt for more details                            //
//                                                            ///
/////////////////////////////////////////////////////////////////

//die('Due to a security issue, this demo has been disabled. It can be enabled by removing line 16 in demos/demo.mysqli.php');
// ini_set('display_errors', 1);
// error_reporting(E_ALL);
ini_set('memory_limit', '512M');


// OPTIONS:
$getid3_demo_mysqli_encoding = 'ISO-8859-1';
$getid3_demo_mysqli_md5_data = false;        // All data hashes are by far the slowest part of scanning
$getid3_demo_mysqli_md5_file = false;

define('GETID3_DB_HOST',  '10.20.30.10');
//define('GETID3_DB_USER',  'moviedb_admin');
define('GETID3_DB_USER',  'btchriss');
//define('GETID3_DB_PASS',  'Bac@@@2030');
define('GETID3_DB_PASS',  'CRfc7TyqeChBdECa');
define('GETID3_DB_DB',    'moviesdb');
define('GETID3_DB_TABLE', 'files');
define('RESCAN_PATH', '/config/www/movies/');
define('CHAR_SET', 'utf8');

$link = mysqli_connect(GETID3_DB_HOST, GETID3_DB_USER, GETID3_DB_PASS, GETID3_DB_DB);
mysqli_set_charset($link, CHAR_SET);

// CREATE DATABASE `getid3`;

ob_start();
if (!$link) {
//if (!mysqlii_connect(GETID3_DB_HOST, GETID3_DB_USER, GETID3_DB_PASS)) {
	$errormessage = ob_get_contents();
	ob_end_clean();
	die('Could not connect to mysqli host: <blockquote style="background-color: #FF9933; padding: 10px;">'.mysqlii_error().'</blockquote>');
}
if (!mysqli_select_db($link, GETID3_DB_DB)) {
//if (!mysqlii_select_db(GETID3_DB_DB)) {
	$errormessage = ob_get_contents();
	ob_end_clean();
	die('Could not select database: <blockquote style="background-color: #FF9933; padding: 10px;">'.mysqlii_error().'</blockquote>');
}
ob_end_clean();

$getid3PHP_filename = realpath('../php/getID3/getid3/getid3.php');
if (!file_exists($getid3PHP_filename) || !include_once($getid3PHP_filename)) {
	die('Cannot open '.$getid3PHP_filename);
}
// Initialize getID3 engine
$getID3 = new getID3;
$getID3->setOption(array(
	'option_md5_data' => $getid3_demo_mysqli_md5_data,
	'encoding'        => $getid3_demo_mysqli_encoding,
));


function RemoveAccents($string) {
	// Revised version by markstewardØhotmail*com
	return strtr(strtr($string, 'ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÑÒÓÔÕÖØÙÚÛÜÝàáâãäåçèéêëìíîïñòóôõöøùúûüýÿ', 
										 'SZszYAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy'), 
										 array('Þ' => 'TH', 'þ' => 'th', 'Ð' => 'DH', 'ð' => 'dh', 'ß' => 'ss', '' => 'OE', '' => 'oe', 'Æ' => 'AE', 'æ' => 'ae', 'µ' => 'u'));
}

function BitrateColor($bitrate, $BitrateMaxScale=768) {
	// $BitrateMaxScale is bitrate of maximum-quality color (bright green)
	// below this is gradient, above is solid green

	$bitrate *= (256 / $BitrateMaxScale); // scale from 1-[768]kbps to 1-256
	$bitrate = round(min(max($bitrate, 1), 256));
	$bitrate--;    // scale from 1-256kbps to 0-255kbps

	$Rcomponent = max(255 - ($bitrate * 2), 0);
	$Gcomponent = max(($bitrate * 2) - 255, 0);
	if ($bitrate > 127) {
		$Bcomponent = max((255 - $bitrate) * 2, 0);
	} else {
		$Bcomponent = max($bitrate * 2, 0);
	}
	return str_pad(dechex($Rcomponent), 2, '0', STR_PAD_LEFT).str_pad(dechex($Gcomponent), 2, '0', STR_PAD_LEFT).str_pad(dechex($Bcomponent), 2, '0', STR_PAD_LEFT);
}

function BitrateText($bitrate, $decimals=0) {
	return '<span style="color: #'.BitrateColor($bitrate).'">'.number_format($bitrate, $decimals).' kbps</span>';
}

function fileextension($filename, $numextensions=1) {
	if (strstr($filename, '.')) {
		$reversedfilename = strrev($filename);
		$offset = 0;
		for ($i = 0; $i < $numextensions; $i++) {
			$offset = strpos($reversedfilename, '.', $offset + 1);
			if ($offset === false) {
				return '';
			}
		}
		return strrev(substr($reversedfilename, 0, $offset));
	}
	return '';
}

// function RenameFileFromTo($from, $to, &$results) {
// 	global $link;
// 	$success = true;
// 	if ($from === $to) {
// 		$results = '<span style="color: #FF0000;"><b>Source and Destination filenames identical</b><br>FAILED to rename';
// 	} elseif (!file_exists($from)) {
// 		$results = '<span style="color: #FF0000;"><b>Source file does not exist</b><br>FAILED to rename';
// 	} elseif (file_exists($to) && (strtolower($from) !== strtolower($to))) {
// 		$results = '<span style="color: #FF0000;"><b>Destination file already exists</b><br>FAILED to rename';
// 	} else {
// 		ob_start();
// 		if (rename($from, $to)) {
// 			ob_end_clean();
// 			$SQLquery  = 'DELETE FROM `'.mysqli_real_escape_string($link, GETID3_DB_TABLE).'`';
// 			$SQLquery .= ' WHERE (`filename` = "'.mysqli_real_escape_string($link, $from).'")';
// 			mysqli_query_safe($SQLquery);
// 			$results = '<span style="color: #008000;">Successfully renamed';
// 		} else {
// 			$errormessage = ob_get_contents();
// 			ob_end_clean();
// 			$results = '<br><span style="color: #FF0000;">FAILED to rename';
// 			$success = false;
// 		}
// 	}
// 	$results .= ' from:<br><i>'.$from.'</i><br>to:<br><i>'.$to.'</i></span><hr>';
// 	return $success;
// }

// if (!empty($_REQUEST['renamefilefrom']) && !empty($_REQUEST['renamefileto'])) {

// 	$results = '';
// 	RenameFileFromTo($_REQUEST['renamefilefrom'], $_REQUEST['renamefileto'], $results);
// 	echo $results;
// 	exit;

// } elseif (!empty($_REQUEST['m3ufilename'])) {

// 	header('Content-type: audio/x-mpegurl');
// 	echo '#EXTM3U'."\n";
// 	echo WindowsShareSlashTranslate($_REQUEST['m3ufilename'])."\n";
// 	exit;

// } elseif (!isset($_REQUEST['m3u']) && !isset($_REQUEST['m3uartist']) && !isset($_REQUEST['m3utitle'])) {

// 	echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"  "http://www.w3.org/TR/html4/loose.dtd">';
// 	echo '<html><head><title>getID3() demo - /demo/mysqli.php</title><style>BODY, TD, TH { font-family: sans-serif; font-size: 10pt; } A { text-decoration: none; } A:hover { text-decoration: underline; } A:visited { font-style: italic; }</style></head><body>';

// }

/* Windows servers only */
// function WindowsShareSlashTranslate($filename) {
// 	if (substr($filename, 0, 2) == '//') {
// 		return str_replace('/', '\\', $filename);
// 	}
// 	return $filename;
// }

function mysqli_query_safe($SQLquery) {
	global $link;
	static $TimeSpentQuerying = 0;
	if ($SQLquery === null) {
		return $TimeSpentQuerying;
	}
	$starttime = microtime(true);
	$result = mysqli_query($link, $SQLquery);
	$TimeSpentQuerying += (microtime(true) - $starttime);
	if (mysqli_error($link)) {
		die('<div style="color: red; padding: 10px; margin: 10px; border: 3px red ridge;"><div style="font-weight: bold;">SQL error:</div><div style="color: blue; padding: 10px;">'.htmlentities(mysqli_error($link)).'</div><hr size="1"><pre>'.htmlentities($SQLquery).'</pre></div>');
	}
	return $result;
}

function mysqli_table_exists($tablename) {
	global $link;
	return (bool) mysqli_query($link, 'DESCRIBE '.$tablename);
}

function AcceptableExtensions($fileformat, $audio_dataformat='', $video_dataformat='') {
	static $AcceptableExtensionsAudio = array();
	if (empty($AcceptableExtensionsAudio)) {
		$AcceptableExtensionsAudio['mp3']['mp3']  = array('mp3');
		$AcceptableExtensionsAudio['mp2']['mp2']  = array('mp2');
		$AcceptableExtensionsAudio['mp1']['mp1']  = array('mp1');
		$AcceptableExtensionsAudio['asf']['asf']  = array('asf');
		$AcceptableExtensionsAudio['asf']['wma']  = array('wma');
		$AcceptableExtensionsAudio['riff']['mp3'] = array('wav');
		$AcceptableExtensionsAudio['riff']['wav'] = array('wav');
	}
	static $AcceptableExtensionsVideo = array();
	if (empty($AcceptableExtensionsVideo)) {
		$AcceptableExtensionsVideo['mp3']['mp3']  = array('mp3');
		$AcceptableExtensionsVideo['mp2']['mp2']  = array('mp2');
		$AcceptableExtensionsVideo['mp1']['mp1']  = array('mp1');
		$AcceptableExtensionsVideo['asf']['asf']  = array('asf');
		$AcceptableExtensionsVideo['asf']['wmv']  = array('wmv');
		$AcceptableExtensionsVideo['gif']['gif']  = array('gif');
		$AcceptableExtensionsVideo['jpg']['jpg']  = array('jpg');
		$AcceptableExtensionsVideo['png']['png']  = array('png');
		$AcceptableExtensionsVideo['bmp']['bmp']  = array('bmp');
	}
	if (!empty($video_dataformat)) {
		return (isset($AcceptableExtensionsVideo[$fileformat][$video_dataformat]) ? $AcceptableExtensionsVideo[$fileformat][$video_dataformat] : array());
	} else {
		return (isset($AcceptableExtensionsAudio[$fileformat][$audio_dataformat]) ? $AcceptableExtensionsAudio[$fileformat][$audio_dataformat] : array());
	}
}

/* If initial scan option, this drops files table from database */
if (!empty($_REQUEST['scan'])) {
	if (mysqli_table_exists(GETID3_DB_TABLE)) {
		$SQLquery  = 'DROP TABLE `'.mysqli_real_escape_string($link, GETID3_DB_TABLE).'`';
		mysqli_query_safe($SQLquery);
	}
}
/* This creates the files table in the database */
if (!mysqli_table_exists(GETID3_DB_TABLE)) {
	$SQLquery  = "CREATE TABLE `".mysqli_real_escape_string($link, GETID3_DB_TABLE)."` (";
	$SQLquery .= " `ID` int(11) unsigned NOT NULL auto_increment,";
	$SQLquery .= " `filename` text NOT NULL,";
	$SQLquery .= " `fileformat` varchar(255) NOT NULL default '',";
	$SQLquery .= " `artist` text NOT NULL default '',";
	$SQLquery .= " `title` varchar(255) NOT NULL default '',";
	$SQLquery .= " `creation_date` varchar(255) NOT NULL default '',";
	$SQLquery .= " `genre` varchar(255) NOT NULL default '',";
	$SQLquery .= " `description` text NOT NULL default '',";
	$SQLquery .= " `description_long` text NOT NULL default '',";
	$SQLquery .= " `cover` mediumblob NULL default '',";
	$SQLquery .= " `media` int(1) NOT NULL default 3,";
	$SQLquery .= " PRIMARY KEY (`ID`)";
	$SQLquery .= ")";
	mysqli_query_safe($SQLquery);
}
/* ***** This section isn't used yet ***** */
$ExistingTableFields = array();
$result = mysqli_query_safe('DESCRIBE `'.mysqli_real_escape_string($link, GETID3_DB_TABLE).'`');
while ($row = mysqli_fetch_array($result)) {
	$ExistingTableFields[$row['Field']] = $row;
}
/* ***** ************************** ***** */

/* ******************************* This funciton isn't used ??? ************************************* */
function SynchronizeAllTags($filename, $synchronizefrom='all', $synchronizeto='A12', &$errors) {
	global $getID3;
	global $link;
	set_time_limit(300);

	$ThisFileInfo = $getID3->analyze($filename);
	$getID3->CopyTagsToComments($ThisFileInfo);

	if ($synchronizefrom == 'all') {
		$SourceArray = (!empty($ThisFileInfo['comments']) ? $ThisFileInfo['comments'] : array());
	} elseif (!empty($ThisFileInfo['tags'][$synchronizefrom])) {
		$SourceArray = (!empty($ThisFileInfo['tags'][$synchronizefrom]) ? $ThisFileInfo['tags'][$synchronizefrom] : array());
	} else {
		die('ERROR: $ThisFileInfo[tags]['.$synchronizefrom.'] does not exist');
	}

	$SQLquery  = 'DELETE FROM `'.mysqli_real_escape_string($link, GETID3_DB_TABLE).'`';
	$SQLquery .= ' WHERE (`filename` = "'.mysqli_real_escape_string($link, $filename).'")';
	mysqli_query_safe($SQLquery);


	$TagFormatsToWrite = array();
	if ((strpos($synchronizeto, '2') !== false) && ($synchronizefrom != 'id3v2')) {
		$TagFormatsToWrite[] = 'id3v2.3';
	}
	if ((strpos($synchronizeto, 'A') !== false) && ($synchronizefrom != 'ape')) {
		$TagFormatsToWrite[] = 'ape';
	}
	if ((strpos($synchronizeto, 'L') !== false) && ($synchronizefrom != 'lyrics3')) {
		$TagFormatsToWrite[] = 'lyrics3';
	}
	if ((strpos($synchronizeto, '1') !== false) && ($synchronizefrom != 'id3v1')) {
		$TagFormatsToWrite[] = 'id3v1';
	}

	getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'write.php', __FILE__, true);
	$tagwriter = new getid3_writetags;
	$tagwriter->filename       = $filename;
	$tagwriter->tagformats     = $TagFormatsToWrite;
	$tagwriter->overwrite_tags = true;
	$tagwriter->tag_encoding   = $getID3->encoding;
	$tagwriter->tag_data       = $SourceArray;

	if ($tagwriter->WriteTags()) {
		$errors = $tagwriter->errors;
		return true;
	}
	$errors = $tagwriter->errors;
	return false;
}
/* ******************************* ********************************* ************************************* */

$IgnoreNoTagFormats = array('', 'png', 'jpg', 'gif', 'bmp', 'swf', 'pdf', 'zip', 'rar', 'mid', 'mod', 'xm', 'it', 's3m', 'plexignore');

if (!empty($_REQUEST['scan']) || !empty($_REQUEST['newscan']) || !empty($_REQUEST['rescanerrors']) || !empty($_REQUEST['rescan'])) {//Start of scan

	$SQLquery  = 'DELETE from `'.mysqli_real_escape_string($link, GETID3_DB_TABLE).'`';
	$SQLquery .= ' WHERE (`fileformat` = "")';
	// Deletes any file from database where fileformat is null
	mysqli_query_safe($SQLquery);

	$FilesInDir = array();

	// Re-scan only files with Errors and/or Warnings option
	if (!empty($_REQUEST['rescanerrors'])) {
		
		echo '<a href="'.htmlentities($_SERVER['PHP_SELF']).'">abort</a><hr>';

		echo 'Re-scanning all media files already in database that had errors and/or warnings in last scan<hr>';

		$SQLquery  = 'SELECT `filename`';
		$SQLquery .= ' FROM `'.mysqli_real_escape_string($link, GETID3_DB_TABLE).'`';
		$SQLquery .= ' WHERE (`error` <> "")';
		$SQLquery .= ' OR (`warning` <> "")';
		$SQLquery .= ' ORDER BY `filename` ASC';

		$result = mysqli_query_safe($SQLquery);

		while ($row = mysqli_fetch_array($result)) {

			if (!file_exists($row['filename'])) {
				echo '<b>File missing: '.$row['filename'].'</b><br>';
				$SQLquery = 'DELETE FROM `'.mysqli_real_escape_string($link, GETID3_DB_TABLE).'`';
				$SQLquery .= ' WHERE (`filename` = "'.mysqli_real_escape_string($link, $row['filename']).'")';
				mysqli_query_safe($SQLquery);
			} else {
				$FilesInDir[] = $row['filename'];
			}

		}
	// Initial Scan and New Scan	
	} elseif (!empty($_REQUEST['scan']) || !empty($_REQUEST['newscan'])) {
		// **** Processing start here when top input is clicked
		echo '<a href="'.htmlentities($_SERVER['PHP_SELF']).'">abort</a><hr>';

		echo 'Scanning all media files in <b>'.str_replace('\\', '/', realpath(!empty($_REQUEST['scan']) ? $_REQUEST['scan'] : $_REQUEST['newscan'])).'</b> (and subdirectories)<hr>';
		/* ************** This section may need to move *************************** */
		// $SQLquery  = 'SELECT COUNT(*) AS `num`, `filename`';
		// $SQLquery .= ' FROM `'.mysqli_real_escape_string($link, GETID3_DB_TABLE).'`';
		// $SQLquery .= ' GROUP BY `filename`';
		// $SQLquery .= ' HAVING (`num` > 1)';
		// $SQLquery .= ' ORDER BY `num` DESC';

		// $result = mysqli_query_safe($SQLquery);

		// $DupesDeleted = 0;

		// while ($row = mysqli_fetch_array($result)) {
		// 	set_time_limit(300);
		// 	$SQLquery  = 'DELETE FROM `'.mysqli_real_escape_string($link, GETID3_DB_TABLE).'`';
		// 	$SQLquery .= ' WHERE `filename` LIKE "'.mysqli_real_escape_string($link, $row['filename']).'"';
		// 	mysqli_query_safe($SQLquery);
		// 	$DupesDeleted++;
		// }
		// if ($DupesDeleted > 0) {
		// 	echo 'Deleted <b>'.number_format($DupesDeleted).'</b> duplicate filenames<hr>';
		// }
		/* ************************************************************************** */
		// New Scan only
		if (!empty($_REQUEST['newscan'])) {
			$AlreadyInDatabase = array();
			set_time_limit(300);
			$SQLquery  = 'SELECT `filename`';
			$SQLquery .= ' FROM `'.mysqli_real_escape_string($link, GETID3_DB_TABLE).'`';
			$SQLquery .= ' ORDER BY `filename` ASC';
			$result = mysqli_query_safe($SQLquery);
			while ($row = mysqli_fetch_array($result)) {
				//$AlreadyInDatabase[] = strtolower($row['filename']);
				$AlreadyInDatabase[] = $row['filename'];
			}
		}
		// if (count($AlreadyInDatabase) > 0) {
		// 	foreach ($AlreadyInDatabase as $value) {
		// 		echo $value . ' is in the database<br>';
		// 	}
		// } else {
		// 	foreach ($AlreadyInDatabase as $value) {
		// 		echo $value . ' is not in the database<br>';
		// 	}
		// }
		
		$DirectoriesToScan  = array(!empty($_REQUEST['scan']) ? $_REQUEST['scan'] : $_REQUEST['newscan']);
		$DirectoriesScanned = array();
		// Puts all files in the directory into $FilesInDir array
		while (count($DirectoriesToScan) > 0) {
			foreach ($DirectoriesToScan as $DirectoryKey => $startingdir) {
				if ($dir = opendir($startingdir)) {
					set_time_limit(300);
					echo '<b>Starting Directory: '.str_replace('\\', '/', $startingdir).'</b><br>';
					flush();
					while (($file = readdir($dir)) !== false) {
						if (($file != '.') && ($file != '..')) {

							$RealPathName = realpath($startingdir.'/'.$file);
							// If $RealPathName is a directory
							if (is_dir($RealPathName)) {
								// If $RealPathName is not in $DirectoriesScanned and not in $DirectoriesToScan
								if (!in_array($RealPathName, $DirectoriesScanned) && !in_array($RealPathName, $DirectoriesToScan)) {
									// Add to $DirectoriesToScan array
									$DirectoriesToScan[] = $RealPathName;
								}
							// If $RealPathName is a file	
							} elseif (is_file($RealPathName)) {
								// If doing a New Scan
								if (!empty($_REQUEST['newscan'])) {
									$thisFile = substr($RealPathName, strrpos($RealPathName, chr(47)) + 1);
									if (!in_array($thisFile, $AlreadyInDatabase)) {
										$FilesInDir[] = $RealPathName;
									}
								// If doing an initial scan
								} elseif (!empty($_REQUEST['scan'])) {
									$FilesInDir[] = $RealPathName;
								}
							}
						}
					}
					closedir($dir);
				} else {
					echo '<div style="color: red;">Failed to open directory "<b>'.htmlentities($startingdir).'</b>"</div><br>';
				}
				$DirectoriesScanned[] = $startingdir;
				unset($DirectoriesToScan[$DirectoryKey]);
			}
		}
		echo '<i>List of files to scan complete (added '.number_format(count($FilesInDir)).' files to scan)</i><hr>';
		flush();
	} 

	// foreach ($FilesInDir as $value) {
	// 	echo $value. ' is not in database ($FilesInDir)<br>';
	// }

	$FilesInDir = array_unique($FilesInDir);
	sort($FilesInDir);

	$starttime = time();
	$rowcounter = 0;
	$totaltoprocess = count($FilesInDir);

	foreach ($FilesInDir as $filename) {
		set_time_limit(300);

		echo '<br>'.date('H:i:s').' ['.number_format(++$rowcounter).' / '.number_format($totaltoprocess).'] '.str_replace('\\', '/', $filename);

		$ThisFileInfo = $getID3->analyze($filename);
		$getID3->CopyTagsToComments($ThisFileInfo);

		if (file_exists($filename)) {
			$ThisFileInfo['file_modified_time'] = filemtime($filename);
			$ThisFileInfo['md5_file']           = ($getid3_demo_mysqli_md5_file ? md5_file($filename) : '');
		}

		if (empty($ThisFileInfo['fileformat'])) {

			echo ' (<span style="color: #990099;">unknown file type</span>)';

		} else {
			//  This will run on mp4s files
			if (!empty($ThisFileInfo['error'])) {
				echo ' (<span style="color: #FF0000;">errors</span>)';
			} elseif (!empty($ThisFileInfo['warning'])) {
				echo ' (<span style="color: #FF9999;">warnings</span>)';
			} else {
				echo ' (<span style="color: #009900;">OK</span>)';
			}

			$this_track_track = '';
			// This if statement won't run
			if (!empty($ThisFileInfo['comments']['track_number'])) {
				foreach ($ThisFileInfo['comments']['track_number'] as $key => $value) {
					if (strlen($value) > strlen($this_track_track)) {
						$this_track_track = str_pad($value, 2, '0', STR_PAD_LEFT);
					}
				}
				if (preg_match('#^([0-9]+)/([0-9]+)$#', $this_track_track, $matches)) {
					// change "1/5"->"01/05", "3/12"->"03/12", etc
					$this_track_track = str_pad($matches[1], 2, '0', STR_PAD_LEFT).'/'.str_pad($matches[2], 2, '0', STR_PAD_LEFT);
				}
			}

			$this_track_remix = '';
			$this_track_title = '';
			// This will run and set $this_track_title to the movie's title
			if (!empty($ThisFileInfo['comments']['title'])) {
				foreach ($ThisFileInfo['comments']['title'] as $possible_title) {
					if (strlen($possible_title) > strlen($this_track_title)) {
						$this_track_title = $possible_title;
					}
				}
			}

			$ParenthesesPairs = array('()', '[]', '{}');
			foreach ($ParenthesesPairs as $pair) {
				if (preg_match_all('/(.*) '.preg_quote($pair[0]).'(([^'.preg_quote($pair).']*[\- '.preg_quote($pair[0]).'])?(cut|dub|edit|version|live|reprise|[a-z]*mix))'.preg_quote($pair[1]).'/iU', $this_track_title, $matches)) {
					$this_track_title = $matches[1][0];
					$this_track_remix = implode("\t", $matches[2]);
				}
			}
			echo $this_track_remix;


			if (!empty($_REQUEST['rescanerrors'])) {
				// this won't run because I will not rescan for errors
				echo 'A rescanerrors request';
				$SQLquery  = 'UPDATE `'.mysqli_real_escape_string($link, GETID3_DB_TABLE).'` SET ';
				$SQLquery .= ', `filename` = "'.mysqli_real_escape_string($link, !empty($ThisFileInfo['filename']) ? $ThisFileInfo['filename'] : '').'"';
				$SQLquery .= ', `fileformat` = "'.mysqli_real_escape_string($link, !empty($ThisFileInfo['fileformat']) ? $ThisFileInfo['fileformat'] : '').'"';
				$SQLquery .= ', `artist` = "'.mysqli_real_escape_string($link, !empty($ThisFileInfo['comments_html']['artist']) ? implode("\t", $ThisFileInfo['comments_html']['artist']) : '').'"'; //artist
				$SQLquery .= ', `title` = "'.mysqli_real_escape_string($link, $this_track_title).'"'; //title
				$SQLquery .= ', `creation_date` = "'.mysqli_real_escape_string($link, !empty($ThisFileInfo['comments_html']['creation_date']) ? implode("\t", $ThisFileInfo['comments_html']['creation_date']) : '').'"'; //creation _date
				$SQLquery .= ', `genre` = "'.mysqli_real_escape_string($link, !empty($ThisFileInfo['comments_html']['genre']) ? implode("\t", $ThisFileInfo['comments_html']['genre']) : '').'"';
				$SQLquery .= ', `description` = "'.mysqli_real_escape_string($link, !empty($ThisFileInfo['comments_html']['description']) ? implode("\t", $ThisFileInfo['comments_html']['description']) : '').'"'; //description
				$SQLquery .= ', `description_long` = "'.mysqli_real_escape_string($link, !empty($ThisFileInfo['comments_html']['description_long']) ? implode("\t", $ThisFileInfo['comments_html']['description_long']) : '').'"'; //description_long
				$SQLquery .= ', `cover` = "'.mysqli_real_escape_string($link, !empty($ThisFileInfo['comments']['picture'][0]['data'][0]) ? implode("\t", $ThisFileInfo['comments']['picture'][0]['data']) : '').'"'; //cover art
				
				$SQLquery .= 'WHERE (`filename` = "'.mysqli_real_escape_string($link, isset($ThisFileInfo['filenamepath']) ? $ThisFileInfo['filenamepath'] : '').'")';

			} elseif (!empty($_REQUEST['scan']) || !empty($_REQUEST['newscan'])) {

				$SQLquery  = 'INSERT IGNORE INTO `'.mysqli_real_escape_string($link, GETID3_DB_TABLE).'` (`filename`, `fileformat`, `artist`, `title`, `creation_date`, `genre`, `description`, `description_long`, `cover` ) VALUES (';
				$SQLquery .= '"'.mysqli_real_escape_string($link, !empty($ThisFileInfo['filename']) ? $ThisFileInfo['filename'] : '').'", ';
				$SQLquery .= '"'.mysqli_real_escape_string($link, !empty($ThisFileInfo['fileformat']) ? $ThisFileInfo['fileformat'] : '').'", ';
				$SQLquery .= '"'.mysqli_real_escape_string($link, !empty($ThisFileInfo['comments_html']['artist']) ? implode("\t", $ThisFileInfo['comments_html']['artist']) : '').'", '; //artist
				$SQLquery .= '"'.mysqli_real_escape_string($link, $this_track_title).'", '; //title
				$SQLquery .= '"'.mysqli_real_escape_string($link, !empty($ThisFileInfo['comments_html']['creation_date']) ? implode("\t", $ThisFileInfo['comments_html']['creation_date']) : '').'", '; //creation_date
				$SQLquery .= '"'.mysqli_real_escape_string($link, !empty($ThisFileInfo['comments_html']['genre']) ? implode("\t", $ThisFileInfo['comments_html']['genre']) : '').'", '; //genre
				$SQLquery .= '"'.mysqli_real_escape_string($link, !empty($ThisFileInfo['comments_html']['description']) ? implode("\t", $ThisFileInfo['comments_html']['description']) : '').'", '; 
				$SQLquery .= '"'.mysqli_real_escape_string($link, !empty($ThisFileInfo['comments_html']['description_long']) ? implode("\t", $ThisFileInfo['comments_html']['description_long']) : '').'", ';
				$SQLquery .= '"'.mysqli_real_escape_string($link, !empty($ThisFileInfo['comments']['picture'][0]['data']) ? $ThisFileInfo['comments']['picture'][0]['data'] : '').'")'; //cover art
			}
			flush();
			
			mysqli_query_safe($SQLquery);
		}

	}

	$SQLquery = 'OPTIMIZE TABLE `'.mysqli_real_escape_string($link, GETID3_DB_TABLE).'`';
	mysqli_query_safe($SQLquery);

	echo '<hr>Done scanning!<hr>';
// End of Scan
} elseif (!empty($_REQUEST['filenamepattern'])) {
	// Filenames that don't match pattern (auto-fix) option  *** Not Sure
	$patterns['A'] = 'artist';
	$patterns['T'] = 'title';
	$patterns['M'] = 'album';
	$patterns['N'] = 'track';
	$patterns['G'] = 'genre';
	$patterns['R'] = 'remix';

	$FieldsToUse = explode(' ', wordwrap(preg_replace('#[^A-Z]#i', '', $_REQUEST['filenamepattern']), 1, ' ', 1));
	//$FieldsToUse = explode(' ', wordwrap($_REQUEST['filenamepattern'], 1, ' ', 1));
	foreach ($FieldsToUse as $FieldID) {
		$FieldNames[] = $patterns["$FieldID"];
	}

	$SQLquery  = 'SELECT `filename`, `fileformat`, '.implode(', ', $FieldNames);
	$SQLquery .= ' FROM `'.mysqli_real_escape_string($link, GETID3_DB_TABLE).'`';
	$SQLquery .= ' WHERE (`fileformat` NOT LIKE "'.implode('") AND (`fileformat` NOT LIKE "', $IgnoreNoTagFormats).'")';
	$SQLquery .= ' ORDER BY `filename` ASC';
	$result = mysqli_query_safe($SQLquery);
	echo 'Files that do not match naming pattern: (<a href="'.htmlentities($_SERVER['PHP_SELF'].'?filenamepattern='.urlencode($_REQUEST['filenamepattern']).'&autofix=1').'">auto-fix</a>)<br>';
	echo '<table border="1" cellspacing="0" cellpadding="3">';
	echo '<tr><th>view</th><th>Why</th><td><b>Actual filename</b><br>(click to play/edit file)</td><td><b>Correct filename (based on tags)</b>'.(empty($_REQUEST['autofix']) ? '<br>(click to rename file to this)' : '').'</td></tr>';
	$nonmatchingfilenames = 0;
	$Pattern = $_REQUEST['filenamepattern'];
	$PatternLength = strlen($Pattern);
	while ($row = mysqli_fetch_array($result)) {
		set_time_limit(300);
		$PatternFilename = '';
		for ($i = 0; $i < $PatternLength; $i++) {
			if (isset($patterns[$Pattern[$i]])) {
				$PatternFilename .= trim(strtr($row[$patterns[$Pattern[$i]]], ':\\*<>|', ';-¤«»¦'), ' ');
			} else {
				$PatternFilename .= $Pattern[$i];
			}
		}

		// Replace "~" with "-" if characters immediately before and after are both numbers,
		// "/" has been replaced with "~" above which is good for multi-song medley dividers,
		// but for things like 24/7, 7/8ths, etc it looks better if it's 24-7, 7-8ths, etc.
		$PatternFilename = preg_replace('#([ a-z]+)/([ a-z]+)#i', '\\1~\\2', $PatternFilename);
		$PatternFilename = str_replace('/',  '×',  $PatternFilename);

		$PatternFilename = str_replace('?',  '¿',  $PatternFilename);
		$PatternFilename = str_replace(' "', ' ', $PatternFilename);
		$PatternFilename = str_replace('("', '(', $PatternFilename);
		$PatternFilename = str_replace('-"', '-', $PatternFilename);
		$PatternFilename = str_replace('" ', ' ', $PatternFilename.' ');
		$PatternFilename = str_replace('"',  '',  $PatternFilename);
		$PatternFilename = str_replace('  ', ' ',  $PatternFilename);


		$ParenthesesPairs = array('()', '[]', '{}');
		foreach ($ParenthesesPairs as $pair) {

			// multiple remixes are stored tab-seperated in the database.
			// change "{2000 Version\tSomebody Remix}" into "{2000 Version} {Somebody Remix}"
			while (preg_match('#^(.*)'.preg_quote($pair[0]).'([^'.preg_quote($pair[1]).']*)('."\t".')([^'.preg_quote($pair[0]).']*)'.preg_quote($pair[1]).'#', $PatternFilename, $matches)) {
				$PatternFilename = $matches[1].$pair[0].$matches[2].$pair[1].' '.$pair[0].$matches[4].$pair[1];
			}

			// remove empty parenthesized pairs (probably where no track numbers, remix version, etc)
			$PatternFilename = preg_replace('#'.preg_quote($pair).'#', '', $PatternFilename);

			// "[01]  - Title With No Artist.mp3"  ==>  "[01] Title With No Artist.mp3"
			$PatternFilename = preg_replace('#'.preg_quote($pair[1]).' +\- #', $pair[1].' ', $PatternFilename);

		}

		// get rid of leading & trailing spaces if end items (artist or title for example) are missing
		$PatternFilename  = trim($PatternFilename, ' -');

		if (!$PatternFilename) {
			// no tags to create a filename from -- skip this file
			continue;
		}
		$PatternFilename .= '.'.$row['fileformat'];

		$ActualFilename = basename($row['filename']);
		if ($ActualFilename != $PatternFilename) {

			$NotMatchedReasons = '';
			if (strtolower($ActualFilename) === strtolower($PatternFilename)) {
				$NotMatchedReasons .= 'Aa ';
			} elseif (RemoveAccents($ActualFilename) === RemoveAccents($PatternFilename)) {
				$NotMatchedReasons .= 'ée ';
			}


			$actualExt  = '.'.fileextension($ActualFilename);
			$patternExt = '.'.fileextension($PatternFilename);
			$ActualFilenameNoExt  = (($actualExt  != '.') ? substr($ActualFilename,   0, 0 - strlen($actualExt))  : $ActualFilename);
			$PatternFilenameNoExt = (($patternExt != '.') ? substr($PatternFilename,  0, 0 - strlen($patternExt)) : $PatternFilename);

			if (strpos($PatternFilenameNoExt, $ActualFilenameNoExt) !== false) {
				$DifferenceBoldedName  = str_replace($ActualFilenameNoExt, '</b>'.$ActualFilenameNoExt.'<b>', $PatternFilenameNoExt);
			} else {
				$ShortestNameLength = min(strlen($ActualFilenameNoExt), strlen($PatternFilenameNoExt));
				for ($DifferenceOffset = 0; $DifferenceOffset < $ShortestNameLength; $DifferenceOffset++) {
					if ($ActualFilenameNoExt[$DifferenceOffset] !== $PatternFilenameNoExt[$DifferenceOffset]) {
						break;
					}
				}
				$DifferenceBoldedName  = '</b>'.substr($PatternFilenameNoExt, 0, $DifferenceOffset).'<b>'.substr($PatternFilenameNoExt, $DifferenceOffset);
			}
			$DifferenceBoldedName .= (($actualExt == $patternExt) ? '</b>'.$patternExt.'<b>' : $patternExt);


			echo '<tr>';
			echo '<td><a href="'.htmlentities('demo.browse.php?filename='.rawurlencode($row['filename'])).'">view</a></td>';
			echo '<td>&nbsp;'.$NotMatchedReasons.'</td>';
			echo '<td><a href="'.htmlentities($_SERVER['PHP_SELF'].'?m3ufilename='.urlencode($row['filename']), ENT_QUOTES).'">'.htmlentities($ActualFilename).'</a></td>';

			if (!empty($_REQUEST['autofix'])) {

				$results = '';
				if (RenameFileFromTo($row['filename'], dirname($row['filename']).'/'.$PatternFilename, $results)) {
					echo '<TD BGCOLOR="#009900">';
				} else {
					echo '<TD BGCOLOR="#FF0000">';
				}
				echo '<b>'.$DifferenceBoldedName.'</b></td>';


			} else {

				echo '<td><a href="'.htmlentities($_SERVER['PHP_SELF'].'?filenamepattern='.urlencode($_REQUEST['filenamepattern']).'&renamefilefrom='.urlencode($row['filename']).'&renamefileto='.urlencode(dirname($row['filename']).'/'.$PatternFilename)).'" title="'.htmlentities(basename($row['filename'])."\n".basename($PatternFilename), ENT_QUOTES).'" target="renamewindow">';
				echo '<b>'.$DifferenceBoldedName.'</b></a></td>';

			}
			echo '</tr>';

			$nonmatchingfilenames++;
		}
	}
	echo '</table><br>';
	echo 'Found '.number_format($nonmatchingfilenames).' files that do not match naming pattern<br>';


} elseif (!empty($_REQUEST['tagtypes'])) {
	// Tag Type Distribution  *** Not sure
	if (!isset($_REQUEST['m3u'])) { // Not Used
		$SQLquery  = 'SELECT `tags`, COUNT(*) AS `num` FROM `'.mysqli_real_escape_string($link, GETID3_DB_TABLE).'`';
		$SQLquery .= ' WHERE (`fileformat` NOT LIKE "'.implode('") AND (`fileformat` NOT LIKE "', $IgnoreNoTagFormats).'")';
		$SQLquery .= ' GROUP BY `tags`';
		$SQLquery .= ' ORDER BY `num` DESC';
		$result = mysqli_query_safe($SQLquery);
		echo 'Files with tags:<br>';
		echo '<table border="1" cellspacing="0" cellpadding="3">';
		echo '<tr><th>Tags</th><th>Count</th><th>M3U</th></tr>';
		while ($row = mysqli_fetch_array($result)) {
			echo '<tr>';
			echo '<td>'.$row['tags'].'</td>';
			echo '<td align="right"><a href="'.htmlentities($_SERVER['PHP_SELF'].'?tagtypes=1&showtagfiles='.($row['tags'] ? urlencode($row['tags']) : '')).'">'.number_format($row['num']).'</a></td>';
			echo '<td align="right"><a href="'.htmlentities($_SERVER['PHP_SELF'].'?tagtypes=1&showtagfiles='.($row['tags'] ? urlencode($row['tags']) : '').'&m3u=.m3u').'">m3u</a></td>';
			echo '</tr>';
		}
		echo '</table><hr>';
	}

	if (isset($_REQUEST['showtagfiles'])) {
		$SQLquery  = 'SELECT `filename`, `tags` FROM `'.mysqli_real_escape_string($link, GETID3_DB_TABLE).'`';
		$SQLquery .= ' WHERE (`tags` LIKE "'.mysqli_real_escape_string($link, $_REQUEST['showtagfiles']).'")';
		$SQLquery .= ' AND (`fileformat` NOT LIKE "'.implode('") AND (`fileformat` NOT LIKE "', $IgnoreNoTagFormats).'")';
		$SQLquery .= ' ORDER BY `filename` ASC';
		$result = mysqli_query_safe($SQLquery);

		if (!empty($_REQUEST['m3u'])) {

			header('Content-type: audio/x-mpegurl');
			echo '#EXTM3U'."\n";
			while ($row = mysqli_fetch_array($result)) {
				echo WindowsShareSlashTranslate($row['filename'])."\n";
			}
			exit;

		} else {

			echo '<table border="1" cellspacing="0" cellpadding="3">';
			while ($row = mysqli_fetch_array($result)) {
				echo '<tr>';
				echo '<td><a href="'.htmlentities('demo.browse.php?filename='.rawurlencode($row['filename']), ENT_QUOTES).'">'.htmlentities($row['filename']).'</a></td>';
				echo '<td>'.$row['tags'].'</td>';
				echo '</tr>';
			}
			echo '</table>';

		}
	}

} elseif (!empty($_REQUEST['fileextensions'])) {
	// File with incorrect file extension option  *** Not Sure
	$SQLquery  = 'SELECT `filename`, `fileformat`, `video_dataformat`';
	$SQLquery .= ' FROM `'.mysqli_real_escape_string($link, GETID3_DB_TABLE).'`';
	$SQLquery .= ' ORDER BY `filename` ASC';
	$result = mysqli_query_safe($SQLquery);
	$invalidextensionfiles = 0;
	$invalidextensionline  = '<table border="1" cellspacing="0" cellpadding="4">';
	$invalidextensionline .= '<tr><th>file</th><th>audio</th><th>video</th><th>tags</th><th>actual</th><th>correct</th><th>filename</th></tr>';
	while ($row = mysqli_fetch_array($result)) {
		set_time_limit(300);

		$acceptableextensions = AcceptableExtensions($row['fileformat'], $row['audio_dataformat'], $row['video_dataformat']);
		$actualextension      = strtolower(fileextension($row['filename']));
		if ($acceptableextensions && !in_array($actualextension, $acceptableextensions)) {
			$invalidextensionfiles++;

			$invalidextensionline .= '<tr>';
			$invalidextensionline .= '<td>'.$row['fileformat'].'</td>';
			$invalidextensionline .= '<td>'.$row['audio_dataformat'].'</td>';
			$invalidextensionline .= '<td>'.$row['video_dataformat'].'</td>';
			$invalidextensionline .= '<td>'.$row['tags'].'</td>';
			$invalidextensionline .= '<td>'.$actualextension.'</td>';
			$invalidextensionline .= '<td>'.implode('; ', $acceptableextensions).'</td>';
			$invalidextensionline .= '<td><a href="'.htmlentities('demo.browse.php?filename='.rawurlencode($row['filename']), ENT_QUOTES).'">'.htmlentities($row['filename']).'</a></td>';
			$invalidextensionline .= '</tr>';
		}
	}
	$invalidextensionline .= '</table><hr>';
	echo number_format($invalidextensionfiles).' files with incorrect filename extension:<br>';
	echo $invalidextensionline;

} elseif (isset($_REQUEST['genredistribution'])) {
	// Genre Distribution option ***
	if (!empty($_REQUEST['m3u'])) {

		header('Content-type: audio/x-mpegurl');
		echo '#EXTM3U'."\n";
		$SQLquery  = 'SELECT `filename`';
		$SQLquery .= ' FROM `'.mysqli_real_escape_string($link, GETID3_DB_TABLE).'`';
		$SQLquery .= ' WHERE (BINARY `genre` = "'.$_REQUEST['genredistribution'].'")';
		$SQLquery .= ' AND (`fileformat` NOT LIKE "'.implode('") AND (`fileformat` NOT LIKE "', $IgnoreNoTagFormats).'")';
		$SQLquery .= ' ORDER BY `filename` ASC';
		$result = mysqli_query_safe($SQLquery);
		while ($row = mysqli_fetch_array($result)) {
			echo WindowsShareSlashTranslate($row['filename'])."\n";
		}
		exit;

	} else {

		if ($_REQUEST['genredistribution'] == '%') {

			$SQLquery  = 'SELECT COUNT(*) AS `num`, `genre`';
			$SQLquery .= ' FROM `'.mysqli_real_escape_string($link, GETID3_DB_TABLE).'`';
			$SQLquery .= ' WHERE (`fileformat` NOT LIKE "'.implode('") AND (`fileformat` NOT LIKE "', $IgnoreNoTagFormats).'")';
			$SQLquery .= ' GROUP BY `genre`';
			$SQLquery .= ' ORDER BY `num` DESC';
			$result = mysqli_query_safe($SQLquery);
			getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'module.tag.id3v1.php', __FILE__, true);
			echo '<table border="1" cellspacing="0" cellpadding="4">';
			echo '<tr><th>Count</th><th>Genre</th><th>m3u</th></tr>';
			while ($row = mysqli_fetch_array($result)) {
				$GenreID = getid3_id3v1::LookupGenreID($row['genre']);
				if (is_numeric($GenreID)) {
					echo '<tr bgcolor="#00FF00;">';
				} else {
					echo '<tr bgcolor="#FF9999;">';
				}
				echo '<td><a href="'.htmlentities($_SERVER['PHP_SELF'].'?genredistribution='.urlencode($row['genre'])).'">'.number_format($row['num']).'</a></td>';
				echo '<td nowrap>'.str_replace("\t", '<br>', $row['genre']).'</td>';
				echo '<td><a href="'.htmlentities($_SERVER['PHP_SELF'].'?m3u=.m3u&genredistribution='.urlencode($row['genre'])).'">.m3u</a></td>';
				echo '</tr>';
			}
			echo '</table><hr>';

		} else {

			$SQLquery  = 'SELECT `filename`, `genre`';
			$SQLquery .= ' FROM `'.mysqli_real_escape_string($link, GETID3_DB_TABLE).'`';
			$SQLquery .= ' WHERE (`genre` LIKE "'.mysqli_real_escape_string($link, $_REQUEST['genredistribution']).'")';
			$SQLquery .= ' ORDER BY `filename` ASC';
			$result = mysqli_query_safe($SQLquery);
			echo '<a href="'.htmlentities($_SERVER['PHP_SELF'].'?genredistribution='.urlencode('%')).'">All Genres</a><br>';
			echo '<table border="1" cellspacing="0" cellpadding="4">';
			echo '<tr><th>Genre</th><th>m3u</th><th>Filename</th></tr>';
			while ($row = mysqli_fetch_array($result)) {
				echo '<tr>';
				echo '<TD NOWRAP>'.str_replace("\t", '<br>', $row['genre']).'</td>';
				echo '<td><a href="'.htmlentities($_SERVER['PHP_SELF'].'?m3ufilename='.urlencode($row['filename'])).'">m3u</a></td>';
				echo '<td><a href="'.htmlentities('demo.browse.php?filename='.rawurlencode($row['filename']), ENT_QUOTES).'">'.htmlentities($row['filename']).'</a></td>';
				echo '</tr>';
			}
			echo '</table><hr>';
		}
	}

} elseif (!empty($_REQUEST['errorswarnings'])) {
	// Files with Errors and/or Warnings  *** Not Sure
	$SQLquery  = 'SELECT `filename`, `error`, `warning`';
	$SQLquery .= ' FROM `'.mysqli_real_escape_string($link, GETID3_DB_TABLE).'`';
	$SQLquery .= ' WHERE (`error` <> "")';
	$SQLquery .= ' OR (`warning` <> "")';
	$SQLquery .= ' ORDER BY `filename` ASC';
	$result = mysqli_query_safe($SQLquery);

	if (!empty($_REQUEST['m3u'])) {

		header('Content-type: audio/x-mpegurl');
		echo '#EXTM3U'."\n";
		while ($row = mysqli_fetch_array($result)) {
			echo WindowsShareSlashTranslate($row['filename'])."\n";
		}
		exit;

	} else {

		echo number_format(mysqli_num_rows($result)).' files with errors or warnings:<br>';
		echo '(<a href="'.htmlentities($_SERVER['PHP_SELF'].'?errorswarnings=1&m3u=.m3u').'">.m3u version</a>)<br>';
		echo '<table border="1" cellspacing="0" cellpadding="4">';
		echo '<tr><th>Filename</th><th>Error</th><th>Warning</th></tr>';
		while ($row = mysqli_fetch_array($result)) {
			echo '<tr>';
			echo '<td><a href="'.htmlentities('demo.browse.php?filename='.rawurlencode($row['filename']), ENT_QUOTES).'">'.htmlentities($row['filename']).'</a></td>';
			echo '<td>'.(!empty($row['error'])   ? '<li>'.str_replace("\t", '<li>', htmlentities($row['error'])).'</li>' : '&nbsp;').'</td>';
			echo '<td>'.(!empty($row['warning']) ? '<li>'.str_replace("\t", '<li>', htmlentities($row['warning'])).'</li>' : '&nbsp;').'</td>';
			echo '</tr>';
		}
	}
	echo '</table><hr>';

} elseif (!empty($_REQUEST['fixid3v1padding'])) {
	// Fix ID3v1 invalid padding  *** Not Sure
	getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'write.id3v1.php', __FILE__, true);
	$id3v1_writer = new getid3_write_id3v1;

	$SQLquery  = 'SELECT `filename`, `error`, `warning`';
	$SQLquery .= ' FROM `'.mysqli_real_escape_string($link, GETID3_DB_TABLE).'`';
	$SQLquery .= ' WHERE (`fileformat` = "mp3")';
	$SQLquery .= ' AND (`warning` <> "")';
	$SQLquery .= ' ORDER BY `filename` ASC';
	$result = mysqli_query_safe($SQLquery);
	$totaltofix = mysqli_num_rows($result);
	$rowcounter = 0;
	while ($row = mysqli_fetch_array($result)) {
		set_time_limit(300);
		if (strpos($row['warning'], 'Some ID3v1 fields do not use NULL characters for padding') !== false) {
			set_time_limit(300);
			$id3v1_writer->filename = $row['filename'];
			echo ($id3v1_writer->FixID3v1Padding() ? '<span style="color: #009900;">fixed - ' : '<span style="color: #FF0000;">error - ');
		} else {
			echo '<span style="color: #0000FF;">No error? - ';
		}
		echo '['.++$rowcounter.' / '.$totaltofix.'] ';
		echo htmlentities($row['filename']).'</span><br>';
		flush();
	}
} 

function CleanUpFileName($filename) {
	$DirectoryName = dirname($filename);
	$FileExtension = fileextension(basename($filename));
	$BaseFilename  = basename($filename, '.'.$FileExtension);

	$BaseFilename = strtolower($BaseFilename);
	$BaseFilename = str_replace('_', ' ', $BaseFilename);
	//$BaseFilename = str_replace('-', ' - ', $BaseFilename);
	$BaseFilename = str_replace('(', ' (', $BaseFilename);
	$BaseFilename = str_replace('( ', '(', $BaseFilename);
	$BaseFilename = str_replace(')', ') ', $BaseFilename);
	$BaseFilename = str_replace(' )', ')', $BaseFilename);
	$BaseFilename = str_replace(' \'\'', ' ', $BaseFilename);
	$BaseFilename = str_replace('\'\' ', ' ', $BaseFilename);
	$BaseFilename = str_replace(' vs ', ' vs. ', $BaseFilename);
	while (strstr($BaseFilename, '  ') !== false) {
		$BaseFilename = str_replace('  ', ' ', $BaseFilename);
	}
	$BaseFilename = trim($BaseFilename);

	return $DirectoryName.'/'.BetterUCwords($BaseFilename).'.'.strtolower($FileExtension);
}

function BetterUCwords($string) {
	$stringlength = strlen($string);

	$string[0] = strtoupper($string[0]);
	for ($i = 1; $i < $stringlength; $i++) {
		if (($string[$i - 1] == '\'') && ($i > 1) && (($string[$i - 2] == 'O') || ($string[$i - 2] == ' '))) {
			// O'Clock, 'Em
			$string[$i] = strtoupper($string[$i]);
		} elseif (preg_match('#^[\'A-Za-z0-9À-ÿ]$#', $string[$i - 1])) {
			$string[$i] = strtolower($string[$i]);
		} else {
			$string[$i] = strtoupper($string[$i]);
		}
	}

	static $LowerCaseWords = array('vs.', 'feat.');
	static $UpperCaseWords = array('DJ', 'USA', 'II', 'MC', 'CD', 'TV', '\'N\'');

	$OutputListOfWords = array();
	$ListOfWords = explode(' ', $string);
	foreach ($ListOfWords as $ThisWord) {
		if (in_array(strtolower(str_replace('(', '', $ThisWord)), $LowerCaseWords)) {
			$ThisWord = strtolower($ThisWord);
		} elseif (in_array(strtoupper(str_replace('(', '', $ThisWord)), $UpperCaseWords)) {
			$ThisWord = strtoupper($ThisWord);
		} elseif ((substr($ThisWord, 0, 2) == 'Mc') && (strlen($ThisWord) > 2)) {
			$ThisWord[2] = strtoupper($ThisWord[2]);
		} elseif ((substr($ThisWord, 0, 3) == 'Mac') && (strlen($ThisWord) > 3)) {
			$ThisWord[3] = strtoupper($ThisWord[3]);
		}
		$OutputListOfWords[] = $ThisWord;
	}
	$UCstring = implode(' ', $OutputListOfWords);
	$UCstring = str_replace(' From ', ' from ', $UCstring);
	$UCstring = str_replace(' \'n\' ', ' \'N\' ', $UCstring);

	return $UCstring;
}


echo '<hr><form action="'.htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES).'" method="get">';
echo '<h4><b>Initial Scan Option:</b></h4>'; 
echo '<b>Warning:</b> Scanning a new directory will erase all previous entries in the database!<br><br>';
echo 'Directory: <input type="text" name="scan" size="50" value="'.htmlentities(!empty($_REQUEST['scan']) ? $_REQUEST['scan'] : '', ENT_QUOTES).'"> ';
echo '<input type="submit" value="Go" onClick="return confirm(\'Are you sure you want to erase all entries in the database and start scanning again?\');">';
echo '</form>';
echo '<hr><form action="'.htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES).'" method="get">';
echo '<h4><b>New Scan Option:</b></h4>'; 
echo 'Re-scanning a new directory will only add new, previously unscanned files into the list (and not erase the database).<br><br>';
echo 'Directory: <input type="text" name="newscan" size="50" value="'.htmlentities(!empty($_REQUEST['newscan']) ? $_REQUEST['newscan'] : '', ENT_QUOTES).'"> ';
echo '<input type="submit" value="Go">';
echo '</form><hr>';
echo '<ul>';
echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?deadfilescheck=1').'">Remove deleted or changed files from database</a></li>';
//echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?md5datadupes=1').'">List files with identical MD5_DATA values</a></li>';
//echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?artisttitledupes=1').'">List files with identical artist + title</a> (<a href="'.$_SERVER['PHP_SELF'].'?artisttitledupes=1&samemix=1">same mix only</a>)</li>';
echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?fileextensions=1').'">File with incorrect file extension</a></li>';
echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?formatdistribution=1').'">File Format Distribution</a></li>';
//echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?audiobitrates=1').'">Audio Bitrate Distribution</a></li>';
//echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?vbrmethod=1').'">VBR_Method Distribution</a></li>';
echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?tagtypes=1').'">Tag Type Distribution</a></li>';
echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?genredistribution='.urlencode('%')).'">Genre Distribution</a></li>';
//echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?missingtrackvolume=1').'">Scan for missing track volume information (update database from pre-v1.7.0b5)</a></li>';
//echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?encoderoptionsdistribution=1').'">Encoder Options Distribution</a></li>';
//echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?encodedbydistribution='.urlencode('%')).'">Encoded By (ID3v2) Distribution</a></li>';
//echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?trackinalbum=1').'">Track number in Album field</a></li>';
//echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?tracknoalbum=1').'">Track number, but no Album</a></li>';
//echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?titlefeat=1').'">"feat." in Title field</a></li>';
//echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?emptygenres=1').'">Blank genres</a></li>';
//echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?trackzero=1').'">Track "zero"</a></li>';
//echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?nonemptycomments=1').'">non-empty comments</a></li>';
//echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?unsynchronizedtags=2A1').'">Tags that are not synchronized</a> (<a href="'.$_SERVER['PHP_SELF'].'?unsynchronizedtags=2A1&autofix=1">autofix</a>)</li>';
//echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?filenamepattern='.urlencode('[N] A - T {R}')).'">Filenames that don\'t match pattern</a> (<a href="?filenamepattern='.urlencode('[N] A - T {R}').'&autofix=1">auto-fix</a>)</li>';
//echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?filenamepattern='.urlencode('A - T')).'">Filenames that don\'t match pattern</a></li>';
//echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?correctcase=1').'">Correct filename case (Win/DOS)</a></li>';
echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?fixid3v1padding=1').'">Fix ID3v1 invalid padding</a></li>';
echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?errorswarnings=1').'">Files with Errors and/or Warnings</a></li>';
//echo '<li><a href="'.htmlentities($_SERVER['PHP_SELF'].'?rescanerrors=1').'">Re-scan only files with Errors and/or Warnings</a></li>';
echo '</ul>';

$SQLquery  = 'SELECT COUNT(*) AS `TotalFiles`';
$SQLquery .= ' FROM `'.mysqli_real_escape_string($link, GETID3_DB_TABLE).'`';
$result = mysqli_query_safe($SQLquery);
if ($row = mysqli_fetch_array($result)) {
	echo '<hr size="1">';
	echo '<div style="float: right;">';
	echo 'Spent '.number_format(mysqli_query_safe(null), 3).' seconds querying the database<br>';
	echo '</div>';
	echo '<b>Currently in the database:</b><TABLE>';
	echo '<tr><th align="left">Total Files</th><td>'.number_format($row['TotalFiles']).'</td></tr>';
	echo '</table>';
	echo '<br clear="all">';
}

echo '</body></html>';
