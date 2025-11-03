<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Db.php';
use App\Db;

$pdo = Db::pdo();

$pdo->exec("DELETE FROM replies;");
$pdo->exec("DELETE FROM topics;");
$pdo->exec("DELETE FROM threads;");
$pdo->exec("DELETE FROM users;");

$pdo->prepare("INSERT INTO users (name, username, email, password, role) VALUES
  ('Admin User', 'admin', 'admin@example.com', 'hash', 'admin'),
  ('Alice', 'alice', 'alice@example.com', 'hash', 'user'),
  ('Bob', 'bob', 'bob@example.com', 'hash', 'user')
")->execute();

$pdo->prepare("INSERT INTO threads (user_id, title, description) VALUES
  (2, 'Welcome', 'Introduce yourself'),
  (3, 'Rules',   'Be respectful')
")->execute();

$pdo->prepare("INSERT INTO topics (thread_id, user_id, title, body) VALUES
  (1, 2, 'Hello everyone', 'I am Alice'),
  (1, 3, 'New member here', 'Hi, Bob here'),
  (2, 1, 'Moderation policy', 'Read carefully')
")->execute();

$pdo->prepare("INSERT INTO replies (topic_id, user_id, body) VALUES
  (1, 3, 'Welcome, Alice!'),
  (2, 2, 'Welcome Bob!'),
  (3, 3, 'Got it')
")->execute();

echo "Seeded.\n";
