<?php

/*
Script Name: Site Guard Integrity Checker
Plugin URI: https://github.com/mingusthecat/neting-sitechecker
Description: Site Checker is a Site Guard Integrity Checker
Version: 1.0
Author: Luca Mainieri
Author URI: http://www.neting.it
License: GPLv2
*/

/*  Copyright 2016  Luca Mainieri  (email : luca@neting.it)

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


/********************************************************************************/
/********************************************************************************/
//      CONFIGURAZIONE // SETTING 
/********************************************************************************/
/********************************************************************************/

// path site root to check integrity, for maximum safety, keep this out of public folders
define("PATH", "/your/absolute/path/to/check/");
// check file - this file will hold the info, for maximum safety, keep this out of public folders
$checkFile = "/your/absolute/path/logs/check.txt";

// file logs of warning, for maximum safety, keep this out of public folders
$logFile = "/your/absolute/path/logs/logs.txt";

//mail setting for warnings
$emailAddress = "your@mail.com";
$emailSubject = "Site Guard Integrity Checker - ".PATH;

// extensions to exclude from control, an empty array will return all extensions
$extExclude = array("png","jpg","gif","bmp","tiff","jpeg","zip");

// directories to ignore, an empty array will check all directories
$skip = array("logs", "cache");

/********************************************************************************/
/********************************************************************************/
/********************************************************************************/
/********************************************************************************/

// DO NOT TOUCH!!

$c = array();
if(file_exists($checkFile)) {
	$c = file_get_contents($checkFile);
}else{
	$isFirstRun = true;
	$c = '{}';
}
$checkObj = json_decode($c);
$mismatchLog = '';

$files = array();

// build profile
$dir = new RecursiveDirectoryIterator(PATH);
$iter = new RecursiveIteratorIterator($dir);
while ($iter->valid()) {
    // skip unwanted directories
    if (!$iter->isDot() && !in_array($iter->getSubPath(), $skip)) {
        // get specific file extensions
        if (!empty($extExclude)) {
            // PHP 5.3.4: if (in_array($iter->getExtension(), $ext)) {
            if (!in_array(pathinfo($iter->key(), PATHINFO_EXTENSION), $extExclude)) {
                $files[$iter->key()] = checkFile($iter->key());	
            }
        } else {
            // ignore file extensions
            $files[$iter->key()] = checkFile($iter->key());
			
        }
    }
    $iter->next();
}

$result = json_encode($files);
file_put_contents($checkFile, $result);

//debug email
//mail($emailAddress, $emailSubject, '<pre>' . print_r($checkObj, true) . '</pre>');

if($mismatchLog != '' && !$isFirstRun){
	// Send
	mail($emailAddress, $emailSubject, $mismatchLog);
	//logResult($logFile, $mismatchLog);
	file_put_contents($logFile, $mismatchLog, FILE_APPEND); 
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
	$result['date_created'] = filectime($file);
	$result['size'] = filesize($file);
	$result['type'] = pathinfo($file, PATHINFO_EXTENSION);
	$result['permission'] = decodePermission($file);
	$result['file'] = $file;
	
	compareResult($result);
	
	return $result;
}

function compareResult($result){
	
	global $mismatchLog;
	global $checkObj;
	
	//check if is an anexpected file
	if(!property_exists($checkObj, $result['file'])){
		$mismatchLog .= date("Y-m-d H:i:s") . " - " . $result['file'] . " - file has been added to site structure \r\n";
	}else{
	
		//il file ha un diverso hash
		if($result['hash'] != $checkObj->$result['file']->hash){
			$mismatchLog .= date("Y-m-d H:i:s") . " - " . $result['file'] . " - file has a different hash \r\n";
		}else{
		
			//il file ha un diverso set di permessi
			if($result['permission'] != $checkObj->$result['file']->permission){
				$mismatchLog .= date("Y-m-d H:i:s") . " - " . $result['file'] . " - file has different permissions - it was ".$checkObj->$result['file']->permission." and now is ".$result['permission']."\r\n";
			}
			
			//il file ha modificato la data
			if($result['date_modified'] != $checkObj->$result['file']->date_modified){
				$mismatchLog .= date("Y-m-d H:i:s") . " - " . $result['file'] . " - file has been modified according the modifify data - last date was ".$checkObj->$result['date_modified']->permission." and now is ".$result['date_modified']."\r\n";
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





?>
