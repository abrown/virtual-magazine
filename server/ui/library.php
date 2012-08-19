<!-- new magazine -->
<p>
    <a class="magazine-admin-button" href="<?php echo WebUrl::create('magazine?method=POST', false); ?>">New Magazine</a>
    <a class="magazine-admin-button" href="<?php echo WebUrl::create('social?method=PUT', false); ?>">Edit Social Settings</a>
    <a class="magazine-admin-button" href="<?php echo WebUrl::getDirectoryUrl() . 'api.php'; ?>">API Documentation</a>
</p>    

<!-- table -->
<table class="admin-table library">
    <tr class="head">
        <th>ID</th>
        <th>URL</th>
        <th>Links</th>
    </tr>
    <?php
    if (!@$data->items) {
        echo "<tr><td colspan='3'>No magazines found</td></tr>";
    } else {
        foreach (@$data->items as $item) {
            $_uri = htmlentities($item->getURI());
            echo "<tr><td class='{$_uri}#id'>{$item->getID()}</td>";
            $get = WebUrl::create($_uri, false);
            echo "<td class='{$_uri}#url'>$get</td>";
            echo "<td class='{$_uri}#links'>";
            echo "<a href='{$get}'>View</a> ";
            $put = WebUrl::create($_uri . '?method=PUT', false);
            echo "<a href='{$put}'>Edit</a> ";
            $delete = WebUrl::create($_uri . '?method=DELETE', false);
            echo "<a href='{$delete}'>Delete</a></td></tr>";
        }
    }
    ?>
</table>
