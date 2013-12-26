<ol class="links">
<?php
/**
 * List links
 */
foreach($data as $i => $link){
    echo '<li id="link-'.$link->id.'">';
    echo $link->url;
    echo ' <a class="button" href="'.$link->url.'">Test</a>';
    echo ' <a class="button delete-link" href="'.WebUrl::createAnchoredUrl('link/'.$link->id.'?method=DELETE').'">Delete</a>';    
    echo '</li>';
    echo "\n";
}
?>
</ol>