<?php
/* Lista as dúvidas dos Embaixadores. Acesso: painel.php?k=<chave do config.php>. */
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');
$dados = dirname(__DIR__, 5) . '/_embaixadores_dados';
$cfg = is_file("$dados/config.php") ? (include "$dados/config.php") : [];
if (empty($cfg['chave']) || !hash_equals((string)$cfg['chave'], (string)($_GET['k'] ?? ''))) { http_response_code(404); exit('Página não encontrada.'); }
$linhas = is_file("$dados/duvidas.jsonl") ? array_reverse(array_filter(array_map('trim', file("$dados/duvidas.jsonl")))) : [];
$h = function ($t) { return htmlspecialchars((string)$t, ENT_QUOTES, 'UTF-8'); };
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow"><title>Dúvidas dos Embaixadores</title>
<link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500&display=swap" rel="stylesheet">
<style>
:root{--bg:#0a0807;--fg:#f3ece4;--muted:#b3a79c;--gold:#d9b08c;--line:rgba(240,220,198,.14)}
body{margin:0;background:var(--bg);color:var(--fg);font:300 15px/1.6 "Lexend",system-ui,sans-serif}
.w{max-width:820px;margin:0 auto;padding:40px 16px 64px}
h1{font-weight:300;font-size:28px;margin:0 0 6px}.sub{color:var(--muted);margin:0 0 28px}
.c{border:1px solid var(--line);border-radius:12px;padding:18px 20px;margin-bottom:14px;background:#16120f}
.top{display:flex;flex-wrap:wrap;justify-content:space-between;gap:8px;margin-bottom:10px}
.n{font-weight:500}.q{white-space:pre-wrap}.m{font-size:12.5px;color:var(--muted)}
a.wa{display:inline-block;margin-top:12px;padding:9px 14px;border-radius:9px;background:linear-gradient(180deg,#f6e7d6,#c49b78);color:#2b1e15;text-decoration:none;font-weight:500;font-size:13.5px}
</style></head><body><div class="w">
<h1>Dúvidas dos Embaixadores</h1>
<p class="sub"><?= count($linhas) ?> dúvida(s) · a mais recente primeiro · atualize a página para ver novas</p>
<?php if (!$linhas): ?><p class="m">Nenhuma dúvida ainda.</p><?php endif; ?>
<?php foreach ($linhas as $l): $d = json_decode($l, true); if (!$d) continue;
  $dig = preg_replace('/\D/', '', $d['telefone'] ?? ''); if (strlen($dig) <= 11) $dig = '55' . $dig; ?>
<div class="c"><div class="top"><span class="n"><?= $h($d['nome']) ?> · <?= $h($d['telefone']) ?></span><span class="m"><?= $h($d['quando']) ?></span></div>
<div class="q"><?= $h($d['duvida']) ?></div>
<?php if (!empty($d['secao'])): ?><div class="m" style="margin-top:8px">Estava lendo: <?= $h($d['secao']) ?></div><?php endif; ?>
<a class="wa" href="https://wa.me/<?= $h($dig) ?>" target="_blank" rel="noopener">Responder no WhatsApp</a></div>
<?php endforeach; ?>
</div></body></html>
