<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['login']) || ($_SESSION['role'] ?? '') !== 'Aluno') {
    header("Location: /PAP/project/public/index.php");
    exit;
}

$login = $_SESSION['login'];

// ── Dados do aluno ─────────────────────────────────────
$stmt = $conn->prepare("SELECT Nome, CONCAT(turma_num, turma_letra) AS Turma, turma_num, turma_letra, foto FROM alunos WHERE login = ? LIMIT 1");
$stmt->bind_param("s", $login);
$stmt->execute();
$aluno = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$aluno) {
    http_response_code(404); echo "Aluno não encontrado."; exit;
}

$nome       = $aluno['Nome'];
$turma      = $aluno['Turma'];
$turmaNum   = (int)$aluno['turma_num'];
$turmaLetra = $aluno['turma_letra'];
$fotoFile   = $aluno['foto'] ?? '';
$fotoUrl    = ($fotoFile && file_exists(__DIR__ . '/../../uploads/fotos/' . $fotoFile))
              ? '/PAP/uploads/fotos/' . rawurlencode($fotoFile)
              : null;

// ── Presenças ──────────────────────────────────────────
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM presencas WHERE login = ? AND presenca = 1");
$stmt->bind_param("s", $login);
$stmt->execute();
$diasPresente = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
$stmt->close();

// ── Notas ──────────────────────────────────────────────
$stmt = $conn->prepare("
    SELECT n.materia, n.tipo, n.valor, n.data, n.observacao,
           p.Nome AS professor_nome
    FROM notas n
    LEFT JOIN professores p ON p.login = n.professor_login
    WHERE n.login_aluno = ?
    ORDER BY n.materia, n.data
");
$stmt->bind_param("s", $login);
$stmt->execute();
$notasRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Agrupar por matéria e calcular médias
$notasPorMateria = [];
foreach ($notasRows as $n) {
    $notasPorMateria[$n['materia']][] = (float)$n['valor'];
}
$mediaGeral = 0;
if (count($notasRows) > 0) {
    $mediaGeral = array_sum(array_column($notasRows, 'valor')) / count($notasRows);
}

// ── Próximos testes ────────────────────────────────────
$stmt = $conn->prepare("
    SELECT t.titulo, t.descricao, t.data_teste, t.materia,
           p.Nome AS professor_nome
    FROM testes t
    LEFT JOIN professores p ON p.login = t.professor_login
    WHERE t.turma_num = ? AND t.turma_letra = ? AND t.data_teste >= CURDATE()
    ORDER BY t.data_teste
");
$stmt->bind_param("is", $turmaNum, $turmaLetra);
$stmt->execute();
$testes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ── Horário ────────────────────────────────────────────
$stmt = $conn->prepare("
    SELECT dia_semana, hora_inicio, hora_fim, materia, sala
    FROM horario
    WHERE turma_num = ? AND turma_letra = ?
    ORDER BY dia_semana, hora_inicio
");
$stmt->bind_param("is", $turmaNum, $turmaLetra);
$stmt->execute();
$horarioRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$diasNome = [1 => 'Segunda', 2 => 'Terça', 3 => 'Quarta', 4 => 'Quinta', 5 => 'Sexta'];
$horarioPorDia = [];
foreach ($horarioRows as $h) {
    $horarioPorDia[(int)$h['dia_semana']][] = $h;
}

$dataGeracao = date('d/m/Y \à\s H:i');
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Perfil — <?= htmlspecialchars($nome) ?></title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, sans-serif; font-size: 13px; color: #111; background: #fff; padding: 24px; }

    /* ── Header ── */
    .pdf-header {
      display: flex; align-items: center; justify-content: space-between;
      border-bottom: 3px solid #0b1b3a; padding-bottom: 12px; margin-bottom: 20px;
    }
    .pdf-header-left { display: flex; align-items: center; gap: 14px; }
    .pdf-header-logo { width: 60px; height: 60px; object-fit: contain; }
    .pdf-header-title { font-size: 20px; font-weight: bold; color: #0b1b3a; }
    .pdf-header-sub { font-size: 11px; color: #6b7280; margin-top: 2px; }
    .pdf-date { font-size: 11px; color: #6b7280; text-align: right; }

    /* ── Student info ── */
    .student-info {
      display: flex; align-items: center; gap: 20px;
      background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 10px;
      padding: 16px; margin-bottom: 20px;
    }
    .student-avatar {
      width: 72px; height: 72px; border-radius: 50%; object-fit: cover;
      border: 3px solid #e5e7eb; flex-shrink: 0;
    }
    .student-avatar-placeholder {
      width: 72px; height: 72px; border-radius: 50%;
      background: #e5e7eb; display: flex; align-items: center;
      justify-content: center; font-size: 32px; flex-shrink: 0;
    }
    .student-info-text h2 { font-size: 18px; color: #0b1b3a; margin-bottom: 4px; }
    .student-info-text p { font-size: 12px; color: #6b7280; line-height: 1.7; }

    /* ── Sections ── */
    .pdf-section { margin-bottom: 22px; }
    .pdf-section-title {
      font-size: 13px; font-weight: bold; text-transform: uppercase;
      letter-spacing: 0.06em; color: #0b1b3a;
      border-bottom: 2px solid #0b1b3a; padding-bottom: 4px; margin-bottom: 12px;
    }

    /* ── Notas table ── */
    .notas-table { width: 100%; border-collapse: collapse; font-size: 12px; }
    .notas-table th {
      background: #0b1b3a; color: #fff; padding: 6px 10px; text-align: left;
    }
    .notas-table td { padding: 5px 10px; border-bottom: 1px solid #e5e7eb; }
    .notas-table tr:nth-child(even) td { background: #f8fafc; }
    .notas-media-row td { font-weight: bold; background: #eff6ff !important; color: #0b1b3a; }
    .nota-valor { font-weight: bold; }
    .media-geral-box {
      display: inline-block; margin-top: 8px; padding: 6px 16px;
      background: #0b1b3a; color: #fff; border-radius: 6px; font-size: 13px; font-weight: bold;
    }

    /* ── Testes ── */
    .teste-item {
      padding: 8px 12px; border-left: 3px solid #0b1b3a;
      margin-bottom: 8px; background: #f8fafc;
    }
    .teste-item-title { font-weight: bold; font-size: 12px; }
    .teste-item-meta { font-size: 11px; color: #6b7280; margin-top: 2px; }

    /* ── Horário ── */
    .horario-table { width: 100%; border-collapse: collapse; font-size: 11px; }
    .horario-table th {
      background: #0b1b3a; color: #fff; padding: 6px; text-align: center; width: 20%;
    }
    .horario-table td { padding: 5px 6px; border: 1px solid #e5e7eb; vertical-align: top; min-height: 30px; }
    .horario-aula { margin-bottom: 4px; }
    .horario-aula-time { font-size: 10px; color: #6b7280; }
    .horario-aula-materia { font-weight: bold; font-size: 11px; }
    .horario-aula-sala { font-size: 10px; color: #9ca3af; }
    .horario-empty { color: #d1d5db; text-align: center; font-size: 18px; padding-top: 4px; }

    /* ── Print button ── */
    .no-print { margin-bottom: 20px; }
    .print-btn {
      background: #0b1b3a; color: #fff; border: none; padding: 10px 24px;
      border-radius: 8px; font-size: 14px; cursor: pointer; margin-right: 10px;
    }
    .print-btn:hover { background: #1e3a6e; }
    .empty-state { color: #9ca3af; font-style: italic; font-size: 12px; }

    /* ── Print media ── */
    @media print {
      @page { margin: 1.5cm; size: A4; }
      .no-print { display: none !important; }
      .pdf-section { page-break-inside: avoid; }
      body { padding: 0; }
    }
  </style>
</head>
<body>

<div class="no-print">
  <button class="print-btn" onclick="window.print()">📄 Imprimir / Guardar PDF</button>
  <button onclick="window.close()" style="padding:10px 16px;border-radius:8px;border:1px solid #e5e7eb;background:#fff;cursor:pointer;font-size:13px;">✕ Fechar</button>
</div>

<!-- ── HEADER ── -->
<div class="pdf-header">
  <div class="pdf-header-left">
    <img src="/PAP/project/assets/aemtg.jpg" alt="Logo AEMTG" class="pdf-header-logo">
    <div>
      <div class="pdf-header-title">Perfil do Aluno</div>
      <div class="pdf-header-sub">Agrupamento de Escolas Manuel Teixeira Gomes</div>
    </div>
  </div>
  <div class="pdf-date">
    Gerado em <?= htmlspecialchars($dataGeracao) ?><br>
    Sistema RFID — PAP 2026
  </div>
</div>

<!-- ── STUDENT INFO ── -->
<div class="student-info">
  <?php if ($fotoUrl): ?>
    <img class="student-avatar" src="<?= htmlspecialchars($fotoUrl) ?>" alt="Foto">
  <?php else: ?>
    <div class="student-avatar-placeholder">👤</div>
  <?php endif; ?>
  <div class="student-info-text">
    <h2><?= htmlspecialchars($nome) ?></h2>
    <p>
      Turma: <strong><?= htmlspecialchars($turma) ?></strong><br>
      Login: <?= htmlspecialchars($login) ?><br>
      Dias de presença registados: <strong><?= $diasPresente ?></strong>
    </p>
  </div>
</div>

<!-- ── NOTAS ── -->
<div class="pdf-section">
  <div class="pdf-section-title">Notas</div>
  <?php if (empty($notasRows)): ?>
    <p class="empty-state">Sem notas registadas.</p>
  <?php else: ?>
    <table class="notas-table">
      <thead>
        <tr>
          <th>Matéria</th>
          <th>Tipo</th>
          <th>Valor</th>
          <th>Data</th>
          <th>Média da matéria</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($notasPorMateria as $materia => $valores):
          $mediaMateria = array_sum($valores) / count($valores);
          $notasDaMateria = array_filter($notasRows, fn($n) => $n['materia'] === $materia);
          foreach ($notasDaMateria as $n): ?>
          <tr>
            <td><?= htmlspecialchars($n['materia']) ?></td>
            <td><?= htmlspecialchars($n['tipo']) ?></td>
            <td class="nota-valor"><?= number_format((float)$n['valor'], 1) ?></td>
            <td><?= htmlspecialchars(date('d/m/Y', strtotime($n['data']))) ?></td>
            <td></td>
          </tr>
        <?php endforeach; ?>
        <tr class="notas-media-row">
          <td colspan="2">Média — <?= htmlspecialchars($materia) ?></td>
          <td colspan="3"><?= number_format($mediaMateria, 1) ?> / 20</td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div class="media-geral-box">
      Média geral: <?= number_format($mediaGeral, 1) ?> / 20
    </div>
  <?php endif; ?>
</div>

<!-- ── PRÓXIMOS TESTES ── -->
<div class="pdf-section">
  <div class="pdf-section-title">Próximos Testes</div>
  <?php if (empty($testes)): ?>
    <p class="empty-state">Sem testes marcados.</p>
  <?php else: ?>
    <?php foreach ($testes as $t): ?>
      <div class="teste-item">
        <div class="teste-item-title"><?= htmlspecialchars($t['titulo']) ?></div>
        <div class="teste-item-meta">
          📅 <?= htmlspecialchars(date('d/m/Y', strtotime($t['data_teste']))) ?>
          &nbsp;·&nbsp;
          <?= htmlspecialchars($t['materia'] ?? '—') ?>
          <?php if ($t['professor_nome']): ?>
            &nbsp;·&nbsp; Prof. <?= htmlspecialchars($t['professor_nome']) ?>
          <?php endif; ?>
        </div>
        <?php if (!empty($t['descricao'])): ?>
          <div style="font-size:11px;color:#374151;margin-top:3px;"><?= nl2br(htmlspecialchars($t['descricao'])) ?></div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- ── HORÁRIO ── -->
<div class="pdf-section">
  <div class="pdf-section-title">Horário Semanal — Turma <?= htmlspecialchars($turma) ?></div>
  <?php if (empty($horarioRows)): ?>
    <p class="empty-state">Sem horário registado.</p>
  <?php else: ?>
    <table class="horario-table">
      <thead>
        <tr>
          <?php foreach ($diasNome as $num => $nome_dia): ?>
            <th><?= $nome_dia ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <tr>
          <?php foreach ($diasNome as $num => $nome_dia): ?>
            <td>
              <?php if (!empty($horarioPorDia[$num])): ?>
                <?php foreach ($horarioPorDia[$num] as $h): ?>
                  <div class="horario-aula">
                    <div class="horario-aula-time"><?= substr($h['hora_inicio'], 0, 5) ?>–<?= substr($h['hora_fim'], 0, 5) ?></div>
                    <div class="horario-aula-materia"><?= htmlspecialchars($h['materia']) ?></div>
                    <?php if ($h['sala']): ?>
                      <div class="horario-aula-sala">Sala <?= htmlspecialchars($h['sala']) ?></div>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="horario-empty">—</div>
              <?php endif; ?>
            </td>
          <?php endforeach; ?>
        </tr>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<script>
  window.onload = function () { window.print(); };
</script>
</body>
</html>
