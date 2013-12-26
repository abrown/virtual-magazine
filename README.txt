VIRTUAL-MAGAZINE
----------------

The virtual magazine is a PDF publishing platform for the web. It uses PHP, 
HTML5, and jQuery to serve up magazine pages to HTTP clients; some features 
include the ability to add clickable links to pages, embedding, and print views.
 It requires GhostScript installed on the server to convert the PDF to JPEGs.

An example is available at http://www.casabrown.com/projects/virtual-magazine.


Installing Ghostscript
----------------------

1. Download and extract: http://downloads.ghostscript.com/public/ghostscript-
9.05.tar.gz (or latest version)

2. To install, run:
    $ cd ghostscript-9.05
    $ ./configure
    $ make install

3. Test that it will create JPEGs:
    $ gs -dNOPAUSE -sDEVICE=jpeg -r200 -sOutputFile=p%d.jpg file.pdf

For more information on using Ghostscript see www.ghostscript.com/doc/9.06/Use.htm


Configuring the server
----------------------

Main files to look at:
    1. service.php - provides a RESTful web service using pocket-knife for 
    manipulating magazines, pages, links, etc.; the start URL for creating
    and viewing magazines is 'service.php/library'
    2. embed.php - browse to this with the magazine ID appended to display the HTML5 
    code to embed; e.g. 'embed.php/some-magazine-id'
    3. print.php - browse to this with the magazine ID appended to display the a 
    printable version of the magazine
    4. upload.php - used by SWFUpload to upload PDF files to /upload

Install pocket-knife (https://github.com/andrewsbrown/pocket-knife) and point
to it in 'required-include.php'. The server uses pocket-knife to set up the 
RESTful API.

Also, if using Apache, ensure that:
    1. The httpd.conf must allow you to override options in .htaccess. Check that
    "AllowOverride All" is uncommented in httpd.conf.
    2. URL rewriting must be setup up; ensure the line
    "LoadModule rewrite_module modules/mod_rewrite.so" is uncommented in httpd.conf.


Using the client
----------------

In progress; see client/virtual-mag.js for instructions on using the JS API


Notes
-----

- Be sure to set file permissions correctly in 'server/data'; both Ghostscript
and GD will be reading/writing JPEGs here. GD must be installed in PHP for the
automatic re-sizing to work.



TODO
----

- fix caching so that the server can respond with '304 Not Modified' to repeated
requests for the same page; this was disabled because IE was caching everything

- add user ownership of magazines; involves user account creation, etc.

- add option for navigation bar displaying 'page 1 of ...'

- make buttons (embed, print, etc.) configurable in the widget options

- add tests

- improve error dialog display, styling