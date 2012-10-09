<?php
$configuration = new Settings(get_base_dir().'/../configuration.json');
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
                    <input type="text" name="q" placeholder="Search Here" /><button type="submit"><img src="site/images/search.png" alt="Search" /></button>
                </form>
                <div class="accounts">
                    <a href="/service.php/library"><img src="site/images/login.png" alt="" /></a>
                    <a href="/service.php/library"> Login</a>
                </div>
            </div>
            <a href="/"><h1><template:title/></h1></a>
        </div>

        <!-- main content -->
        <div class="content">
            <template:content/>
        </div>

        <!-- footer -->
        <div class="footer">
            <table>
                <tr>
                    <th><?php echo $configuration->get('title'); ?></th>
                    <th>Business</th>
                    <th>Developers</th>
                    <th>Get Social With Us</th>
                </tr>
                <tr class="first-row">
                    <td><a href="#">About</a></td>
                    <td><a href="#">Overview</a></td>
                    <td><a href="#">Overview</a></td>
                    <td>
                        <a href="http://www.facebook.com"><img src="site/images/facebook.png" alt="" /></a> &nbsp;
                        <a href="http://www.facebook.com">Follow us on Facebook</a>
                    </td>
                </tr>
                <tr>
                    <td><a href="#">Careers</a></td>
                    <td><a href="#">Advertising</a></td>
                    <td><a href="#">Customization</a></td>
                    <td>
                        <a href="http://www.twitter.com"><img src="site/images/twitter.png" alt="" /></a> &nbsp;
                        <a href="http://www.twitter.com">Follow us on Twitter</a>
                    </td>
                </tr>
                <tr>
                    <td><a href="#">Press</a></td>
                    <td><a href="#">Support</a></td>
                    <td> </td>
                    <td>
                        <a href="http://www.pinterest.com"><img src="site/images/pinterest.png" alt="" /></a> &nbsp;
                        <a href="http://www.pinterest.com">Follow us on Pinterest</a>
                    </td>
                </tr>
                <tr>
                    <td><a href="#">Publications</a></td>
                    <td> </td>
                    <td> </td>
                    <td> </td>
                </tr>
                <tr class="last-row">
                    <td><a href="#">Upload</a></td>
                    <td> </td>
                    <td> </td>
                    <td> </td>
                </tr>
            </table>
            <div class="copyright">Copyright &copy; 2012 <?php echo $configuration->get('title'); ?></div>
        </div>
    </body>
</html>