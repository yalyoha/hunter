<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>scan.php</title>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootswatch/3.3.7/superhero/bootstrap.min.css">
</head>
<body style="margin: 20px;">
<?php
$search = ($_GET) ? stripslashes($_GET['q']) : '';
?>
<form style="float: right;" action="/scan.php" method="get">
    <div style="display: table;">
        <div style="display: table-cell; vertical-align: middle;">
            <input type="text" class="form-control" style="width: 320px;" name="q" value="<?php echo htmlspecialchars($search);?>">
        </div>  
        <div style="display: table-cell; vertical-align: middle;">
            <input type="submit" class="btn btn-info" value="поиск">
        </div>  
    </div>
</form>
<?php
$extensions = array('php', 'js', 'css', 'less', 'sccs', 'tpl');

$droot = $_SERVER["DOCUMENT_ROOT"];
$files = new ArrayObject;
$colors = array(
    '#ff0000',
    '#ff2200',
    '#ff4400',
    '#ff6600',
    '#ff8800',
    '#ffaa00',
    '#ffbb00',
    '#ffcc00',
    '#ffdd00',
    '#ffee00',
    '#ffff00');
$defaultColor = '#ffffff';

function printOutput($extension) {
    global $files, $colors;
    if (isset($files[$extension]) && $files[$extension]) {
        $i = 0;
        echo strtoupper($extension) . ':<br>';
        foreach ($files[$extension] as $file) {
            foreach ($file['parts'] as $part) {
                $i++;
                if (!isset($colors[$i])) $colors[$i] = $defaultColor;
                echo '<span style="color:' . $colors[$i] . ';">/' . $part . '</span>';
            }
            echo ' (' . $file['count'] . ')<br>';
            $i = 0;
        }
        echo '<br>';
    }
}

if ($search):
    $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__file__)), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($objects as $path => $object) {
        $info = pathinfo($path);
        $basename = $info['basename'];
        $extension = $info['extension'];
        if ($basename != 'scan.php' && in_array($extension, $extensions)) {
            $source = file_get_contents($path);
            if (strpos($source, $search) !== false) {
                $_path = str_replace($_SERVER["DOCUMENT_ROOT"], '', $path);
                $count = substr_count($source, $search);
                $parts = explode('/', trim($_path, '/'));
                $files[$extension][] = array('count' => $count, 'parts' => $parts);
            }
        }
    }
    foreach ($extensions as $extension) printOutput($extension); 

endif;
?>
<div class="text-center">FRANKIE MAKERS :: Hunter - Substring hunting</div>
</body>
</html>
