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
    try {
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
    } catch (Error $e) {
        ?>
        <div class="magazine-thumbnail">
            <h4><?php echo $e->http_code; ?> <?php echo $e->http_message; ?></h4>
            <hr class="title"/>
            <p><span class="property">Message</span>: <?php echo $e->message; ?></p>
            <p><span class="property">Thrown at</span>: <?php echo $e->file; ?>(<?php echo $e->line; ?>)</p>
            <pre>Thrown at <?php echo $e->file; ?>(<?php echo $e->line; ?>)
                <?php
                foreach ($e->trace as $line) {
                    echo $line . "\n";
                }
                ?>
            </pre>
        </div>
        <?php
    }
endforeach;

// end capture
$content = ob_get_clean();

// templating
$template = new WebTemplate('server/ui/site-template.php', WebTemplate::PHP_FILE);
$template->replace('title', 'StatMags');
$template->replace('content', $content);
$template->display();
