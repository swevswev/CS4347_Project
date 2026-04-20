<?php
declare(strict_types=1);

function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/** @return int[] */
function int_list_from_post(mixed $arr): array
{
    if ($arr === null || $arr === '') {
        return [];
    }
    if (!is_array($arr)) {
        $arr = [$arr];
    }
    $out = [];
    foreach ($arr as $v) {
        if ($v === '' || $v === null) {
            continue;
        }
        if (is_numeric($v)) {
            $out[] = (int) $v;
        }
    }
    return $out;
}
