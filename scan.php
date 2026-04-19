<?php
/* ============================================================
 *  HTTP Basic Authentication
 * ============================================================ */
define('HUNTER_USER', 'admin');
define('HUNTER_PASS', 'nimda');
define('HUNTER_REALM', 'Hunter');

(function () {
    $user = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : null;
    $pass = isset($_SERVER['PHP_AUTH_PW'])   ? $_SERVER['PHP_AUTH_PW']   : null;

    // FastCGI / некоторые конфиги Apache не заполняют PHP_AUTH_*,
    // зато прокидывают исходный заголовок. Достаём оттуда вручную.
    if ($user === null || $pass === null) {
        $auth = null;
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $auth = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $auth = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        } elseif (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (isset($headers['Authorization']))      $auth = $headers['Authorization'];
            elseif (isset($headers['authorization']))  $auth = $headers['authorization'];
        }
        if ($auth && stripos($auth, 'Basic ') === 0) {
            $decoded = base64_decode(substr($auth, 6));
            if ($decoded !== false && strpos($decoded, ':') !== false) {
                list($user, $pass) = explode(':', $decoded, 2);
            }
        }
    }

    $okUser = is_string($user) && hash_equals(HUNTER_USER, $user);
    $okPass = is_string($pass) && hash_equals(HUNTER_PASS, $pass);

    if (!($okUser && $okPass)) {
        header('WWW-Authenticate: Basic realm="' . HUNTER_REALM . '", charset="UTF-8"');
        header('HTTP/1.0 401 Unauthorized');
        header('Content-Type: text/plain; charset=utf-8');
        echo "401 Unauthorized\n";
        exit();
    }
})();

header('Content-Type: text/html; charset=utf-8');

/* ============================================================
 *  AJAX: сохранение файла
 *  POST: action=save, path, content
 *  Перед записью копируем оригинал в <file>.bak (перезаписывая
 *  предыдущий .bak). Разрешаем писать только внутри DOCUMENT_ROOT.
 * ============================================================ */
if (isset($_POST['action']) && $_POST['action'] === 'save') {
    header('Content-Type: application/json; charset=utf-8');

    $path    = isset($_POST['path']) ? $_POST['path'] : '';
    $content = isset($_POST['content']) ? $_POST['content'] : '';

    $response = array('ok' => false);

    $realDocRoot = realpath($_SERVER['DOCUMENT_ROOT']);
    $realPath    = realpath($path);

    if (!$realPath || !$realDocRoot || strpos($realPath, $realDocRoot) !== 0) {
        $response['error'] = 'Path is outside of DOCUMENT_ROOT';
        echo json_encode($response);
        exit();
    }
    if (!is_file($realPath)) {
        $response['error'] = 'File does not exist';
        echo json_encode($response);
        exit();
    }
    if (!is_writable($realPath)) {
        $response['error'] = 'File is not writable';
        echo json_encode($response);
        exit();
    }

    $bakPath = $realPath . '.bak';
    if (!@copy($realPath, $bakPath)) {
        $response['error'] = 'Failed to create backup (' . $bakPath . ')';
        echo json_encode($response);
        exit();
    }

    // нормализуем CRLF -> LF (Ace отдаёт \n, оставим как есть)
    $bytes = @file_put_contents($realPath, $content);
    if ($bytes === false) {
        $response['error'] = 'Failed to write file';
        echo json_encode($response);
        exit();
    }

    $response['ok']     = true;
    $response['bytes']  = $bytes;
    $response['backup'] = basename($bakPath);
    $response['time']   = date('H:i:s');
    echo json_encode($response);
    exit();
}

/* ============================================================
 *  AJAX: чтение файла
 * ============================================================ */
if (isset($_POST['path'])) {
    echo file_get_contents($_POST['path']);
    exit();
}
?>
<!doctype html>
<html lang="en">
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
        .editor-wrap {margin: 4px 0 14px;}
        .editor-toolbar {padding: 4px 0; display: flex; align-items: center; gap: 10px;}
        .editor-toolbar .save-status {font-size: 12px; opacity: 0.85;}
        .editor-toolbar .save-status.ok    {color: #5cb85c;}
        .editor-toolbar .save-status.err   {color: #d9534f;}
        .editor-toolbar .save-status.busy  {color: #f0ad4e;}
        .editor-toolbar .lang-tag {font-size: 11px; opacity: 0.6; text-transform: uppercase;}
    </style>
</head>
<body style="margin: 20px;">
<?php
define('BASE_NAME', basename(__file__));
$search = ($_GET) ? stripslashes($_GET['q']) : '';
?>
<form class="pull-right" action="" method="get">
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
    'tpl',
    'html',
    'xml',
    'twig');

function printOutput($extension)
{
    global $files, $colors, $defaultColor;
    if (isset($files[$extension]) && $files[$extension]) {
        $i = 0;
        echo '<ul>';
        echo '<li>' . strtoupper($extension) . ':</li>';
        foreach ($files[$extension] as $file) {
            echo '<li>';
            echo '<span class="data-link" data-path="' . $file['path'] . '" data-ext="' . $extension . '">';
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
    $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($_SERVER['DOCUMENT_ROOT']), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($objects as $path => $object) {
        $info = pathinfo($path);
        $basename = $info['basename'];
        $extension = isset($info['extension']) ? $info['extension'] : '';
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
<div class="text-center"><small>FRANKIEMAKERS :: Hunter - Substring hunting</small></div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.2.5/ace.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.2.5/mode-php.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.2.5/mode-javascript.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.2.5/mode-css.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.2.5/mode-less.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.2.5/mode-scss.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.2.5/mode-html.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.2.5/mode-xml.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.2.5/mode-twig.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.2.5/mode-smarty.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.2.5/theme-idle_fingers.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.2.5/ext-beautify.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.7.0/highlight.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.7.0/languages/php.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.7.0/languages/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.7.0/languages/css.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.7.0/languages/less.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.7.0/languages/scss.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.7.0/languages/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.7.0/languages/twig.min.js"></script>
<script>hljs.initHighlightingOnLoad();</script>
<script type="text/javascript">
    // ext -> Ace mode
    var ACE_MODE_BY_EXT = {
        php:  'php',
        js:   'javascript',
        css:  'css',
        less: 'less',
        scss: 'scss',
        tpl:  'smarty',
        html: 'html',
        xml:  'xml',
        twig: 'twig'
    };

    function getExtFromPath(path) {
        var m = String(path).match(/\.([a-z0-9]+)$/i);
        return m ? m[1].toLowerCase() : '';
    }

    function applyAceMode(editor, ext) {
        var modeName = ACE_MODE_BY_EXT[ext] || 'text';
        try {
            var Mode = ace.require("ace/mode/" + modeName).Mode;
            editor.session.setMode(new Mode());
        } catch (e) {
            // если режим не подгружен — оставим plain text
            try {
                var TextMode = ace.require("ace/mode/text").Mode;
                editor.session.setMode(new TextMode());
            } catch (_) {}
        }
    }

    function setStatus($status, kind, text) {
        $status.removeClass('ok err busy').addClass(kind).text(text);
    }

    function saveFile($status, $btn, path, editor) {
        setStatus($status, 'busy', 'Saving...');
        $btn.prop('disabled', true);
        $.ajax({
            type: 'POST',
            url:  'scan.php',
            data: {
                action:  'save',
                path:    path,
                content: editor.getValue()
            },
            dataType: 'json'
        }).done(function (resp) {
            if (resp && resp.ok) {
                setStatus($status, 'ok',
                    'Saved ' + resp.bytes + ' bytes (backup: ' + resp.backup + ') @ ' + resp.time);
            } else {
                setStatus($status, 'err', 'Error: ' + ((resp && resp.error) || 'unknown'));
            }
        }).fail(function (xhr) {
            setStatus($status, 'err', 'HTTP ' + xhr.status + ' ' + xhr.statusText);
        }).always(function () {
            $btn.prop('disabled', false);
        });
    }

    $('.data-link').each(function (idx) {
        var i = idx + 1;
        var $link = $(this);

        $link.parent().find('.open').css('cursor', 'pointer').click(function () {
            $(this).next().toggle();
        });

        $link.css('cursor', 'pointer').click(function () {
            var $this = $(this);
            if ($this.hasClass('lock')) {
                $this.parent().next('.editor-wrap').toggle();
                return;
            }
            $this.addClass('lock');

            var path = $this.attr('data-path');
            var ext  = ($this.attr('data-ext') || getExtFromPath(path)).toLowerCase();
            var editorId = 'editor' + i;

            var wrapHtml =
                '<div class="editor-wrap">' +
                    '<div id="' + editorId + '" style="min-height: 120px;"></div>' +
                    '<div class="editor-toolbar">' +
                        '<button type="button" class="btn btn-success btn-xs save-btn">' +
                            '<i class="fa fa-floppy-o"></i> Save' +
                        '</button>' +
                        '<span class="lang-tag">' + (ACE_MODE_BY_EXT[ext] || 'text') + '</span>' +
                        '<span class="save-status">Ctrl+S to save \u2014 a .bak will be created next to the file</span>' +
                    '</div>' +
                '</div>';

            $this.parent().after(wrapHtml);

            var $wrap   = $this.parent().next('.editor-wrap');
            var $btn    = $wrap.find('.save-btn');
            var $status = $wrap.find('.save-status');

            var editor = ace.edit(editorId);
            editor.setTheme("ace/theme/idle_fingers");
            editor.setOptions({ maxLines: Infinity });
            applyAceMode(editor, ext);

            // Ctrl+S / Cmd+S
            editor.commands.addCommand({
                name: 'saveFile',
                bindKey: { win: 'Ctrl-S', mac: 'Command-S' },
                exec: function () { saveFile($status, $btn, path, editor); }
            });

            $btn.click(function () { saveFile($status, $btn, path, editor); });

            $.ajax({
                type: "POST",
                url:  "scan.php",
                data: { path: path }
            }).done(function (data) {
                editor.setValue(data, -1); // -1 ставит курсор в начало без выделения
            }).fail(function (xhr) {
                setStatus($status, 'err', 'Load failed: HTTP ' + xhr.status);
            });
        });
    });
</script>
</body>
</html>
