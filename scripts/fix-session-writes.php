<?php
function findStatementEnd(string $code, int $exprStart): ?int
{
    $depth = 0;
    $len = strlen($code);
    $inSingle = false;
    $inDouble = false;
    $escaped = false;

    for ($i = $exprStart; $i < $len; $i++) {
        $ch = $code[$i];

        if ($escaped) {
            $escaped = false;
            continue;
        }

        if ($inSingle) {
            if ($ch === '\\') {
                $escaped = true;
            } elseif ($ch === "'") {
                $inSingle = false;
            }
            continue;
        }

        if ($inDouble) {
            if ($ch === '\\') {
                $escaped = true;
            } elseif ($ch === '"') {
                $inDouble = false;
            }
            continue;
        }

        if ($ch === "'") {
            $inSingle = true;
            continue;
        }
        if ($ch === '"') {
            $inDouble = true;
            continue;
        }
        if ($ch === '(' || $ch === '[') {
            $depth++;
        } elseif ($ch === ')' || $ch === ']') {
            $depth--;
        } elseif ($ch === ';' && $depth === 0) {
            return $i;
        }
    }

    return null;
}

function transformFlashAssignments(string $code): string
{
    $keys = ['flash_success', 'flash_error'];
    foreach ($keys as $key) {
        $needle = "\$_SESSION['{$key}'] = ";
        $offset = 0;
        while (($pos = strpos($code, $needle, $offset)) !== false) {
            $exprStart = $pos + strlen($needle);
            $exprEnd = findStatementEnd($code, $exprStart);
            if ($exprEnd === null) {
                break;
            }
            $expr = substr($code, $exprStart, $exprEnd - $exprStart);
            $replacement = "Auth::setSession('{$key}', {$expr});";
            $code = substr($code, 0, $pos) . $replacement . substr($code, $exprEnd + 1);
            $offset = $pos + strlen($replacement);
        }
    }

    $consume = '/\$flashSuccess = \$_SESSION\[\'flash_success\'\] \?\? null;\s*\$flashError = \$_SESSION\[\'flash_error\'\] \?\? null;\s*unset\(\$_SESSION\[\'flash_success\'\], \$_SESSION\[\'flash_error\'\]\);/';
    $code = preg_replace($consume, "['success' => \$flashSuccess, 'error' => \$flashError] = Auth::consumeFlash();", $code);

    return $code;
}

$root = dirname(__DIR__) . '/php-app/app';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }
    $path = $file->getPathname();
    $code = file_get_contents($path);
    $fixed = transformFlashAssignments($code);
    if ($fixed !== $code) {
        file_put_contents($path, $fixed);
        echo $path, PHP_EOL;
    }
}
