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

// if GET, reset
if( isset($_GET['reset']) && $_GET['reset'] ){
    $configuration->reset();
    $configuration->title = '[Your Title Here]';
    $configuration->representations = array('text/html', 'application/json');
    $configuration->authentication = new stdClass();
    $configuration->set('authentication.enforce_https', false);
    $configuration->set('authentication.authentication_type', 'digest');
    $configuration->set('authentication.password_security', 'plaintext');
    $configuration->set('authentication.users', array());
    $configuration->set('authentication.users.0', new stdClass());
    $configuration->set('authentication.users.0.username', 'admin');
    $configuration->set('authentication.users.0.roles', array('administrator'));
    $configuration->set('authentication.users.0.password', 'admin');
    $configuration->set('authentication.storage', new stdClass());
    $configuration->set('authentication.storage.type', 'memory');
    $configuration->set('acl', array('admin can * */*','* can GET magazine/*','* can GET page/*','* can OPTIONS page/*'));
}

// store configuration, if necessary
if($configuration->isChanged()){
    $configuration->store();
}

// start HTML capture
ob_start();
?>
<h2>Settings</h2>
<form method="POST">
    <p>
        Edit site-wide settings below. You may also restore default values by
        clicking <a class="magazine-admin-button" href="<?php echo WebUrl::create('configure.php?reset=1'); ?>">Restore Defaults</a>.
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
$template = new WebTemplate('site/templates/admin.php', WebTemplate::PHP_FILE);
$template->replace('title', 'Settings');
$template->replace('content', $content);
$template->display();
?>
