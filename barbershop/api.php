<?php
// ============================================================
//  BarberPro — api.php
//  Coloque este arquivo na mesma pasta que barbearia.html
//  Ex: C:\xampp\htdocs\barberpro\api.php
// ============================================================

// ---- Configurações do banco ----
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // usuário do MySQL no XAMPP
define('DB_PASS', '');           // senha (em geral vazia no XAMPP local)
define('DB_NAME', 'barberpro');

// ---- CORS (permite o HTML chamar a API) ----
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// ---- Conexão ----
$pdo = new PDO(
    "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
    DB_USER, DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

// ---- Roteamento ----
$action = $_GET['action'] ?? '';
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

function resp($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================================
//  LOGIN
// ============================================================
if ($action === 'login') {
    $user = trim($body['user'] ?? '');
    $pass = trim($body['pass'] ?? '');
    $mode = $body['mode'] ?? 'barbeiro'; // 'barbeiro' ou 'cliente'

    if ($mode === 'barbeiro') {
        // Verifica admin
        $adm = $pdo->prepare("SELECT * FROM usuarios WHERE user = ? AND pass = ? AND role = 'admin'");
        $adm->execute([$user, $pass]);
        if ($row = $adm->fetch()) {
            resp(['ok' => true, 'role' => 'admin', 'id' => $row['id'], 'nome' => $row['nome'], 'refId' => null]);
        }
        // Verifica barbeiro
        $bar = $pdo->prepare("SELECT * FROM barbeiros WHERE user = ? AND pass = ?");
        $bar->execute([$user, $pass]);
        if ($row = $bar->fetch()) {
            resp(['ok' => true, 'role' => 'barbeiro', 'id' => $row['id'], 'nome' => $row['nome'], 'refId' => $row['id']]);
        }
    } else {
        $cli = $pdo->prepare("SELECT * FROM clientes WHERE user = ? AND pass = ?");
        $cli->execute([$user, $pass]);
        if ($row = $cli->fetch()) {
            resp(['ok' => true, 'role' => 'cliente', 'id' => $row['id'], 'nome' => $row['nome'], 'refId' => $row['id']]);
        }
    }
    resp(['ok' => false, 'msg' => 'Usuário ou senha inválidos.'], 401);
}

// ============================================================
//  BARBEIROS
// ============================================================
if ($action === 'get_barbeiros') {
    resp($pdo->query("SELECT * FROM barbeiros ORDER BY nome")->fetchAll());
}

if ($action === 'add_barbeiro') {
    // Proteção server-side: apenas admin pode cadastrar barbeiro
    // O front envia o role do usuário logado para conferência dupla
    if (($body['role'] ?? '') !== 'admin') {
        resp(['ok' => false, 'msg' => 'Acesso negado. Apenas o administrador pode cadastrar barbeiros.'], 403);
    }
    $s = $pdo->prepare("INSERT INTO barbeiros (nome,esp,tel,status,cor,user,pass) VALUES (?,?,?,?,?,?,?)");
    $s->execute([
        $body['nome'], $body['esp'] ?? '', $body['tel'] ?? '',
        $body['status'] ?? 'Ativo', $body['cor'] ?? 'gold',
        $body['user'] ?? '', $body['pass'] ?? '1234'
    ]);
    resp(['ok' => true, 'id' => $pdo->lastInsertId()]);
}

if ($action === 'del_barbeiro') {
    $pdo->prepare("DELETE FROM barbeiros WHERE id = ?")->execute([$body['id']]);
    resp(['ok' => true]);
}

// ============================================================
//  CLIENTES
// ============================================================
if ($action === 'get_clientes') {
    resp($pdo->query("SELECT * FROM clientes ORDER BY nome")->fetchAll());
}

if ($action === 'add_cliente') {
    $s = $pdo->prepare("INSERT INTO clientes (nome,tel,email,nasc,serv_fav,visitas,desde,user,pass) VALUES (?,?,?,?,?,0,CURDATE(),?,?)");
    $s->execute([
        $body['nome'], $body['tel'] ?? '', $body['email'] ?? '',
        $body['nasc'] ?: null, $body['serv_fav'] ?? '—',
        $body['user'] ?? '', $body['pass'] ?? '1234'
    ]);
    resp(['ok' => true, 'id' => $pdo->lastInsertId()]);
}

if ($action === 'del_cliente') {
    $pdo->prepare("DELETE FROM clientes WHERE id = ?")->execute([$body['id']]);
    resp(['ok' => true]);
}

// ============================================================
//  SERVIÇOS
// ============================================================
if ($action === 'get_servicos') {
    resp($pdo->query("SELECT * FROM servicos ORDER BY nome")->fetchAll());
}

if ($action === 'add_servico') {
    $s = $pdo->prepare("INSERT INTO servicos (nome,icone,preco,dur,cat) VALUES (?,?,?,?,?)");
    $s->execute([
        $body['nome'], $body['icone'] ?? '✂️',
        $body['preco'], $body['dur'] ?? 30, $body['cat'] ?? 'Corte'
    ]);
    resp(['ok' => true, 'id' => $pdo->lastInsertId()]);
}

if ($action === 'del_servico') {
    $pdo->prepare("DELETE FROM servicos WHERE id = ?")->execute([$body['id']]);
    resp(['ok' => true]);
}

// ============================================================
//  AGENDAMENTOS
// ============================================================
if ($action === 'get_agendamentos') {
    $sql = "
        SELECT a.*, c.nome AS cliente_nome, b.nome AS barbeiro_nome, s.nome AS servico_nome, s.preco
        FROM agendamentos a
        JOIN clientes c  ON a.cliente_id  = c.id
        JOIN barbeiros b ON a.barbeiro_id = b.id
        JOIN servicos s  ON a.servico_id  = s.id
        ORDER BY a.data DESC, a.hora DESC
    ";
    resp($pdo->query($sql)->fetchAll());
}

if ($action === 'add_agendamento') {
    $s = $pdo->prepare("INSERT INTO agendamentos (cliente_id,barbeiro_id,servico_id,data,hora,obs,status) VALUES (?,?,?,?,?,?,'Aguardando')");
    $s->execute([
        $body['clienteId'], $body['barbeiroId'], $body['servicoId'],
        $body['data'], $body['hora'], $body['obs'] ?? ''
    ]);
    // Incrementa visitas do cliente
    $pdo->prepare("UPDATE clientes SET visitas = visitas + 1 WHERE id = ?")->execute([$body['clienteId']]);
    resp(['ok' => true, 'id' => $pdo->lastInsertId()]);
}

if ($action === 'update_status') {
    $s = $pdo->prepare("UPDATE agendamentos SET status = ? WHERE id = ?");
    $s->execute([$body['status'], $body['id']]);
    // Se concluído, incrementa cortes do barbeiro
    if ($body['status'] === 'Concluído') {
        $ag = $pdo->prepare("SELECT barbeiro_id FROM agendamentos WHERE id = ?");
        $ag->execute([$body['id']]);
        if ($row = $ag->fetch()) {
            $pdo->prepare("UPDATE barbeiros SET cortes = cortes + 1 WHERE id = ?")->execute([$row['barbeiro_id']]);
        }
    }
    resp(['ok' => true]);
}

if ($action === 'del_agendamento') {
    $pdo->prepare("DELETE FROM agendamentos WHERE id = ?")->execute([$body['id']]);
    resp(['ok' => true]);
}

// ---- Ação não encontrada ----
resp(['ok' => false, 'msg' => 'Ação inválida.'], 400);
