<?php
   include 'header.php';

   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ini_set('memory_limit', '256M');

   $getid3_demo_mysqli_encoding = 'ISO-8859-1';
   $getid3_demo_mysqli_md5_data = false;        // All data hashes are by far the slowest part of scanning
   $getid3_demo_mysqli_md5_file = false;
   

   define('GETID3_DB_HOST',  '10.20.30.10');
   define('GETID3_DB_USER',  'moviedb_admin');
   //define('GETID3_DB_USER',  'btchriss');
   define('GETID3_DB_PASS',  'KW3V4yd2VUmqgBZb');
   //define('GETID3_DB_PASS',  'CRfc7TyqeChBdECa');
   define('GETID3_DB_DB',    'moviesdb');
   define('GETID3_DB_TABLE', 'files');
   define('RESCAN_PATH', '/config/www/movies/');
   
   $link = mysqli_connect(GETID3_DB_HOST, GETID3_DB_USER, GETID3_DB_PASS, GETID3_DB_DB);

   ob_start();
   if (!$link) {
      $errormessage = ob_get_contents();
      ob_end_clean();
      die('Could not connect to mysqli host: <blockquote style="background-color: #FF9933; padding: 10px;">'.mysqlii_error().'</blockquote>');
   }
   if (!mysqli_select_db($link, GETID3_DB_DB)) {
      $errormessage = ob_get_contents();
      ob_end_clean();
      die('Could not select database: <blockquote style="background-color: #FF9933; padding: 10px;">'.mysqlii_error().'</blockquote>');
   }
   ob_end_clean();

   
   $getid3PHP_filename = realpath('php/getID3/getid3/getid3.php');
   if (!file_exists($getid3PHP_filename) || !include_once($getid3PHP_filename)) {
      die('Cannot open '.$getid3PHP_filename);
   }
   // Initialize getID3 engine
   $getID3 = new getID3;
   $getID3->setOption(array(
      'option_md5_data' => $getid3_demo_mysqli_md5_data,
      'encoding'        => $getid3_demo_mysqli_encoding,
   ));

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

   if ( !empty($_REQUEST['refresh']) ) {//Start of scan
      $MoviesDirectory = '../../../movies';
      $FilesInDir = array();

      echo 'Scanning all media files in <b>'.str_replace('\\', '/', realpath(!empty($_REQUEST['refresh']) ? $_REQUEST['refresh'] : $MoviesDirectory)).'</b> (and subdirectories)<hr>';

      $AlreadyInDatabase = array();
      set_time_limit(300);

      $SQLquery  = 'SELECT `filename`';
      $SQLquery .= ' FROM `'.mysqli_real_escape_string($link, GETID3_DB_TABLE).'`';
      $SQLquery .= ' ORDER BY `filename` ASC';

      $result = mysqli_query_safe($SQLquery);

      while ($row = mysqli_fetch_array($result)) {
      
         $AlreadyInDatabase[] = $row['filename'];
      }

      $DirectoriesToScan  = array(!empty($_REQUEST['refresh']) ? $_REQUEST['refresh'] : $MoviesDirectory);
      $DirectoriesScanned = array();
      
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
      
      $FilesInDir = array_unique($FilesInDir);
      sort($FilesInDir);

      $starttime = time();
      $rowcounter = 0;
      $totaltoprocess = count($FilesInDir);
      echo $totaltoprocess;

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
   
   
            if ( !empty($_REQUEST['refresh']) ) {
   
               $SQLquery  = 'INSERT INTO `'.mysqli_real_escape_string($link, GETID3_DB_TABLE).'` (`filename`, `fileformat`, `artist`, `title`, `creation_date`, `genre`, `description`, `description_long`, `cover` ) VALUES (';
               $SQLquery .= '"'.mysqli_real_escape_string($link, !empty($ThisFileInfo['filename']) ? $ThisFileInfo['filename'] : '').'", ';
               $SQLquery .= '"'.mysqli_real_escape_string($link, !empty($ThisFileInfo['fileformat']) ? $ThisFileInfo['fileformat'] : '').'", ';
               $SQLquery .= '"'.mysqli_real_escape_string($link, !empty($ThisFileInfo['comments_html']['artist']) ? implode("\t", $ThisFileInfo['comments_html']['artist']) : '').'", '; //artist
               $SQLquery .= '"'.mysqli_real_escape_string($link, $this_track_title).'", '; //title
               $SQLquery .= '"'.mysqli_real_escape_string($link, !empty($ThisFileInfo['comments_html']['creation_date']) ? implode("\t", $ThisFileInfo['comments_html']['creation_date']) : '').'", '; //creation_date
               $SQLquery .= '"'.mysqli_real_escape_string($link, !empty($ThisFileInfo['comments_html']['genre']) ? implode("\t", $ThisFileInfo['comments_html']['genre']) : '').'", '; //genre
               $SQLquery .= '"'.mysqli_real_escape_string($link, !empty($ThisFileInfo['comments_html']['description']) ? implode("\t", $ThisFileInfo['comments_html']['description']) : '').'", '; 
               $SQLquery .= '"'.mysqli_real_escape_string($link, !empty($ThisFileInfo['comments_html']['description_long']) ? implode("\t", $ThisFileInfo['comments_html']['description_long']) : '').'", ';
               $SQLquery .= '"'.mysqli_real_escape_string($link, !empty($ThisFileInfo['comments']['picture'][0]['data']) ? $ThisFileInfo['comments']['picture'][0]['data'] : '').'")'; //cover art
            } //End of if ( !empty($_REQUEST['refresh']) )
            flush();
            
            mysqli_query_safe($SQLquery);
         }
   
      }// End of For loop
      $SQLquery = 'OPTIMIZE TABLE `'.mysqli_real_escape_string($link, GETID3_DB_TABLE).'`';
      mysqli_query_safe($SQLquery);

      echo '<hr>Done scanning!<hr>';
      // End of Scan
   } else {
      echo '<div class="main__content"><form class="form_input" action="'.htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES).'" method="get">';
      echo '<h4><b>New Scan Option:</b></h4>'; 
      echo 'Re-scanning a new directory will only add new, previously unscanned files into the list (and not erase the database).<br><br>';
      echo 'Directory: <input class="form__input" type="text" name="refresh" size="50" value="'.htmlentities(!empty($_REQUEST['refresh']) ? $_REQUEST['refresh'] : '', ENT_QUOTES).'"> ';
      echo '<input type="submit" class="btn btn--med btn--blue" value="Go">';
      echo '</form>';

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
   }

   

   
   include 'footer.php';
?>