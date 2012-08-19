<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.min.js"></script>
<script type="text/javascript">
    $(document).ready(function () {
        // do nothing
    });
</script>
<h2>Edit Social Settings</h2>
<p>
    Add or edit the follow links for the social sites below.
</p>   
<form id="social-edit" method="POST" action="<?php echo WebUrl::create("/social?method=PUT", false); ?>">
    <table class="admin-table social">
        <tr>    
            <td>Follow On Facebook: </td>
            <td id="social-facebook"><input type="url" name="facebook" value="<?php echo @$data->facebook; ?>" title="Enter the link to follow on Facebook."/></td>
        </tr>
        <tr>    
            <td>Follow On Twitter: </td>
            <td id="social-twitter"><input type="url" name="twitter" value="<?php echo @$data->twitter; ?>" title="Enter the link to follow on Twitter."/></td>
        </tr>
        <tr>    
            <td>Follow On Pinterest: </td>
            <td id="social-pinterest"><input type="url" name="pinterest" value="<?php echo @$data->pinterest; ?>" title="Enter the link to follow on Pinterest."/></td>
        </tr>
        <tr>    
            <td></td>
            <td><input type="submit" id="social-submit" value="Save"/></td>
        </tr>
    </table>
</form>
