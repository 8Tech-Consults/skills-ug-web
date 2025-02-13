<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Test CV</title>
    <style>
        .container { max-width: 800px; margin: auto; overflow: hidden; }
        .box1 { width: 50%; float: left; background-color: #eee; padding: 20px; }
        .box2 { width: 50%; float: left; background-color: #f0f0f0; padding: 20px; }
        .clearfix:before, .clearfix:after { content: " "; display: table; } .clearfix:after { clear: both; } .clearfix { *zoom: 1; }
    </style>
</head>
<body class="container clearfix">
    <div class="box1">Box 1 Content - Block Level Test</div>
    <div class="box2">Box 2 Content - Block Level Test</div>
    <p>Paragraph after floats - should be below</p>
</body>
</html>