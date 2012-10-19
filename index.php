<?php
require 'server/pocket-knife/start.php';
add_include_path('server/classes');

// get configuration
$configuration = new Settings(get_base_dir() . '/../configuration.json');

// start capture
ob_start();
?>
<h2>Browse</h2>
<?php
// get library
$library = new Library();
$magazines = $library->GET();
foreach ($magazines->items as $id => $magazine):
    try {
        $page = new Page($id, 1);
        $page->GET();
        ?>
        <a href="<?php echo WebUrl::create("service.php/magazine/{$id}"); ?>">
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
$template = new WebTemplate('site/templates/main.php', WebTemplate::PHP_FILE);
$template->replace('title', $configuration->get('title'));
$template->replace('content', $content);
$template->display();
