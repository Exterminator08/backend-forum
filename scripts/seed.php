<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Core/Db.php';
use App\Core\Db;

$pdo = Db::pdo();

$pdo->exec(statement: "DELETE FROM replies;");
$pdo->exec(statement: "DELETE FROM topics;");
$pdo->exec(statement: "DELETE FROM threads;");
$pdo->exec(statement: "DELETE FROM users;");

$pdo->prepare(query: "INSERT INTO users (name, username, email, password, role) VALUES
  ('Admin User', 'admin', 'admin@example.com', 'hash', 'admin'),
  ('Alice', 'alice', 'alice@example.com', 'hash', 'user'),
  ('Bob', 'bob', 'bob@example.com', 'hash', 'user')
")->execute();

$pdo->prepare(query: "INSERT INTO threads (user_id, title, description) VALUES
  (2, 'Welcome', 'Introduce yourself'),
  (3, 'Rules',   'Be respectful')
")->execute();

$pdo->prepare(query: "INSERT INTO topics (thread_id, user_id, title, body) VALUES
  (1, 2, 'Hello everyone', 'I am Alice'),
  (1, 3, 'New member here', 'Hi, Bob here'),
  (2, 1, 'Moderation policy', 'Read carefully')
")->execute();

$pdo->prepare(query: "INSERT INTO replies (topic_id, user_id, body) VALUES
  (1, 3, 'Welcome, Alice!'),
  (2, 2, 'Welcome Bob!'),
  (3, 3, 'Got it')
")->execute();

echo "Seeded.\n";
