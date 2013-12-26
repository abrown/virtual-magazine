<?php
require 'required-include.php';

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
if (isset($_POST['title'])) {
    $configuration->set('title', BasicSanitize::toText($_POST['title']));
}
if (isset($_POST['ghostscript_path'])) {
    $configuration->set('ghostscript_path', $_POST['ghostscript_path']);
}
if (isset($_POST['admin_password']) && !empty($_POST['admin_password']) && $_POST['admin_password'] == $_POST['admin_password_confirmation']) {
    $configuration->set('authentication.users.0.password', $_POST['admin_password']);
}

// if GET, reset
if (isset($_GET['reset']) && $_GET['reset']) {
    $configuration->reset();
    $configuration->title = '[Your Title Here]';
    $configuration->representations = array('text/html', 'application/json', 'multipart/form-data', 'application/x-www-form-urlencoded');
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
    $configuration->set('acl', array('admin can * */*', '* can GET magazine/*', '* can GET page/*', '* can OPTIONS page/*'));
}

// store configuration, if necessary
if ($configuration->isChanged()) {
    $configuration->store();
}

// start HTML capture
ob_start();
?>

<!-- HTML -->
<h2>Settings</h2>
<form method="POST" action="configure.php">
    <fieldset>
        <legend>Reset</legend>
        <p>
            <a class="button" href="<?php echo WebUrl::create('configure.php?reset=1'); ?>">Restore default settings</a> This will overwrite any currently saved settings and return the configuration to its first-installed state.
        </p>
    </fieldset>
    <fieldset>
        <legend>Site</legend>
        <label for="title"  title="Enter the title to display for this site.">Site title:</label>
        <input id="title" name="title" type="text" value="<?php echo $configuration->title; ?>" title="Enter the title to display for this site." required="required"/>
        <label for="ghostcript_path"  title="Enter the path to the Ghostscript installation; for information on how to install Ghostscript, see README.txt">Path to Ghostscript</label>
        <input id="ghostscript_path" name="ghostscript_path" type="text" value="<?php echo $configuration->ghostscript_path; ?>" title="Enter the path to the Ghostscript installation; for information on how to install Ghostscript, see README.txt" /> 
        <input type="button" id="auto_find" class="button" value="Auto-find" />
    </fieldset>
    <fieldset>
        <legend>Administration</legend>
        <label for="admin_password"  title="Enter the new administrator password.">Enter the administrator password (if no password is entered, the password will not change):</label>
        <input id="admin_password"  name="admin_password" type="password" value="" title="Enter the new administrator password." /> 
        <label for="admin_password_confirmation" title="Confirm the new administrator password.">Confirm the administrator password:</label>
        <input id="admin_password_confirmation" name="admin_password_confirmation" type="password" value="" title="Confirm the new administrator password." /> 
    </fieldset>
    <p>
        <input type="submit" id="magazine-submit" class="button" value="Save settings"/>
    </p>
</form>

<!-- SCRIPT -->
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.min.js"></script>
<script type="text/javascript">
    $('#auto_find').on('click', function(e){
        e.preventDefault();
        e.stopPropagation();
        $.get("<?php echo WebUrl::create('locate.php'); ?>")
        .success(function(data){
            if(data){
                $('#ghostscript_path').val(data);
            }
            else{
                alert('Ghostscript could not be auto-located. Please enter the path manually.');
            }
        })
        .error(function(){
            alert('Ghostscript could not be auto-located. Please enter the path manually.');
        });
    });
</script>

<?php
// end capture
$content = ob_get_clean();

// templating
$template = new WebTemplate('site/templates/admin.php', WebTemplate::PHP_FILE);
$template->replace('title', 'Settings');
$template->replace('content', $content);
$template->display();
?>
