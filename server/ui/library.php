<!-- title -->
<h2>Magazine Library</h2>

<!-- buttons -->
<p>
    <a class="button" href="<?php echo WebUrl::createAnchoredUrl('magazine?method=POST', false); ?>">New Magazine</a>
    <a class="button" href="<?php echo WebUrl::getDirectoryUrl() . 'configure.php'; ?>">Edit Settings</a>
</p>
<p>
    
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
            $get = WebUrl::createAnchoredUrl($_uri, false);
            echo "<td class='{$_uri}#url'>$get</td>";
            echo "<td class='{$_uri}#links'>";
            echo "<a href='{$get}'>View</a> ";
            $put = WebUrl::createAnchoredUrl($_uri . '?method=PUT', false);
            echo "<a href='{$put}'>Edit</a> ";
            $delete = WebUrl::createAnchoredUrl($_uri . '?method=DELETE', false);
            echo "<a href='{$delete}'>Delete</a></td></tr>";
        }
    }
    ?>
</table>
