<h2>Magazine Saved</h2>
<p>
    Magazine '<?php echo $data->id ?>' saved.
    <a href="<?php echo WebUrl::createAnchoredUrl('library', false); ?>">Back to Library</a>
    or <a href="<?php echo WebUrl::createAnchoredUrl('magazine/' . $data->id, false); ?>">view magazine</a>
</p>  
