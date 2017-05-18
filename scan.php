<?php
header('Content-Type: text/html; charset=utf-8');
if (isset($_POST['path'])) {
    echo file_get_contents($_POST['path']);
    exit();
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Hunter :: FRANKIE MAKERS</title>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootswatch/3.3.7/superhero/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.7.0/styles/default.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.7.0/styles/railscasts.min.css" />
<style>
ul, li, pre {display: block; padding: 0; margin: 0; border: none;}
pre {font-size:12px; line-height: 1em;}
ul {margin-top: 30px;}
.data-link:hover span {color: #fff!important;}
</style>
</head>
<body style="margin: 20px;">
<?php
define('BASE_NAME', basename(__file__));
$search = ($_GET) ? stripslashes($_GET['q']) : '';
?>
<form class="pull-right" action="/scan.php" method="get">
    <div style="display: table;">
        <div style="display: table-cell; vertical-align: middle;">
            <input type="text" class="form-control" style="width: 320px;" name="q" value="<?php echo htmlspecialchars($search); ?>">
        </div>  
        <div style="display: table-cell; vertical-align: middle;">
            <input type="submit" class="btn btn-info" value="go">
        </div>  
    </div>
</form>
<?php
$droot = $_SERVER["DOCUMENT_ROOT"];
$files = new ArrayObject;
$defaultColor = '#ff0000';
$colors = array(
    '#ffff00',
    '#ffee00',
    '#ffdd00',
    '#ffcc00',
    '#ffbb00',
    '#ffaa00',
    '#ff8800',
    '#ff6600',
    '#ff4400',
    '#ff2200',
    '#ff0000');

$extensions = array(
    'php',
    'js',
    'css',
    'less',
    'scss',
    'tpl');

function printOutput($extension)
{
    global $files, $colors;
    if (isset($files[$extension]) && $files[$extension]) {
        $i = 0;
        echo '<ul>';
        echo '<li>' . strtoupper($extension) . ':</li>';
        foreach ($files[$extension] as $file) {
            echo '<li>';
            echo '<span class="data-link" data-path="' . $file['path'] . '">';
            foreach ($file['parts'] as $part) {
                $i++;
                if (!isset($colors[$i])) $colors[$i] = $defaultColor;
                echo '<span style="color:' . $colors[$i] . ';">/' . $part . '</span>';
            }

            $entries = '';
            foreach ($file['entries'] as $entry) $entries .= '<pre><code class="' . $extension . '">' . htmlentities($entry) . '</code></pre>';

            echo ' (<small>countEntries = </small>' . $file['count'] . '<small>, countLinesInFile = </small>' . $file['lines'] . ' )</span> <span class="open"><i class="fa fa-eye text-primary" aria-hidden="true"></i></span> <div class="entries" style="display:none;">' . $entries . '</div></li>';
            $i = 0;
        }
        echo '</ul>';
    }
}

function getLinesWithString($file, $str)
{
    $entries = array();
    foreach ($file as $lineNumber => $line) {
        $lineNumber++;
        if (strpos($line, $str) !== false) {
            $entries[] = $lineNumber . ' :: ' . $line;
        }
    }
    return $entries;
}

if ($search):
    $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__file__)), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($objects as $path => $object) {
        $info = pathinfo($path);
        $basename = $info['basename'];
        $extension = $info['extension'];
        if ($basename != BASE_NAME && in_array($extension, $extensions)) {
            if (filesize($path) < 1000000) {
                $source = file_get_contents($path);
                $file = file($path);
                if (strpos($source, $search) !== false) {
                    $_path = str_replace($_SERVER["DOCUMENT_ROOT"], '', $path);
                    $entries = getLinesWithString($file, $search);
                    $countLines = count($file);
                    $countEntries = substr_count($source, $search);
                    $parts = explode('/', trim($_path, '/'));
                    $files[$extension][] = array(
                        'count' => $countEntries,
                        'parts' => $parts,
                        'path' => $path,
                        'lines' => $countLines,
                        'entries' => $entries);
                }
            } else  echo '<div>' . $path . ' File size: <span style="color:red">' . filesize($path) . '</span> bytes!</div>';

        }
    }
    foreach ($extensions as $extension) printOutput($extension);
endif;
?>
<div class="text-center"><small>FRANKIE MAKERS :: Hunter - Substring hunting</small></div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.2.5/ace.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.2.5/mode-php.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.2.5/mode-javascript.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.2.5/mode-css.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.2.5/mode-less.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.2.5/theme-idle_fingers.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.2.5/ext-beautify.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.7.0/highlight.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.7.0/languages/php.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.7.0/languages/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.7.0/languages/css.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.7.0/languages/less.min.js"></script>
<script>hljs.initHighlightingOnLoad();</script>
<script type="text/javascript">
var i = 0;
$('.data-link').each(function() {
    i++;      
      
    $(this).parent().find('.open').css('cursor', 'pointer').click(function() {
        $(this).next().toggle();
    });    
    
    $(this).css('cursor', 'pointer').click(function() {            
        $this = $(this);
        if ($this.hasClass('lock')) { 
            $this.parent().next().toggle();
            return; 
        }     
        $this.addClass('lock');
        $this.parent().after('<textarea id="editor' + i + '"></textarea>');    
        var editor = ace.edit("editor" + i);
        editor.setTheme("ace/theme/idle_fingers"); 
        editor.setOptions({ maxLines: Infinity });
                 
        var PHPMode = ace.require("ace/mode/php").Mode;    
        var JSMode = ace.require("ace/mode/javascript").Mode;    
        var CSSMode = ace.require("ace/mode/css").Mode;
        var LESSMode = ace.require("ace/mode/less").Mode;
        
        editor.session.setMode(new PHPMode());            
                                                                  
       	$.ajax({
            type: "POST",
            url: "scan.php",
            data: { path: $this.attr('data-path') }
        }).done(function( data ) {
            editor.setValue(data);
        });                
    });       
});
</script>
</body>
</html>
