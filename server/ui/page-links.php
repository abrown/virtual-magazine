<!-- magazine: <?php echo @$data[0]->magazine_id; ?>, page: <?php echo @$data[0]->page; ?> -->
<ol class="magazine-page-links">
<?php
/**
 * List links
 */
foreach($data as $i => $link){
    echo '<li class="magazine-page-link" id="link-'.$link->id.'">';
    echo $link->url;
    echo ' <a class="magazine-link-test" href="'.$link->url.'">Test</a>';
    echo ' <a class="magazine-link-delete" href="'.WebUrl::create('link/'.$link->id.'?method=DELETE', false).'">Delete</a>';    
    echo '</li>';
    echo "\n";
}
?>
</ol>


