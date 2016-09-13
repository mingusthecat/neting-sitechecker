<?php

/********************************************************************************/
/********************************************************************************/
//      CHECKER SETTING 
/********************************************************************************/
/********************************************************************************/

// path site root to check integrity (indicare parent directory - percorso assoluto del server)
define("PATH", "/home/user/public_html/");


// check file - file di controllo e verifica ( keep this file out of public accessible directory )
// this file will save the domain configuration data for comparision
$checkFile = "check.txt";

// file logs of warning ( keep this file out of public accessible directory )
// this file will logs the scan resulting for further usage
$logFile = "logs.txt";

//mail setting for warnings
$emailAddress = "your@mail.com";
$emailSubject = "Check integrity at - ".PATH;

//decide whether you want to get an email with all debug info of scanning
$sendDebugEmail = true;
$debugEmailAddress = 'your@mail.com';


// file with extensions to exclude from control, you can point this to a remote file
// in order to manage more checker from a centralized location

$skipExtensions 	=	'extensionsToSkipFromControl.txt';


// file with list of directories to ignore, an empty (or missing) file will check all directories
// you can point this to a remote file in order to manage more checker from a centralized location

$skipDirectories 	=	'directoriesToSkipFromControl.txt'; 


// file with list of files to ignore
// you can point this to a remote file in order to manage more checker from a centralized location

$skipFiles 			=	'filesToSkipFromControl.txt'; 

// files with a list of dangerous paths to consider
// you can point it to a local file or a remote file to mantain this file remotely

$dangerousPatterns 	=	'dangerousPatterns.txt'; 

// put file containing malicious code in quarantine
$setQuarantine  = true;
$extQuarantine  = '.quarantine';


/********************************************************************************/
/********************************************************************************/
/********************************************************************************/
/********************************************************************************/


?>
