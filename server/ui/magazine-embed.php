<?php
if (!@$data && !@$data->getURI()) {
    $url = '[URL to server here]';
} else {
    $url = WebUrl::getSiteUrl() . DS . 'service.php' . DS . $data->getURI();
}
?>
<!-- INSERT THIS CODE -->
<link rel="stylesheet" type="text/css" href="<?php echo WebUrl::getSiteUrl(); ?>client/virtual-mag.css" />
<link rel="stylesheet" type="text/css" href="<?php echo WebUrl::getSiteUrl(); ?>client/jquery/jquery-ui-1.8.22.custom.css" />
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.min.js"></script>
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js"></script>
<script type="text/javascript" src="<?php echo WebUrl::getSiteUrl(); ?>client/virtual-mag.js"></script>
<script type="text/javascript">
    $(document).ready(function () {
        // initialize widget
        $("div#virtual-mag").virtualmag({
            url: '<?php echo $url; ?>',
            showNavigation: true,   // turn on navigation bar at top
            editable: false,        // turn on editing bar (add link, etc.)
            autoresizeContainer: true,  // if this is enabled, the container will auto-resize when the window resizes
            fixedWidth: 800,        // set the width of the widget; for caching reasons, the server accepts increments of 50px.
            fixedHeight: false      // like 'fixedWidth', but set the height; only use one or the other
        });
    });
</script>
<!-- set up widget within this DIV -->
<div id="virtual-mag"></div>
<?php if (@$data->tracking_code): ?>
    <!-- Google Analytics -->
    <script type="text/javascript">
        var _gaq = _gaq || [];
        _gaq.push(['_setAccount', '<?php echo $data->tracking_code; ?>']);
        _gaq.push(['_trackPageview']);
        (function() {
            var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
            ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
            var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
        })();

    </script>
<?php endif; ?>