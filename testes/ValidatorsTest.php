<?php

use PHPUnit\Framework\TestCase;

/**
 * Testes para api/lib/validators.php
 * Funções: is_valid_turma_num, is_valid_turma_letra, is_valid_turma
 */
class ValidatorsTest extends TestCase
{
    // ── is_valid_turma_num ────────────────────────────────────

    public function test_turma_num_10_valido(): void
    {
        $this->assertTrue(is_valid_turma_num('10'));
    }

    public function test_turma_num_11_valido(): void
    {
        $this->assertTrue(is_valid_turma_num('11'));
    }

    public function test_turma_num_12_valido(): void
    {
        $this->assertTrue(is_valid_turma_num('12'));
    }

    public function test_turma_num_9_invalido(): void
    {
        $this->assertFalse(is_valid_turma_num('9'));
    }

    public function test_turma_num_13_invalido(): void
    {
        $this->assertFalse(is_valid_turma_num('13'));
    }

    public function test_turma_num_zero_invalido(): void
    {
        $this->assertFalse(is_valid_turma_num('0'));
    }

    public function test_turma_num_string_vazia_invalido(): void
    {
        $this->assertFalse(is_valid_turma_num(''));
    }

    public function test_turma_num_letra_invalido(): void
    {
        $this->assertFalse(is_valid_turma_num('abc'));
    }

    public function test_turma_num_int_10_valido(): void
    {
        // Aceita inteiro porque a função faz cast (string)
        $this->assertTrue(is_valid_turma_num(10));
    }

    // ── is_valid_turma_letra ──────────────────────────────────

    public function test_turma_letra_A_valida(): void
    {
        $this->assertTrue(is_valid_turma_letra('A'));
    }

    public function test_turma_letra_B_valida(): void
    {
        $this->assertTrue(is_valid_turma_letra('B'));
    }

    public function test_turma_letra_C_valida(): void
    {
        $this->assertTrue(is_valid_turma_letra('C'));
    }

    public function test_turma_letra_minuscula_aceita(): void
    {
        // A função faz strtoupper() — aceita minúsculas
        $this->assertTrue(is_valid_turma_letra('a'));
    }

    public function test_turma_letra_D_invalida(): void
    {
        $this->assertFalse(is_valid_turma_letra('D'));
    }

    public function test_turma_letra_vazia_invalida(): void
    {
        $this->assertFalse(is_valid_turma_letra(''));
    }

    public function test_turma_letra_numero_invalida(): void
    {
        $this->assertFalse(is_valid_turma_letra('1'));
    }

    // ── is_valid_turma (composição) ───────────────────────────

    public function test_turma_10A_valida(): void
    {
        $this->assertTrue(is_valid_turma('10', 'A'));
    }

    public function test_turma_12C_valida(): void
    {
        $this->assertTrue(is_valid_turma('12', 'C'));
    }

    public function test_turma_9A_invalida(): void
    {
        $this->assertFalse(is_valid_turma('9', 'A'));
    }

    public function test_turma_10D_invalida(): void
    {
        $this->assertFalse(is_valid_turma('10', 'D'));
    }

    public function test_turma_ambos_vazios_invalida(): void
    {
        $this->assertFalse(is_valid_turma('', ''));
    }
}
