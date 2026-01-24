<?php
/**
 * Cleanup Orphaned Records
 * Removes records that reference non-existent borrowers
 *
 * Run this: http://localhost/loan/database/cleanup_orphans.php
 */

require_once __DIR__ . '/../db_connect.php';

echo "<!DOCTYPE html><html><head><title>Cleanup Orphaned Records</title>";
echo "<style>
body { font-family: 'Segoe UI', Tahoma, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
.container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
h1 { color: #1e293b; }
.success { color: #10b981; background: #d1fae5; padding: 10px 15px; border-radius: 5px; margin: 10px 0; }
.warning { color: #f59e0b; background: #fef3c7; padding: 10px 15px; border-radius: 5px; margin: 10px 0; }
.info { color: #2563eb; background: #dbeafe; padding: 10px 15px; border-radius: 5px; margin: 10px 0; }
.btn { display: inline-block; padding: 12px 24px; background: #2563eb; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
</style></head><body><div class='container'>";

echo "<h1>Cleanup Orphaned Records</h1>";

// Find orphaned borrower_documents
$orphanDocs = $conn->query("
    SELECT bd.id, bd.borrower_id, bd.document_type
    FROM borrower_documents bd
    LEFT JOIN borrowers b ON bd.borrower_id = b.id
    WHERE b.id IS NULL
");

$orphanDocCount = $orphanDocs ? $orphanDocs->num_rows : 0;

// Find orphaned customer_notifications
$orphanNotifs = $conn->query("
    SELECT cn.id, cn.borrower_id, cn.title
    FROM customer_notifications cn
    LEFT JOIN borrowers b ON cn.borrower_id = b.id
    WHERE b.id IS NULL
");

$orphanNotifCount = $orphanNotifs ? $orphanNotifs->num_rows : 0;

echo "<h2>Orphaned Records Found</h2>";

if ($orphanDocCount > 0) {
    echo "<div class='warning'>Found $orphanDocCount orphaned document(s) in borrower_documents</div>";
    echo "<ul>";
    while ($row = $orphanDocs->fetch_assoc()) {
        echo "<li>Document ID: {$row['id']} (borrower_id: {$row['borrower_id']}, type: {$row['document_type']})</li>";
    }
    echo "</ul>";
} else {
    echo "<div class='success'>No orphaned documents found</div>";
}

if ($orphanNotifCount > 0) {
    echo "<div class='warning'>Found $orphanNotifCount orphaned notification(s) in customer_notifications</div>";
    echo "<ul>";
    while ($row = $orphanNotifs->fetch_assoc()) {
        echo "<li>Notification ID: {$row['id']} (borrower_id: {$row['borrower_id']}, title: {$row['title']})</li>";
    }
    echo "</ul>";
} else {
    echo "<div class='success'>No orphaned notifications found</div>";
}

// Check if cleanup requested
if (isset($_GET['cleanup']) && $_GET['cleanup'] === 'yes') {
    echo "<h2>Cleanup Results</h2>";

    // Delete orphaned documents
    $deleteDocs = $conn->query("
        DELETE bd FROM borrower_documents bd
        LEFT JOIN borrowers b ON bd.borrower_id = b.id
        WHERE b.id IS NULL
    ");
    $deletedDocs = $conn->affected_rows;
    echo "<div class='success'>Deleted $deletedDocs orphaned document(s)</div>";

    // Delete orphaned notifications
    $deleteNotifs = $conn->query("
        DELETE cn FROM customer_notifications cn
        LEFT JOIN borrowers b ON cn.borrower_id = b.id
        WHERE b.id IS NULL
    ");
    $deletedNotifs = $conn->affected_rows;
    echo "<div class='success'>Deleted $deletedNotifs orphaned notification(s)</div>";

    // Now try to add foreign keys
    echo "<h2>Adding Foreign Keys</h2>";

    $fkResult1 = $conn->query("
        ALTER TABLE borrower_documents
        ADD CONSTRAINT fk_borrower_documents_borrower
        FOREIGN KEY (borrower_id) REFERENCES borrowers(id) ON DELETE CASCADE
    ");
    if ($fkResult1) {
        echo "<div class='success'>✓ Added FK: fk_borrower_documents_borrower</div>";
    } else {
        echo "<div class='warning'>Could not add FK: " . $conn->error . "</div>";
    }

    $fkResult2 = $conn->query("
        ALTER TABLE customer_notifications
        ADD CONSTRAINT fk_notifications_borrower
        FOREIGN KEY (borrower_id) REFERENCES borrowers(id) ON DELETE CASCADE
    ");
    if ($fkResult2) {
        echo "<div class='success'>✓ Added FK: fk_notifications_borrower</div>";
    } else {
        echo "<div class='warning'>Could not add FK: " . $conn->error . "</div>";
    }

    echo "<div class='info' style='margin-top: 20px;'>Cleanup complete!</div>";
} else {
    if ($orphanDocCount > 0 || $orphanNotifCount > 0) {
        echo "<p style='margin-top: 20px;'><a href='?cleanup=yes' class='btn' onclick=\"return confirm('This will permanently delete orphaned records. Continue?');\">Clean Up Orphaned Records</a></p>";
    }
}

echo "<p><a href='../admin.php?page=home' class='btn' style='background: #6b7280;'>Back to Dashboard</a></p>";
echo "</div></body></html>";
?>
