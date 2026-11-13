<?php
/* Recebe as dúvidas do formulário de /embaixadores/.
   1. Guarda cada dúvida em ~/_embaixadores_dados/duvidas.jsonl (fora do site, o rsync não apaga).
   2. Avisa o Jeferson: e-mail (o PHP mail() está bloqueado nesta hospedagem em 13/11/2026)
      e/ou webhook do GHL, se 'webhook' estiver no config.php.
   3. As dúvidas aparecem em painel.php?k=<chave>.
   O config.php fica só no servidor (não vai para o GitHub):
     <?php return ['para' => 'email', 'de' => 'noreply@eleveseplus.com.br', 'chave' => '...', 'webhook' => ''];
*/
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function sair($cod, $dados) { http_response_code($cod); echo json_encode($dados, JSON_UNESCAPED_UNICODE); exit; }
function corta($t, $n) { $t = trim((string)$t); return function_exists('mb_substr') ? mb_substr($t, 0, $n) : substr($t, 0, $n); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') sair(405, ['ok' => false]);
$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) $in = $_POST;
if (!empty($in['site'])) sair(200, ['ok' => true]);            // campo-isca: robô preencheu

$nome  = corta($in['nome'] ?? '', 120);
$tel   = corta($in['telefone'] ?? '', 40);
$duv   = corta($in['duvida'] ?? '', 3000);
$secao = corta($in['secao'] ?? '', 120);
if ($nome === '' || $tel === '' || $duv === '') sair(422, ['ok' => false, 'erro' => 'Preencha nome, telefone e dúvida.']);

$dados = dirname(__DIR__, 5) . '/_embaixadores_dados';       // /home/<usuário>/_embaixadores_dados
if (!is_dir($dados)) @mkdir($dados, 0700, true);

// limite: 8 envios por hora por IP
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$lim = "$dados/limite.json";
$mapa = is_file($lim) ? (json_decode(@file_get_contents($lim), true) ?: []) : [];
$agora = time();
foreach ($mapa as $k => $v) $mapa[$k] = array_values(array_filter($v, function ($t) use ($agora) { return $t > $agora - 3600; }));
$mapa = array_filter($mapa);
if (count($mapa[$ip] ?? []) >= 8) sair(429, ['ok' => false, 'erro' => 'Muitos envios seguidos. Tente de novo em alguns minutos.']);
$mapa[$ip][] = $agora;
@file_put_contents($lim, json_encode($mapa));

date_default_timezone_set('America/Sao_Paulo');
$quando = date('d/m/Y H:i');
$reg = ['quando' => $quando, 'nome' => $nome, 'telefone' => $tel, 'duvida' => $duv, 'secao' => $secao, 'ip' => $ip];
$salvo = @file_put_contents("$dados/duvidas.jsonl", json_encode($reg, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX) !== false;

$cfg = is_file("$dados/config.php") ? (include "$dados/config.php") : [];
$enviado = false;
if (!empty($cfg['para'])) {
  $digitos = preg_replace('/\D/', '', $tel);
  if (strlen($digitos) <= 11) $digitos = '55' . $digitos;
  $corpo = "Nova dúvida no Manual do Embaixador\n\n"
         . "Nome: $nome\nWhatsApp: $tel\nResponder: https://wa.me/$digitos\nQuando: $quando\n"
         . ($secao ? "Estava lendo: $secao\n" : '')
         . "\nDúvida:\n$duv\n\n— congressoplenitude.eleveseplus.com.br/embaixadores/";
  $de = $cfg['de'] ?? 'noreply@eleveseplus.com.br';
  $assunto = '=?UTF-8?B?' . base64_encode("Dúvida de Embaixador: $nome") . '?=';
  $cab = "From: =?UTF-8?B?" . base64_encode('Manual do Embaixador') . "?= <$de>\r\n"
       . "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\nMIME-Version: 1.0\r\n";
  $enviado = @mail($cfg['para'], $assunto, $corpo, $cab, "-f$de");
}
// aviso pelo GHL (se houver um webhook em config.php): o fluxo do GHL manda o WhatsApp para o Jeferson
if (!empty($cfg['webhook'])) {
  $ctx = stream_context_create(['http' => ['method' => 'POST', 'timeout' => 6, 'ignore_errors' => true,
    'header' => "Content-Type: application/json\r\n", 'content' => json_encode($reg + ['origem' => 'manual-embaixador'], JSON_UNESCAPED_UNICODE)]]);
  if (@file_get_contents($cfg['webhook'], false, $ctx) !== false) $enviado = true;
}
if (!$salvo && !$enviado) sair(500, ['ok' => false, 'erro' => 'Não foi possível enviar agora. Tente de novo.']);
sair(200, ['ok' => true]);
