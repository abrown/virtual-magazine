<?php
require 'server/pocket-knife/start.php';
require 'server/classes/Magazine.php';
require 'server/classes/Library.php';
require 'server/classes/Link.php';
require 'server/classes/Page.php';
require 'server/classes/Social.php';

// start capture
ob_start();

?>
<h2>Browse</h2>
<?php

// get library
$library = new Library();
$_GET['width'] = 200;
$magazines = $library->GET();
foreach ($magazines->items as $id => $magazine):
    $page = new Page($id, 1);
    $page->GET();
    $url = WebUrl::getSiteUrl() . 'service.php' . DS . 'magazine' . DS . $id;
    
    ?>
    <a href="<?php echo $url ?>">
        <div class="magazine-thumbnail">
            <img src="<?php echo $page->image; ?>" title="<?php echo $magazine->title; ?>" alt="<?php echo $magazine->title; ?>"/>
            <h4><?php echo $magazine->title; ?></h4>
        </div>
    </a>
<?php

endforeach;

// end capture
$content = ob_get_clean();

// templating
$template = new WebTemplate('server/ui/site-template.php', WebTemplate::PHP_FILE);
$template->replace('title', 'Browse Purple Cow Magazines');
$template->replace('content', $content);
$template->display();