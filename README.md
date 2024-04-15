

# About

phpVirtualBox is from 2017 maintained by Smart Guide Pty Ltd (tudor at smartguide dot com dot au)

with support from various contributors (see https://github.com/phpvirtualbox/phpvirtualbox/graphs/contributors)

Originally Copyright (C) 2015 Ian Moore (imoore76 at yahoo dot com)

FREE, WITHOUT WARRANTY:

All files of this program (phpVirtualBox) are distributed under the
terms contained in the LICENSE.txt file in this folder unless otherwise
specified in an individual source file. By using this software, you are
agreeing to the terms contained therein. If you have not received and read
the license file, or do not agree with its conditions, please cease using
this software immediately and remove any copies you may have in your
possession.

# Installation from Zip file

1) Download zip file from GitHub project site: https://github.com/phpvirtualbox/phpvirtualbox/archive/master.zip

2) Unzip the zipfile into a folder accessible by your web server

3) Rename config.php-example to config.php and edit as needed.

Read the wiki for more information : https://github.com/phpvirtualbox/phpvirtualbox/wiki

# Post installation

Default login is username: admin password: admin

Please report bugs / feature requests to GitHub
https://github.com/phpvirtualbox/phpvirtualbox/issues

Please report bugs related to modification here:
https://github.com/mamayadesu/phpvirtualbox/issues

# Password Recovery

Rename the file recovery.php-disabled to recovery.php, navigate to it in
your web browser, and follow the instructions presented.
<hr>

# About modification

## Restriction of access rights

This modification restricts ordinary users access to important VirtualBox functionality, such as: configuring NAT, creating, editing and deleting virtual machines, appliance import/export, creating virtual media

## Differentiation of access rights to virtual machines

In this modification, ordinary users can only see and interact with those virtual machines if they are in a group whose name is equal to the username

How admin sees it:
![User 'admin'](https://raw.githubusercontent.com/mamayadesu/things/main/phpvirtualbox/screenshots/admin.png)

And how user 'xref' sees it:
![User 'xref'](https://raw.githubusercontent.com/mamayadesu/things/main/phpvirtualbox/screenshots/user.png)

## Modified console

This modification has a different console that works via the HTTP protocol. The console has two input modes: direct and via "VBoxInputServer" (https://github.com/mamayadesu/VBoxInputServer/tree/main). It is recommended to use direct input. However, if direct input doesn't work, use should use input via VBoxInputServer. Please note that `config.php` in this modification has additional settings. They are set at the very beginning of `config.php`

![User 'xref'](https://raw.githubusercontent.com/mamayadesu/things/main/phpvirtualbox/screenshots/user1.png)

![User 'xref'](https://raw.githubusercontent.com/mamayadesu/things/main/phpvirtualbox/screenshots/user2.png)

![User 'xref'](https://raw.githubusercontent.com/mamayadesu/things/main/phpvirtualbox/screenshots/user3.png)

![User 'xref'](https://raw.githubusercontent.com/mamayadesu/things/main/phpvirtualbox/screenshots/user4.png)