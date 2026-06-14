<?php

function is_valid_turma_num($n): bool {
    return in_array((string)$n, ['10', '11', '12'], true);
}

function is_valid_turma_letra($l): bool {
    return in_array(strtoupper((string)$l), ['A', 'B', 'C'], true);
}

function is_valid_turma($n, $l): bool {
    return is_valid_turma_num($n) && is_valid_turma_letra($l);
}

function is_valid_dia_semana($d): bool {
    return in_array((string)$d, ['1', '2', '3', '4', '5'], true);
}

function is_valid_hora($h): bool {
    return is_string($h) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $h) === 1;
}

function is_valid_mac($m): bool {
    return is_string($m) && preg_match('/^([0-9A-F]{2}:){5}[0-9A-F]{2}$/', $m) === 1;
}
