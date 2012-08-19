<h2>Magazine Saved</h2>
<p>
    Magazine '<?php echo $data->id ?>' saved.
    <a href="<?php echo WebUrl::create('library', false); ?>">Back to Library</a>
    or <a href="<?php echo WebUrl::create('magazine/' . $data->id, false); ?>">view magazine</a>
</p>  
