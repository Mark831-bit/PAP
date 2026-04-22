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
