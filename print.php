<?php
require 'server/pocket-knife/start.php';
add_include_path('server/classes');
set_time_limit(0);

try {
    // validate ID
    $tokens = WebUrl::getTokens();
    $id = @$tokens[0];
    if (!$id) {
        throw new Error('No ID defined in URL like "/print.php/[id]', 400);
    }
    // get magazine
    $magazine = new Magazine($id);
    if (!$magazine->getStorage()->exists($id)) {
        throw new Error("Magazine '{$id}' does not exist.", 404);
    }
    $magazine->GET();
    // 
} catch (Error $e) {
    // send error as HTML always
    $e->send('text/html');
    exit();
}
?>
<!doctype html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Print: <?php echo $magazine->title; ?></title>
        <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.min.js"></script>
        <script type="text/javascript">
            $(window).load(function(){
                $('.magazine-loading').remove();
                window.print(); 
            });
        </script>
        <style type="text/css">
            body{
                background-color: #282626;
                display: block;
                font-size: 16px;
                font-family: 'Arial', 'Helvetica', 'Verdana', 'Tahoma', sans-serif;
                font-weight: bold;
                margin: 0.5em;
            }
            .magazine-loading{
                border: 1px solid grey;
                background-color: white;
                background-image: url('<?php echo WebUrl::create('client/images/loading.gif'); ?>');
                background-position: center;
                background-repeat: no-repeat;
                height: 200px;
                margin-top: -100px;
                width: 300px;
                margin-left: -150px;
                position: fixed;
                left: 50%;
                top: 50%;
                padding: 1em;
                font-size: 16px;
                font-weight: bold;
            }
            .magazine-page{
                width: 8.5in;
                height: 11in;
                page-break-after: always;
                background-color: white;
                margin-bottom: 1em;
            }
            @page{
                margin: 0.2in;
            }
        </style>
    </head>
    <body>
        <div class="magazine-loading">
            Magazine will print when loading is complete...
        </div>
        <?php
        $_GET['width'] = 1700; // set images to 200 DPI
        $number_of_pages = $magazine::countPages($id);
        for ($i = 1; $i <= $number_of_pages; $i++) {
            $page = new Page($id, $i);
            $data = $page->GET();
            include 'server/ui/page-view.php';
        }
        ?>
    </body>
</html>