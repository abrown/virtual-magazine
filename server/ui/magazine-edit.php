<!-- virtual-mag -->
<link rel="stylesheet" type="text/css" href="/client/virtual-mag.css" />
<link rel="stylesheet" type="text/css" href="/client/jquery/jquery-ui-1.8.22.custom.css" />
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.min.js"></script>
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js"></script>
<script type="text/javascript" src="/client/virtual-mag.js"></script>
<script type="text/javascript">
    $(document).ready(function () {
        $("div#virtual-mag").virtualmag({
            url: '<?php echo WebUrl::create($data->getURI(), false); ?>',
            showNavigation: true,
            editable: true
        });
    });
</script>

<!-- Google Analytics -->
<?php if (@$data->tracking_code): ?>
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

<!-- virtual-mag -->
<div id="virtual-mag"></div>

<h3>URL</h3>
<?php $url = WebUrl::create('magazine/'.$data->id, false); ?>
<p>The URL to this magazine is: <a href="<?php echo $url ?>" style="font-weight: bold;"><?php echo $url; ?></a>.</p>

<h3>Data</h3>
<script type="text/javascript">
    $(document).ready(function () {
        $('#magazine-edit').submit(function(){
 
        });
    });
</script>
<p>
    Edit the magazine properties below.
</p>   
<form id="magazine-edit" method="POST" action="<?php echo WebUrl::create("/magazine/{$data->id}?method=PUT", false); ?>">
    <table class="admin-table magazine">
        <tr>    
            <td>ID</td>
            <td id="magazine#id"><?php echo @$data->id; ?></td>
        </tr>
        <tr>    
            <td>Title</td>
            <td id="magazine-title"><input type="text" name="title" value="<?php echo $data->title; ?>" title="Enter the title to display for this magazine." required="required"/></td>
        </tr>  
        <tr>    
            <td>Description</td>
            <td id="magazine-description"><input type="text" name="description" value="<?php echo $data->description; ?>" title="Enter the description to display for this magazine." /></td>
        </tr> 
        <tr>    
            <td>Tracking Code</td>
            <td id="magazine-tracking-code"><input type="text" name="tracking_code" value="<?php echo $data->tracking_code; ?>" title="Enter the Google Analytics tracking code to associate with this magazine." /></td>
        </tr>
        <tr>    
            <td>PDF Server Path</td>
            <td id="magazine#pdf"><?php echo @$data->pdf; ?></td>
        </tr>
        <tr>    
            <td>PDF URL</td>
            <td id="magazine#pdf"><a href="<?php echo @$data->url_to_pdf; ?>"><?php echo @$data->url_to_pdf; ?></a></td>
        </tr>
        <tr>    
            <td>Number of Pages</td>
            <td id="magazine#pages"><?php echo Magazine::countPages($data->id); ?></td>
        </tr>
        <tr>    
            <td></td>
            <td><input type="submit" id="magazine-submit" value="Save"/></td>
        </tr>
    </table>
</form>