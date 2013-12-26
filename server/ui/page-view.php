<div class="page" style="width: <?php echo $data->width; ?>px; height: <?php echo $data->height; ?>px">
    <?php
    // Tip: do not add any text/space/elements outside the page <div> tag, because jQuery will parse this as an array of elements and not a DIV
    ?>
    
    <!-- image -->
    <img src="<?php echo $data->image; ?>" title="Page <?php echo $data->page; ?>" alt="Page <?php echo $data->page; ?>" style="width: <?php echo $data->width; ?>px; height: <?php echo $data->height; ?>px;" />
    
    <!-- links -->
    <?php
    foreach ($data->links as $i => $link) {
        // calculate dimensions based on original image width
        $ratio = $data->width / $link->original_image_width;
        $x = (int) ($ratio * $link->x);
        $y = (int) ($ratio * $link->y);
        $width = (int) ($ratio * $link->width);
        $height = (int) ($ratio * $link->height);
        // print link box
        echo '<div class="link" id="link-' . $link->id . '" ';
        echo 'style="position: absolute; ';
        echo 'left: ' . $x . 'px; ';
        echo 'top: ' . $y . 'px; ';
        echo 'width: ' . $width . 'px; ';
        echo 'height: ' . $height . 'px;" ';
        echo 'onclick="window.location = \'' . $link->url . '\';"> ';
        echo '</div>';
        echo "\n";
    } 
    ?>
    
    <!-- Google Analytics -->
    <script type="text/javascript">
        if(typeof(_gaq) != "undefined"){
            _gaq.push(['_trackPageview', '<?php echo WebUrl::createAnchoredUrl("page/{$data->magazine_id}/{$data->page}", false); ?>']);
        }
    </script>
    <?php
        if(WebHttp::getParameter('outline')){
            echo "\n";
            echo "\n\t<!-- outline style -->";
            echo "\n\t".'<style type="text/css">.page{outline: 1px solid black;} .link{outline: 3px dashed grey;}</style>';
        }
    ?>
    
</div>