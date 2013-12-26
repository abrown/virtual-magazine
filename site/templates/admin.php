<!DOCTYPE hmtl>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Administration: <template:title/></title>
        <link rel="stylesheet" type="text/css" href="<?php echo WebUrl::create('site/style/reset.css'); ?>" />
        <link rel="stylesheet" type="text/css" href="<?php echo WebUrl::create('site/style/main.css'); ?>" />
        <link rel="stylesheet" type="text/css" href="<?php echo WebUrl::create('site/style/admin.css'); ?>" />
    </head>
    <body>
        <!-- header -->
        <div class="header">
            <div class="navigation">
                <div class="accounts">
                    <a href="<?php echo WebUrl::getDirectoryUrl() . 'configure.php'; ?>">Settings</a> |
                    <a href="<?php echo WebUrl::create('index.php'); ?>"><img src="<?php echo WebUrl::create('site/images/login.png'); ?>" alt="" /></a>
                    <a href="<?php echo WebUrl::create('index.php'); ?>"> Logout</a>
                </div>
            </div>
            <a href="<?php echo WebUrl::create('service.php/library'); ?>"><h1>Administration</h1></a>
        </div>

        <!-- main content -->
        <div class="content">
            <template:content/>
        </div>

        <?php
        // insert footer
        require get_vm_dir() . '/site/templates/footer.php';
        ?>  
    </body>
</html>