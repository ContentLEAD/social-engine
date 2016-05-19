<html>
    <head>
        <script src="/js/adrum.js"></script>
        <!--BOOTSTRAP-->
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css">
        <link rel="stylesheet" href="/css/style.css?v=1">
        <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">
        <link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
        <link rel="stylesheet" href="/css/sweetalert.css">
        <link rel="stylesheet" href="/css/jquery.fancybox.css" type="text/css" media="screen" />
        <title> | B | SOCIAL</title>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script>
        <script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
        <script src="https://code.highcharts.com/highcharts.js"></script>
        <script src="https://code.highcharts.com/highcharts-more.js"></script>
        <script src="https://code.highcharts.com/modules/exporting.js"></script>
        <script src="https://code.highcharts.com/modules/offline-exporting.js"></script>
        <script src="https://code.highcharts.com/modules/solid-gauge.js"></script>
        <script type="text/javascript" src="/js/jquery.fancybox.js"></script>
        <script src="/js/sweetalert.min.js"></script>
        <script src="/js/se.js"></script>
        <script src="/js/sego.js"></script>
        <script src="https://use.typekit.net/odr1dhg.js"></script>
        <script>try{Typekit.load({ async: true });}catch(e){}</script>
        <script src="https://use.typekit.net/woo5yta.js"></script>
        <script>try{Typekit.load({ async: true });}catch(e){}</script>
    </head>
    <body>
        <?php if($is_admin) :?>
        <div id="top" style="background-color:skyblue;">
        <?php else :?>
        <div id="top">
        <?php endif; ?>
            <div class="container">
                <div class="col-md-4"><a href="/"><img src="/images/logo.png" style="width:100px;height:auto;"></a></div>
                <div class="col-md-8 text-right">
                    <a href="javascript:void(0);" class="user">
                        <?=$gbl_first_name?>
                    </a>
                </div>
            </div>
        </div>
        <div id="top-sub">
            <div class="container">
                    <div class="col-md-3 col-md-offset-9 user-options">
                        <a href="/index.php/gate/logout">Log Out</a>
                            <?php if($is_admin) :?>
                            <a href="/index.php/dashboard/index/admin">Dashboard</a>
                            <a href="/index.php/board/">Toggle Admin</a>
                            <?php else :?>
                            <a href="/index.php/dashboard/index/">Dashboard</a>
                            <a href="/index.php/board/index/admin">Toggle Admin</a>
                            <?php endif ;?>
                        <div style="clear:both;"></div>
                    </div>
            </div>
        </div>