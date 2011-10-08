**** DISCLAIMER ****
Copyright (C) 2010 - Jens Nyman <nymanjens.nj@gmail.com>

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; either version 2 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT
ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
details.

**** DESCRIPTION ****
This extension implements a new tag, <filelist>, which generates a list of
all images or other media that were uploaded to the page. Also, the tag adds
an input field to add a new file.

**** INSTALLATION ****
- copy FileList/ to extensions folder
- Add to LocalSettings.php:
    require_once("$IP/extensions/FileList/FileList.php");
    $wgFileListConfig['upload_anonymously'] = false; // set this if uploads need to be anonymous
    
- Allow file uploads

**** NOTES ****
This extension edits some global settings:
- allowed upload extensions
- caching (disabled)

