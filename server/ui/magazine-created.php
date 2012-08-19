<?php $url = WebUrl::create('magazine/'.$data, false); ?>
<h2>Magazine Created</h2>
<p>
    Magazine '<?php echo $data ?>' created. The URL is <a href="<?php echo $url; ?>"><?php echo $url; ?></a>.
    <a href="<?php echo WebUrl::create('library', false); ?>">Back to Library</a>
    or <a href="<?php echo $url; ?>">view new magazine</a>
</p>  
