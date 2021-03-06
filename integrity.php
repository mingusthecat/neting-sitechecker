<?php

/*

Site Checker - v. 2.4

Author: Luca Mainieri
Author URI: http://www.neting.it
License: GPLv2

Copyright 2016  Luca Mainieri  (email : luca@neting.it)
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// set max execution time - check your php.ini for more
// Warning : This function has no effect when PHP is running in safe mode. There is no workaround other than turning off safe mode or changing the time limit in the php.ini.

ini_set('max_execution_time', 300);

// increase maximum amount of memory available to PHP
ini_set('memory_limit','128M'); 

// calculate script execution time
// record script execution - start time
$time_start = microtime(true); 

//get settings
include('setting.php');

//get checker configurations
$skipExt 			= file (	$skipExtensions 	, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
$skipDir		 	= file (	$skipDirectories 	, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
$skipFile 			= file (	$skipFiles 			, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
$patterns 			= file (	$dangerousPatterns 	, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );

//inizialize arrays if empty
if(!$skipExt ) $skipExt = array();
if(!$skipDir ) $skipDir = array();
if(!$skipFile ) $skipFile = array();
if(!$patterns ) $patterns = array();

//remove comments from array
$skipExt 	= array_filter	( $skipExt	, 'removeComments');
$skipDir 	= array_filter	( $skipDir	, 'removeComments');
$skipFile 	= array_filter	( $skipFile	, 'removeComments');
$patterns 	= array_filter	( $patterns	, 'removeComments');

//add quarantine extension, skip quarantined files from check
array_push($skipExt, $extQuarantine);

// ++++++++++++++++++++++++++++++++++++++++
// start checker
// ++++++++++++++++++++++++++++++++++++++++

$c = array();

if(file_exists($checkFile)) {
	$c = file_get_contents($checkFile);
	$isFirstRun = false;
}else{
	$isFirstRun = true;
	$c = '{}';
}

$checkObj = json_decode($c);

//initialize check variables and counters
$mismatchLog = '';
$i = 0;
$c = 0;
$issues = 0;
$files = array();

// build profile
$dir = new RecursiveDirectoryIterator(PATH);
$iter = new RecursiveIteratorIterator($dir);

// start iterator and file checker loop
while ($iter->valid()) {	
	
    // skip unwanted directories
	if (!$iter->isDot() && !in_array($iter->getSubPath(), $skipDir)) {
		
        // skip unwanted extensions and files       
            if (!in_array(strtolower(pathinfo($iter->key(), PATHINFO_EXTENSION)), $skipExt) && !in_array( str_replace (PATH,"",$iter->key()), $skipFile ) ) {
                $files[$iter->key()] = checkFile($iter->key());
				$c++;
            }
    }
	
	$i++;
    $iter->next();
}

$result = json_encode($files);

// record and log check
file_put_contents($checkFile, $result);

	// record script execution - end time
	$time_end = microtime(true);

	//dividing with 60 will give the execution time in minutes other wise seconds
	$execution_time = ($time_end - $time_start)/60;

	//issues reported
	$emailBody .= 'We have found <b>'.$issues.' issues</b> on this site'.PHP_EOL;	

	$emailBody .= '<b>Executed check of: </b> '.PATH.' '.PHP_EOL;

	//number of files and/or directory found in defined path
	$emailBody .= '<b>Files / Directories found on path:</b> '.$i.' '.PHP_EOL;	
	
	//number of files and/or directory checked by this script
	$emailBody .= '<b>Files / Directories checked:</b> '.$c.' '.PHP_EOL;	
	
	//execution time of the script
	$emailBody .= '<b>Total Execution Time:</b> '.$execution_time.' mins'.PHP_EOL;	
			
	//Check results
	if($mismatchLog != '')
	$emailBody .= '<b>Found '.$issues.' issues:</b> ' . $mismatchLog . ''.PHP_EOL;		

	//Get settings for debug purpose
	$emailBody .= '<b>Skip Extensions: </b>' . join("," , $skipExt) . ''.PHP_EOL;	

	//Get settings for debug purpose
	$emailBody .= '<b>Skip Directories: </b> ' . join("," , $skipDir) . ''.PHP_EOL;	

	//Get settings for debug purpose
	$emailBody .= '<b>Skip Files: </b>' . join("," , $skipFile ) . ''.PHP_EOL;	
	
	//Get settings for debug purpose
	//$emailBody .= '<b>Malicious Patterns:</b> <pre>' . print_r($patterns, true) . '</pre><br /><hr>';	
	
	//Logs
	//$emailBody .= '<b>Checker logs:</b> <pre>' . print_r($checkObj, true) . '</pre><hr>';		


// send checker email only if checker found something interesting and is not the first run
if($mismatchLog != '' && !$isFirstRun){

	// Send
	sendEmail( $emailAddress, $emailSubject, $emailBody.$mismatchLog );
	
	// record and log checker findings
	file_put_contents($logFile, $mismatchLog, FILE_APPEND);
	 
}

//debug email
if($sendDebugEmail){

	// send email
	sendEmail( $debugEmailAddress, $emailSubject, $emailBody );

}


/********************************************************************************/
/********************************************************************************/
/********************************************************************************/
//      HELPER FUNCTIONS
/********************************************************************************/
/********************************************************************************/
/********************************************************************************/

function checkFile($file){
	
	$result = array();
	
	$result['hash'] = hash_file("sha1", $file);
	$result['date_modified'] = filemtime($file);
	$result['date_changed'] = filectime($file);
	$result['size'] = filesize($file);
	$result['type'] = pathinfo($file, PATHINFO_EXTENSION);
	$result['permission'] = decodePermission($file);
	$result['file'] = $file;
	$result['filename'] = basename($file);   
	
	
	compareResult($result);
	
	return $result;
}

function compareResult($result){
	
	global $mismatchLog;
	global $checkObj;
	global $patterns;
	global $isFirstRun;
	global $issues;
	global $setQuarantine;
	global $extQuarantine;
	
	$scan = false;
	
	if(!property_exists($checkObj, $result['file'])){
			// this file has been added to structure	
			$mismatchLog .= date("Y-m-d H:i:s") . " - " . $result['file'] . " - file has been added to site structure ".PHP_EOL;
			$scan = true;
			$issues++;		
	}elseif($result['hash'] != $checkObj->$result['file']->hash){
			// this file has changed
			$mismatchLog .= date("Y-m-d H:i:s") . " - " . $result['file'] . " - file has a different hash ".PHP_EOL;
			$scan = true;
			$issues++;
	}elseif($result['permission'] != $checkObj->$result['file']->permission){
			// this file has a different set of permission
			$mismatchLog .= date("Y-m-d H:i:s") . " - " . $result['file'] . " - file has different permissions - it was ".$checkObj->$result['file']->permission." and now is ".$result['permission']."".PHP_EOL;
			$issues++;
	}elseif($result['date_modified'] != $checkObj->$result['file']->date_modified || $result['date_changed'] != $checkObj->$result['file']->date_changed){
			//file data has changed
			$mismatchLog .= date("Y-m-d H:i:s") . " - " . $result['file'] . " - file data has been modified / changed " ."".PHP_EOL;
			$issues++;
	}
		
	// we check for malicious piece of code if file has a different hash or is new 
	if($scan && !$isFirstRun){
		
		foreach($patterns as $pattern){
			
			if(stripos(preg_replace('/\s+/', '',file_get_contents($result['file'])),$pattern)){
				//we have a match!!
				$mismatchLog .= date("Y-m-d H:i:s") . " - " . $result['file'] . " - found malicious code pattern '".$pattern."'".PHP_EOL;
				
				if($setQuarantine) {
				// we put this file in quarantine	
					$mismatchLog .= date("Y-m-d H:i:s") . " - " . $result['file'] . " has been put in quarantine and renamed ".$result['filename']. "." . $extQuarantine."'".PHP_EOL;
					rename($result['file'], $result['file'] . "." . $extQuarantine);
				}
				$issues++;
			}
		}
	}
	
	return;
}


function decodePermission($file){
	
		$perms = fileperms($file);
		
		switch ($perms & 0xF000) {
			case 0xC000: // socket
				$info = 's';
				break;
			case 0xA000: // symbolic link
				$info = 'l';
				break;
			case 0x8000: // regular
				$info = 'r';
				break;
			case 0x6000: // block special
				$info = 'b';
				break;
			case 0x4000: // directory
				$info = 'd';
				break;
			case 0x2000: // character special
				$info = 'c';
				break;
			case 0x1000: // FIFO pipe
				$info = 'p';
				break;
			default: // unknown
				$info = 'u';
		}
		
		// Owner
		$info .= (($perms & 0x0100) ? 'r' : '-');
		$info .= (($perms & 0x0080) ? 'w' : '-');
		$info .= (($perms & 0x0040) ?
					(($perms & 0x0800) ? 's' : 'x' ) :
					(($perms & 0x0800) ? 'S' : '-'));
		
		// Group
		$info .= (($perms & 0x0020) ? 'r' : '-');
		$info .= (($perms & 0x0010) ? 'w' : '-');
		$info .= (($perms & 0x0008) ?
					(($perms & 0x0400) ? 's' : 'x' ) :
					(($perms & 0x0400) ? 'S' : '-'));
		
		// World
		$info .= (($perms & 0x0004) ? 'r' : '-');
		$info .= (($perms & 0x0002) ? 'w' : '-');
		$info .= (($perms & 0x0001) ?
					(($perms & 0x0200) ? 't' : 'x' ) :
					(($perms & 0x0200) ? 'T' : '-'));
		
		return $info;

}

function removeComments($string) {
  return strpos($string, '#') === false;
}


function sendEmail( $to, $subject, $message ){
	
	$type 		= 'html'; // or HTML
	$charset 	= 'iso-8859-1';
	
	$message =  nl2br($message);
	
	$from = explode("@", $to);
	$mail     = 'no-reply@'.$from[1];
	$uniqid   = md5(uniqid(time()));
	$headers  = 'From: '.$mail . PHP_EOL;
	$headers .= 'Reply-to: '.$mail . PHP_EOL;
	$headers .= 'Return-Path: '.$mail . PHP_EOL;
	$headers .= 'Message-ID: <'.$uniqid.'@'.$_SERVER['SERVER_NAME'].">" . PHP_EOL;
	$headers .= 'MIME-Version: 1.0' . PHP_EOL;
	$headers .= 'Date: '.gmdate('D, d M Y H:i:s', time()) . PHP_EOL;
	$headers .= 'X-Priority: 3' . PHP_EOL;
	$headers .= 'X-MSMail-Priority: Normal' . PHP_EOL;
	$headers .= 'Content-Type: multipart/mixed;boundary="----------'.$uniqid.'"' . PHP_EOL;
	$headers .= '------------'.$uniqid . PHP_EOL;
	$headers .= 'Content-type: text/'.$type.';charset='.$charset.''. PHP_EOL;
	$headers .= 'Content-transfer-encoding: 7bit';
	
	mail($to, $subject, $message, $headers);
	
	return;
}



?>
