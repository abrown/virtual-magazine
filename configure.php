<?php
require 'server/pocket-knife/start.php';

// get configuration
$configuration = new Settings('server/configuration.json'); 

// get request
$request = new stdClass();
$request->resource = 'configuration';
$request->id = '*';
$request->action = WebHttp::getMethod();
$request->content_type = WebHttp::getContentType() ? WebHttp::getContentType() : 'text/html';
$request->accept = WebHttp::getAccept() ? WebHttp::getAccept() : 'text/html';

// do security
try {
    Service::performSecurityCheck($configuration, $request);
} catch (Error $e) {
    $e->send(WebHttp::getAccept());
}

// if POST, save the sent variables
if( isset($_POST['title']) ){
    $configuration->set('title', BasicSanitize::toText($_POST['title']));
}
if($configuration->isChanged()){
    $configuration->store();
}

// start capture
ob_start();
?>
<h2>Settings</h2>
<form method="POST">
    <p>
        Edit site-wide settings below.
    </p>   
    <table class="admin-table magazine">
        <tr>    
            <td>Site Title</td>
            <td><input type="text" name="title" value="<?php echo $configuration->title; ?>" title="Enter the title to display for this site." required="required"/></td>
        </tr>  
        <tr>    
            <td></td>
            <td><input type="submit" id="magazine-submit" value="Save"/></td>
        </tr>
    </table>
</form>
<?php
// end capture
$content = ob_get_clean();

// templating
$template = new WebTemplate('server/ui/admin-template.php', WebTemplate::PHP_FILE);
$template->replace('title', 'Settings');
$template->replace('content', $content);
$template->display();
?>
