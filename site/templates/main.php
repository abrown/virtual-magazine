<!DOCTYPE hmtl>
<html>
    <head>
        <meta charset="UTF-8">
        <title><template:title/></title>
        <link rel="stylesheet" type="text/css" href="<?php echo WebUrl::create('site/style/reset.css'); ?>" />
        <link rel="stylesheet" type="text/css" href="<?php echo WebUrl::create('site/style/main.css'); ?>" />
    </head>
    <body>
        <!-- header -->
        <div class="header">
            <div class="navigation">
                <!--<form action="#">
                    <input type="text" name="q" placeholder="Search Here" />
                    <button type="submit">
                        <img src="<?php echo WebUrl::create('site/images/search.png'); ?>" alt="Search" />
                    </button>
                </form>-->
                <div class="accounts">
                    <a href="<?php echo WebUrl::create('service.php/library'); ?>"><img src="<?php echo WebUrl::create('site/images/login.png'); ?>" alt="" /></a>
                    <a href="<?php echo WebUrl::create('service.php/library'); ?>"> Edit Magazines</a>
                </div>
            </div>
            <a href="<?php echo WebUrl::create('index.php'); ?>"><h1><template:title/></h1></a>
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