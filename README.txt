VIRTUAL-MAGAZINE
----------------

The virtual magazine is a PDF publishing platform for the web. It uses PHP, 
HTML5, and jQuery to serve up magazine pages to HTTP clients; some features 
include the ability to add clickable links to pages, embedding, and print views.
 It requires GhostScript installed on the server to convert the PDF to JPEGs.

An example is available at http://www.purplecowmagazines.com.


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


Configuring the server
----------------------

Main files to look at:
    1. service.php - provides a RESTful web service using pocket-knife for 
    manipulating magazines, pages, links, etc.; the start URL for creating
    2. and viewing magazines is 'service.php/library'
    embed.php - browse to this with the magazine ID appended to display the HTML5 
    code to embed; e.g. 'embed.php/some-magazine-id'
    3. print.php - browse to this with the magazine ID appended to display the a 
    printable version of the magazine


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