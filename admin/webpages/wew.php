<?php
require_once('../classes/database.php');
require_once('../classes/orgclub.class.php');

session_start();
if (!isset($_SESSION['user']) || $_SESSION['user'] != 'admin') {
    header('HTTP/1.1 403 Forbidden');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit();
}

$db = new Database();
$orgClub = new OrgClub();

$orgProposalID = filter_input(INPUT_POST, 'orgProposalID', FILTER_VALIDATE_INT);
$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);

if (!$orgProposalID || !in_array($action, ['approve', 'reject'])) {
    header('HTTP/1.1 400 Bad Request');
    exit();
}

$status = ($action === 'approve') ? 'Approved' : 'Rejected';

if ($orgClub->updateProposalStatus($orgProposalID, $status)) {
    echo json_encode(['success' => true, 'message' => 'Proposal status updated']);
} else {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Failed to update status']);
}