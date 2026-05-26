<?php
// Bootstrap para PHPUnit — carregado antes de cada teste.
// Apenas inclui ficheiros que NÃO dependem de sessão/BD.
// Os testes que precisam de sessão devem fazer require_once próprio.

require_once __DIR__ . '/../api/lib/validators.php';
