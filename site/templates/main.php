<?php
$configuration = new Settings(get_base_dir() . '/../configuration.json');
?>
<!DOCTYPE hmtl>
<html>
    <head>
        <meta charset="UTF-8">
        <title><template:title/></title>
        <link rel="stylesheet" type="text/css" href="site/style/reset.css" />
        <link rel="stylesheet" type="text/css" href="site/style/main.css" />
    </head>
    <body>
        <!-- header -->
        <div class="header">
            <div class="navigation">
                <form action="#">
                    <input type="text" name="q" placeholder="Search Here" />
                    <button type="submit">
                        <img src="site/images/search.png" alt="Search" />
                    </button>
                </form>
                <div class="accounts">
                    <a href="<?php echo WebUrl::create('service.php/library'); ?>"><img src="site/images/login.png" alt="" /></a>
                    <a href="<?php echo WebUrl::create('service.php/library'); ?>"> Login</a>
                </div>
            </div>
            <a href="/"><h1><template:title/></h1></a>
        </div>

        <!-- main content -->
        <div class="content">
            <template:content/>
        </div>

        <?php
        // insert footer
        require get_base_dir() . '/../../site/templates/footer.php';
        ?>      
    </body>
</html>