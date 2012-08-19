<?php
    $width = $data->width;
    $height = $data->height;
?>
<!-- magazine: <?php echo $data->magazine_id; ?>, page: <?php echo $data->page; ?> -->
<div class="magazine-page" style="width: <?php echo $width; ?>; height: <?php echo $height; ?>">
    <img src="<?php echo $data->image; ?>" title="Page <?php echo $data->page; ?>" alt="Page <?php echo $data->page; ?>"/>
    <?php
    /**
     * Display image links
     */
    foreach ($data->links as $i => $link) {
        // calculate dimensions based on original image width
        $ratio = $data->width / $link->original_image_width;
        $x = (int) ($ratio * $link->x);
        $y = (int) ($ratio * $link->y);
        $width = (int) ($ratio * $link->width);
        $height = (int) ($ratio * $link->height);
        // print link box
        echo '<div class="magazine-link" id="link-' . $link->id . '" ';
        echo 'style="position: absolute; ';
        echo 'left: ' . $x . 'px; ';
        echo 'top: ' . $y . 'px; ';
        echo 'width: ' . $width . 'px; ';
        echo 'height: ' . $height . 'px;" ';
        echo 'onclick="window.location = \'' . $link->url . '\';">';
        echo '</div>';
        echo "\n";
    }

    /**
     * Google Analytics
     */
    ?>
    <script type="text/javascript">
        if(typeof(_gaq) != "undefined"){
            _gaq.push(['_trackPageview', '<?php echo "/server/service.php/page/{$data->magazine_id}/{$data->page}"; ?>']);
        }
    </script>
</div>
