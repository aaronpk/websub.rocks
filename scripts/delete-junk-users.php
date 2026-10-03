<?php
// Deletes user accounts that have no test data associated with them.
//
// Most of these were created by bots entering other people's email
// addresses into the old emailed-login form, after which the recipients'
// email security scanners followed the login link. Passkey login stops
// new ones from being created this way.
//
// A user is deleted only if all of these are true:
//   - no hubs or publishers rows belong to them
//   - they have no passkeys
//   - they haven't logged in during the grace period (default 30 days)
//
// Subscriber tests don't record which user ran them, so someone who only
// ever tested a subscriber also has no test data and will be deleted.
//
// Usage, from the project root:
//   php scripts/delete-junk-users.php            # dry run: report only
//   php scripts/delete-junk-users.php --delete   # actually delete
//   php scripts/delete-junk-users.php --days=90  # change the grace period

function fail($message) {
  fwrite(STDERR, $message . "\n");
  exit(1);
}

if(PHP_SAPI !== 'cli') {
  fail("Run this from the command line.");
}

chdir(dirname(__DIR__));
require 'vendor/autoload.php';

$options = getopt('', ['delete', 'days:']);
$delete = isset($options['delete']);
$days = isset($options['days']) ? (int)$options['days'] : 30;
if($days < 1) {
  fail("--days must be at least 1");
}

$db = ORM::get_db();

// Refuse to run before the passkeys migration, or every passkey user's
// protection below would be silently missing
if(!$db->query("SHOW TABLES LIKE 'passkeys'")->fetchColumn()) {
  fail("The passkeys table doesn't exist. Apply database/0003.sql first.");
}

$cutoff = date('Y-m-d H:i:s', strtotime('-' . $days . ' days'));
$params = ['login_cutoff' => $cutoff, 'created_cutoff' => $cutoff];

$junk = "
  FROM users u
  WHERE NOT EXISTS (SELECT 1 FROM hubs h WHERE h.user_id = u.id)
    AND NOT EXISTS (SELECT 1 FROM publishers p WHERE p.user_id = u.id)
    AND NOT EXISTS (SELECT 1 FROM passkeys k WHERE k.user_id = u.id)
    AND (u.last_login IS NULL OR u.last_login < :login_cutoff)
    AND (u.date_created IS NULL OR u.date_created < :created_cutoff)";

$total = (int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn();

$query = $db->prepare('SELECT COUNT(*) ' . $junk);
$query->execute($params);
$count = (int)$query->fetchColumn();

$query = $db->prepare('SELECT YEAR(u.last_login) AS year, COUNT(*) AS users ' . $junk . ' GROUP BY year ORDER BY year');
$query->execute($params);
$by_year = $query->fetchAll(PDO::FETCH_KEY_PAIR);

echo "Users: $total\n";
echo "Without test data or passkeys, and no login since $cutoff: $count\n";
echo "Would keep: " . ($total - $count) . "\n\n";
echo "To delete, by year of last login:\n";
foreach($by_year as $year => $users) {
  printf("  %s  %d\n", $year ?: 'never', $users);
}
echo "\n";

if(!$delete) {
  echo "Dry run, nothing was deleted. Run with --delete to delete these users.\n";
  exit(0);
}

$db->beginTransaction();
try {
  $query = $db->prepare('DELETE u ' . $junk);
  $query->execute($params);
  $deleted = $query->rowCount();
  if($deleted != $count) {
    throw new RuntimeException("Expected to delete $count users but matched $deleted. Nothing was deleted.");
  }
  $db->commit();
} catch(Throwable $e) {
  $db->rollBack();
  fail($e->getMessage());
}

echo "Deleted $deleted users.\n";
