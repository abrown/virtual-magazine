<?php
// create URL
if (!@$data && !@$data->getURI()) {
    $url = '[URL to server here]';
} else {
    $url = WebUrl::create("service.php/{$data->getURI()}");
}

// simplify notation
$magazine = $data;

// create e-mail URL
$email = 'subject=Check Out "' . $data->title . '"';
$email .= '&body=Hey, check out this magazine I found: "' . $data->title . '" <' . WebUrl::createAnchoredUrl("magazine/{$data->id}") . '>.';
$email = 'mailto:...?' . $email;
?>

<!-- INSERT THIS CODE -->
<link rel="stylesheet" type="text/css" href="<?php echo WebUrl::create("client/jquery/jquery-ui-1.8.22.custom.css"); ?>" />
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.min.js"></script>
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js"></script>
<script type="text/javascript" src="<?php echo WebUrl::create("client/pageflip.js"); ?>"></script>
<script type="text/javascript" src="<?php echo WebUrl::create("client/pagezoom.js"); ?>"></script>
<script type="text/javascript">
    $(document).ready(function () {
        // start flip widget
        $("div#virtual-mag").pageflip({
            BOOK_WIDTH: 1168,
            PAGE_WIDTH: 570,
            BOOK_HEIGHT: 831,
            PAGE_HEIGHT: 806,
            BASE_URL: '<?php echo WebUrl::createAnchoredUrl("page/{$data->id}/[page#]?width=570"); ?>',
            show_buttons: true,
            use_ajax_loading: true
        });
        
        // button events
        $('.book-print').click(function(){
            window.location = '<?php echo WebUrl::create("print.php/{$data->id}"); ?>';
        });
        $('.book-email').click(function(){
            window.location = '<?php echo $email; ?>';
        });
        $('.book-zoom-in').click(function(e){
            $("div#virtual-mag").pagezoom('zoomIn'); 
        });
        $('.book-zoom-out').click(function(e){
            $("div#virtual-mag").pagezoom('resetZoom'); 
        });

        // start zoom widget
        $("div#virtual-mag").pagezoom({
            zoomed: function(){
                $("div#virtual-mag").pageflip('disable');
            },
            reset: function(){
                $("div#virtual-mag").pageflip('enable');
            }
        });
    });
</script>

<!-- navigation controls -->
<div class="book-controls">
    <span>
        <h2><?php echo $data->title; ?></h2>
        <?php echo $data->created; ?>
    </span>
    <span>
        <button class="book-zoom-out">
            <img src="<?php echo WebUrl::create('client/buttons/zoom-out.png'); ?>" alt="Zoom" />
        </button>
        <button class="book-zoom-in">
            <img src="<?php echo WebUrl::create('client/buttons/zoom-in.png'); ?>" alt="Zoom" />
        </button>
        <button class="book-print">
            <img src="<?php echo WebUrl::create('client/buttons/print.png'); ?>" alt="Print" />
        </button>
        <button class="book-email">
            <img src="<?php echo WebUrl::create('client/buttons/email.png'); ?>" alt="E-mail" />
        </button>
    </span>
    <span>
        Share on:
        <a class="button book-facebook" href="http://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(WebUrl::getUrl()); ?>">
            <img src="<?php echo WebUrl::create('client/buttons/facebook.png'); ?>" alt="Share on Facebook" />
        </a>
        <a class="button book-twitter" href="http://twitter.com/intent/tweet?url=<?php echo urlencode(WebUrl::getUrl()); ?>">
            <img src="<?php echo WebUrl::create('client/buttons/twitter.png'); ?>" alt="Share on Twitter" />
        </a>
        <a class="button book-pinterest" href="http://pinterest.com/pin/create?url=<?php echo urlencode(WebUrl::getUrl()); ?>">
            <img src="<?php echo WebUrl::create('client/buttons/pinterest.png'); ?>" alt="Share on Pinterest" />
        </a>
    </span>
</div>

<!--
<tr>
<td>
    <h2><?php echo $data->title; ?></h2>
    <span class="magazine-created"><?php echo $data->created; ?></span>
</td>
<td>
    <button class="magazine-zoom-out">
        <img src="<?php echo WebUrl::create('client/buttons/zoom-out.png'); ?>" alt="Zoom" />
    </button>
    <button class="magazine-zoom-in">
        <img src="<?php echo WebUrl::create('client/buttons/zoom-in.png'); ?>" alt="Zoom" />
    </button>
    <button class="magazine-print">
        <img src="<?php echo WebUrl::create('client/buttons/print.png'); ?>" alt="Print" />
    </button>
    <button class="magazine-email" ;">
        <img src="<?php echo WebUrl::create('client/buttons/email.png'); ?>" alt="E-mail" />
    </button>
</td>
<td class="magazine-social">
    <span class="magazine-follow">
        Share on: </span>
    <a class="magazine-facebook" href="http://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(WebUrl::getUrl()); ?>">
        <img src="<?php echo WebUrl::create('client/buttons/facebook.png'); ?>" alt="Share on Facebook" />
    </a>
    <a class="magazine-twitter" href="http://twitter.com/intent/tweet?url=<?php echo urlencode(WebUrl::getUrl()); ?>">
        <img src="<?php echo WebUrl::create('client/buttons/twitter.png'); ?>" alt="Share on Twitter" />
    </a>
    <a class="magazine-pinterest" href="http://pinterest.com/pin/create?url=<?php echo urlencode(WebUrl::getUrl()); ?>">
        <img src="<?php echo WebUrl::create('client/buttons/pinterest.png'); ?>" alt="Share on Pinterest" />
    </a>
</td>
</tr>
</table>-->

<!-- set up widget on this DIV -->
<div id="virtual-mag">        
    <?php
    /*
     * 
      $_GET['width'] = 570; // set images to 200 DPI
      $number_of_pages = $magazine::countPages($magazine->id);
      for ($i = 1; $i <= 4; $i++) {
      $page = new Page($magazine->id, $i);
      $data = $page->GET();
      include 'server/ui/page-view.php';
      }
     * 
     */
    ?>
</div>

<?php if (@$magazine->tracking_code): ?>
    <!-- Google Analytics -->
    <script type="text/javascript">
        var _gaq = _gaq || [];
        _gaq.push(['_setAccount', '<?php echo $magazine->tracking_code; ?>']);
        _gaq.push(['_trackPageview']);
        (function() {
            var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
            ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
            var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
        })();

    </script>
<?php endif; ?>