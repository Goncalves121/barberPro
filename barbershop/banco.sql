-- ============================================================
--  BarberPro — Script de criação do banco de dados MySQL
--  Execute no phpMyAdmin ou via terminal:
--  mysql -u root -p < banco.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS barberpro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE barberpro;

-- ======================== USUÁRIOS (admin) ========================
CREATE TABLE IF NOT EXISTS usuarios (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  nome     VARCHAR(100) NOT NULL,
  user     VARCHAR(50)  NOT NULL UNIQUE,
  pass     VARCHAR(255) NOT NULL,
  role     ENUM('admin','barbeiro','cliente') NOT NULL DEFAULT 'cliente'
);

INSERT INTO usuarios (nome, user, pass, role) VALUES
('Administrador', 'admin', 'admin123', 'admin');

-- ======================== BARBEIROS ========================
CREATE TABLE IF NOT EXISTS barbeiros (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  nome       VARCHAR(100) NOT NULL,
  esp        VARCHAR(100),
  tel        VARCHAR(20),
  status     ENUM('Ativo','Inativo') NOT NULL DEFAULT 'Ativo',
  cor        VARCHAR(20) DEFAULT 'gold',
  cortes     INT DEFAULT 0,
  avaliacao  DECIMAL(3,1) DEFAULT 5.0,
  user       VARCHAR(50) UNIQUE,
  pass       VARCHAR(255)
);

INSERT INTO barbeiros (nome, esp, tel, status, cor, cortes, avaliacao, user, pass) VALUES
('João Silva',   'Degradê & Navalhado', '(11) 91111-2222', 'Ativo', 'gold',  128, 4.9, 'joao',   '1234'),
('Rafael Souza', 'Barba & Estilo',      '(11) 92222-3333', 'Ativo', 'blue',   95, 4.8, 'rafael', '1234');

-- ======================== SERVIÇOS ========================
CREATE TABLE IF NOT EXISTS servicos (
  id     INT AUTO_INCREMENT PRIMARY KEY,
  nome   VARCHAR(100) NOT NULL,
  icone  VARCHAR(10) DEFAULT '✂️',
  preco  DECIMAL(8,2) NOT NULL,
  dur    INT DEFAULT 30,
  cat    VARCHAR(50) DEFAULT 'Corte'
);

INSERT INTO servicos (nome, icone, preco, dur, cat) VALUES
('Corte Degradê',       '✂️',  45.00, 45, 'Corte'),
('Barba Completa',      '🪒',  35.00, 30, 'Barba'),
('Corte + Barba',       '💈',  70.00, 60, 'Combo'),
('Hidratação Capilar',  '💧',  40.00, 30, 'Tratamento'),
('Corte Infantil',      '👦',  30.00, 30, 'Corte');

-- ======================== CLIENTES ========================
CREATE TABLE IF NOT EXISTS clientes (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  nome      VARCHAR(100) NOT NULL,
  tel       VARCHAR(20),
  email     VARCHAR(100),
  nasc      DATE,
  serv_fav  VARCHAR(100) DEFAULT '—',
  visitas   INT DEFAULT 0,
  desde     DATE,
  obs       TEXT,
  user      VARCHAR(50) UNIQUE,
  pass      VARCHAR(255)
);

INSERT INTO clientes (nome, tel, email, nasc, serv_fav, visitas, desde, user, pass) VALUES
('Carlos Almeida', '(11) 99111-2233', 'carlos@email.com', '1990-05-12', 'Corte Degradê',  8,  '2023-01-10', 'carlos', '1234'),
('Marcos Lima',    '(11) 98222-3344', 'marcos@email.com', '1985-11-20', 'Corte + Barba',  15, '2022-08-05', 'marcos', '1234'),
('Pedro Santos',   '(11) 97333-4455', 'pedro@email.com',  '1995-03-08', 'Barba Completa', 5,  '2024-02-14', 'pedro',  '1234');

-- ======================== AGENDAMENTOS ========================
CREATE TABLE IF NOT EXISTS agendamentos (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id  INT NOT NULL,
  barbeiro_id INT NOT NULL,
  servico_id  INT NOT NULL,
  data        DATE NOT NULL,
  hora        TIME NOT NULL,
  obs         TEXT,
  status      ENUM('Aguardando','Confirmado','Concluído','Cancelado') NOT NULL DEFAULT 'Aguardando',
  FOREIGN KEY (cliente_id)  REFERENCES clientes(id)  ON DELETE CASCADE,
  FOREIGN KEY (barbeiro_id) REFERENCES barbeiros(id) ON DELETE CASCADE,
  FOREIGN KEY (servico_id)  REFERENCES servicos(id)  ON DELETE CASCADE
);

INSERT INTO agendamentos (cliente_id, barbeiro_id, servico_id, data, hora, obs, status) VALUES
(1, 1, 1, CURDATE(),                '09:00', '', 'Confirmado'),
(2, 2, 3, CURDATE(),                '11:00', '', 'Aguardando'),
(3, 1, 2, CURDATE(),                '14:30', '', 'Concluído'),
(1, 2, 1, DATE_SUB(CURDATE(),INTERVAL 1 DAY), '10:00', '', 'Concluído'),
(2, 1, 3, DATE_ADD(CURDATE(),INTERVAL 1 DAY), '09:30', '', 'Aguardando');
