<?php

// Show errors
error_reporting(E_ALL | E_ERROR | E_PARSE);

// Load dependencies
require 'config.php';
require_once __DIR__ . '/vendor/autoload.php';

// Instance parser
$parser = new \cebe\markdown\GithubMarkdown();

// Check if is local
$local = false;

// Get route
$route = (filter_input(INPUT_GET, 'route') !== null)? filter_input(INPUT_GET, 'route') : 'index.html';
$route = str_replace('.html', '', $route);

// Get page contents
if ($route === 'index') {

    // Get API
    $api = json_decode(file_get_contents('posts.json'), true);
} else {

    // Get markdown file
    if (isset($route) && ($route !== '') && file_exists('../documents/' . $route . '.md')) {
        $markdown = file_get_contents('../documents/' . $route . '.md');
    }

    // Render page
    $html = $parser->parse($markdown);
}


?>
<!DOCTYPE HTML>
<html lang='en-US'>
<head>
<meta charset='utf-8'>

<meta http-equiv='X-UA-Compatible' content='IE=edge'>
<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
<meta name='viewport' content='width=device-width, initial-scale=1.0, user-scalable=no'>

<title>Laito</title>

<!-- Bootstrap -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.4/css/bootstrap.min.css" integrity="sha384-2hfp1SzUoho7/TsGGGDaFdsuuDL0LX2hnUp6VkX3CUQ2K4K+xjboZdsXyp4oUHZj" crossorigin="anonymous">

<!-- Font Awesome -->
<link href='//netdna.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css' rel='stylesheet'>

<!-- Google Fonts -->
<link href='https://fonts.googleapis.com/css?family=Open+Sans:400,700|Source+Sans+Pro:400,300' rel='stylesheet' type='text/css'>

<!-- Syntax Highlighter -->
<link rel="stylesheet" href="//highlightjs.org/static/demo/styles/github-gist.css">

<!-- Site -->
<link href='<?php if ($local): ?>../<?php endif; ?>assets/css/style.css' rel='stylesheet'>

<!-- JSON -->
<link href='<?php if ($local): ?>../<?php endif; ?>assets/css/jquery.jsonview.min.css' rel='stylesheet'>

</head>

<nav class="navbar navbar-dark navbar-fixed-top" style="background: #16a085">
    <div class="container">
        <a class="navbar-brand" href="/laito">
            <i class="fa fa-leaf fa-flip-horizontal" aria-hidden="true"></i>
            Laito
        </a>
        <ul class="nav navbar-nav pull-md-right">
            <li class="nav-item">
                <a class="nav-link" href="installation.html">Docs</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="https://github.com/codebri/laito" target="_blank">GitHub</a>
            </li>
        </ul>
    </div>
</nav>

<?php if ($route === 'index'): ?>
    <?php include 'home.php' ?>
<?php else: ?>
    <div class="container">
        <div class="row">
            <div class="col-md-3">
                <?php include 'sidebar.php' ?>
            </div>
            <div class="col-md-9">
                <div class="content">
                    <?=$html?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<footer>
    <div class="container">
        <span>Laito by <a href="http://codebri.com">Codebri</a></span>
    </div>
</footer>

<!-- Bootstrap -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.0.0/jquery.min.js" integrity="sha384-THPy051/pYDQGanwU6poAc/hOdQxjnOEXzbT+OuUAFqNqFjL+4IGLBgCJC3ZOShY" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tether/1.2.0/js/tether.min.js" integrity="sha384-Plbmg8JY28KFelvJVai01l8WyZzrYWG825m+cZ0eDDS1f7d/js6ikvy1+X+guPIB" crossorigin="anonymous"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.4/js/bootstrap.min.js" integrity="sha384-VjEeINv9OSwtWFLAtmc4JCtEJXXBub00gtSnszmspDLCtC0I4z4nqz7rEFbIZLLU" crossorigin="anonymous"></script>

<!-- Syntax Highlighter -->
<script src="//highlightjs.org/static/highlight.pack.js"></script>

<!-- Site -->
<script src="<?php if ($local): ?>../<?php endif; ?>assets/js/site.js"></script>

<!-- JSON -->
<script src="<?php if ($local): ?>../<?php endif; ?>assets/js/jquery.jsonview.min.js"></script>

</body>
</html>