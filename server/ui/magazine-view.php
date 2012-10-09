<?php
if (!@$data && !@$data->getURI()) {
    $url = '[URL to server here]';
} else {
    $url = WebUrl::getSiteUrl() . 'service.php' . DS . $data->getURI();
}
$magazine = $data;
?>

<!-- INSERT THIS CODE -->
<link rel="stylesheet" type="text/css" href="<?php echo WebUrl::getSiteUrl(); ?>client/virtual-mag.css" />
<link rel="stylesheet" type="text/css" href="<?php echo WebUrl::getSiteUrl(); ?>client/jquery/jquery-ui-1.8.22.custom.css" />
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.min.js"></script>
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js"></script>
<!--<script type="text/javascript" src="//code.jquery.com/mobile/1.1.1/jquery.mobile-1.1.1.min.js"></script>-->
<script type="text/javascript" src="<?php echo WebUrl::getSiteUrl(); ?>client/pageflip.js"></script>
<script type="text/javascript" src="<?php echo WebUrl::getSiteUrl(); ?>client/pagezoom.js"></script>
<script type="text/javascript">
    $(document).ready(function () {
        // start flip widget
        $("div#virtual-mag").pageflip({
            BOOK_WIDTH: 1200,
            PAGE_WIDTH: 590,
            BOOK_HEIGHT: 800,
            PAGE_HEIGHT: 775
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
        // 
        $('.magazine-zoom-in').click(function(e){
            $("div#virtual-mag").pagezoom('zoomIn'); 
        });
        $('.magazine-zoom-out').click(function(e){
            $("div#virtual-mag").pagezoom('resetZoom'); 
        });
    });
</script>
<!--
<script type="text/javascript" src="<?php echo WebUrl::getSiteUrl(); ?>client/virtual-mag.js"></script>
<script type="text/javascript">
    $(document).ready(function () {
        // initialize widget
        $("div#virtual-mag").virtualmag({
            url: '<?php echo $url; ?>',
            showNavigation: true,   // turn on navigation bar at top
            editable: false,        // turn on editing bar (add link, etc.)
            fixedWidth: 1200,       // set the width of the widget; for caching reasons, the server accepts increments of 50px.
            fixedHeight: false      // like 'fixedWidth', but set the height; only use one or the other
        });
    });
</script>-->

<!-- navigation controls -->
<table class="magazine-navigation-controls">
    <tr>
        <td>
            <h2><?php echo $data->title; ?></h2>
            <span class="magazine-created"><?php echo $data->created; ?></span>
        </td>
        <td>
            <button class="magazine-button magazine-zoom-out">
                <img src="/client/buttons/zoom-out.png" alt="Zoom" />
            </button>
            <button class="magazine-button magazine-zoom-in">
                <img src="/client/buttons/zoom-in.png" alt="Zoom" />
            </button>
            <button class="magazine-button magazine-print" onclick="window.location = '<?php echo WebUrl::getDirectoryUrl() . 'print.php/' . $data->id; ?>';">
                <img src="/client/buttons/print.png" alt="Print" />
            </button>
            <?php
            // create email string
            $email = 'mailto: ?subject=Check Out "' . $data->title . '"';
            $email .= '&body=Hey, check out this magazine I found: "' . $data->title . '" <' . WebUrl::create('magazine/' . $data->id) . '>.';
            $email = urlencode($email);
            ?>
            <button class="magazine-button magazine-email" onclick="window.location = '<?php echo $email; ?>';">
                <img src="/client/buttons/email.png" alt="E-mail" />
            </button>
        </td>
        <td class="magazine-social">
            <span class="magazine-follow">
                Share on: </span>
            <a class="magazine-button magazine-facebook" href="http://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(WebUrl::getUrl()); ?>">
                <img src="/client/buttons/facebook.png" alt="Share on Facebook" />
            </a>
            <a class="magazine-button magazine-twitter" href="http://twitter.com/intent/tweet?url=<?php echo urlencode(WebUrl::getUrl()); ?>">
                <img src="/client/buttons/twitter.png" alt="Share on Twitter" />
            </a>
            <a type="submit" class="magazine-button magazine-pinterest" href="http://pinterest.com/pin/create?url=<?php echo urlencode(WebUrl::getUrl()); ?>">
                <img src="/client/buttons/pinterest.png" alt="Share on Pinterest" />
            </a>
        </td>
    </tr>
</table>

<!-- set up widget on this DIV -->
<div id="virtual-mag">        
    <?php
    $_GET['width'] = 600; // set images to 200 DPI
    $number_of_pages = $magazine::countPages($magazine->id);
    for ($i = 1; $i <= $number_of_pages; $i++) {
        $page = new Page($magazine->id, $i);
        echo "<div class='magazine-page page'>";
        $data = $page->GET();
        include 'server/ui/page-view.php';
        echo "</div>";
    }
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