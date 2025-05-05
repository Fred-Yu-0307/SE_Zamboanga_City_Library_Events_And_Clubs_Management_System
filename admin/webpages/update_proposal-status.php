<?php
require_once('../classes/database.php');
require_once('../classes/orgclub.class.php');

session_start();
if (!isset($_SESSION['user']) || $_SESSION['user'] != 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$orgProposalID = $_POST['orgProposalID'] ?? null;
$action = $_POST['action'] ?? null;

if (!$orgProposalID || !in_array($action, ['approve', 'reject'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$db = new Database();
$orgClub = new OrgClub();

$status = $action === 'approve' ? 'Approved' : 'Rejected';

if ($orgClub->updateProposalStatus($orgProposalID, $status)) {
    echo json_encode([
        'success' => true,
        'message' => 'Proposal status updated',
        'status' => $status
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update proposal status'
    ]);
}