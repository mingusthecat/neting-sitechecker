# neting-sitechecker
Site Checker is a Site Guard Integrity Checker<br /><br />
Check and keep track of site integrity, log and email discrepancies due to site modification and hacking.<br /><br />
This script will crawl the specified directory or site folder, create a static map of site files info (hash, permissions, date of modification, size, etc.). If the script found a discrepancy between file checked and saved info, the script will email administrator with details. <br /><br />
The script will check also for added files or directories to the original saved site structure.
For maximu security the script should be kept out of the public folder (public_html) and a crontask scheduled to perform daily check up.
<br /><br />
<h2>Configuration of the script</h2>
In order to configure the script, change the setting.php file according to your configuration

<h2>How to run this script</h2>

In order to run this script you should have shell access to your server, if the script is placed out of public folder (suggested!!).<br />
The best option is to set a daily cron task to perform regular checkup of your site. <br /><br /><br />


<h2>License and disclaimer</h2>


    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

<h2>Support</h2>

This is software is provided as it is without any warranty and without any support.<br /><br />
For more info contact luca@neting.it or visit Neting.it web design web agency at https://www.neting.it
