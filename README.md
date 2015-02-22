# Disclaimer
Copyright (C) 2010 - Jens Nyman <nymanjens.nj@gmail.com>

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; either version 2 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT
ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
details.

# Description
This extension implements a new tag, `<filelist>`, which generates a list of
all images or other media that were uploaded to the page. Also, the tag adds
an input field to add a new file.

# Installation
- Unpack the source archive to `/extensions/FileList/`
- Add to LocalSettings.php:

  ```php
  require_once("$IP/extensions/FileList/FileList.php");

  // optional configuration settings (note: the following are the default values)
  $wgFileListConfig['everyone_can_delete_files'] = true; // If false, only creator and admins can delete files
  $wgFileListConfig['add_title'] = false; // If true, add title above filelist. Example: "== File Attachments =="
  $wgFileListConfig['ask_description'] = false; // If true, the user is asked to fill in a description upon uploading a new file.
   
  ```
- Allow file uploads

# Notes
This extension edits some global settings:
- allowed upload extensions
- caching (disabled)

# Screenshot
![Screenshot](/screenshots/screenshot.png)
