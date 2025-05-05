<?php
require_once('../classes/database.php');
require_once('../classes/orgclub.class.php');

session_start();
if (!isset($_SESSION['user']) || $_SESSION['user'] != 'admin') {
    header('location: ./index.php');
    exit();
}

// Get the proposal ID from URL
$proposal_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($proposal_id <= 0) {
    $error = "Invalid or missing proposal ID";
}

$db = new Database();
$conn = $db->connect();
$orgClub = new OrgClub();

// Initialize variables
$proposal = null;
$proposalFiles = [];
$userDetails = null;

if (!$error) {
    // Fetch the specific proposal with organization details
    $query = "SELECT 
                p.*, 
                oc.ocName, 
                oc.ocEmail, 
                oc.userID,
                op.status,
                op.org_proposalID
              FROM proposal p
              JOIN org_proposal op ON p.proposalID = op.proposalID
              JOIN organization_club oc ON op.organizationClubID = oc.organizationClubID
              WHERE p.proposalID = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$proposal_id]);
    $proposal = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$proposal) {
        $error = "Proposal not found";
    } else {
        // Fetch proposal files
        $query = "SELECT proposalFile FROM proposal_files WHERE proposalID = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$proposal_id]);
        $proposalFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch user details
        if ($proposal['userID']) {
            $query = "SELECT userFirstName, userLastName FROM user WHERE userID = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$proposal['userID']]);
            $userDetails = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
}

// Set status text and color
$statusText = '';
$statusColor = '';
if (isset($proposal['status'])) {
    if ($proposal['status'] === 'Approved') {
        $statusText = 'Approved';
        $statusColor = 'text-success';
    } elseif ($proposal['status'] === 'Rejected') {
        $statusText = 'Rejected';
        $statusColor = 'text-danger';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<?php
$title = 'Proposal Details';
$organization = 'active-1';
require_once('../include/head.php');
?>

<body>
<div class="main">
    <div class="row">
        <?php require_once('../include/nav-panel.php'); ?>

        <div class="col-12 col-md-8 col-lg-9">
            <div class="row pt-3 ps-4">
                <div class="col-12 dashboard-header d-flex align-items-center justify-content-between">
                    <div class="heading-name d-flex">
                        <button class="back-btn me-4" onclick="goBack()">
                            <div class="d-flex align-items-center">
                                <i class='bx bx-arrow-back pe-3 back-icon'></i>
                                <span class="back-text">Back</span>
                            </div>
                        </button>
                        <p class="pt-3">Proposal Details</p>
                    </div>
                </div>

                <div class="row ps-2">
                    <div class="row">
                        <div class="col-12 scrollable-container-request ps-3">
                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger">
                                    <?= htmlspecialchars($error); ?>
                                    <p>Please go back to the <a href="organization-proposal.php">proposals list</a> and select a valid proposal.</p>
                                </div>
                            <?php else: ?>
                                <div class="proposal-card mt-4 mx-4">
                                    <div class="row d-flex justify-content-between align-items-center mb-5">
                                        <div class="col-12 col-lg-7">
                                            <div class="proposalSubject mb-2">
                                                <strong>SUBJECT: </strong>
                                                <?= isset($proposal['proposalSubject']) ? htmlspecialchars($proposal['proposalSubject']) : 'No Subject Available'; ?>
                                            </div>
                                            <div class="ocName mb-2">
                                                <strong>ORGANIZATION NAME: </strong>
                                                <?= isset($proposal['ocName']) ? htmlspecialchars($proposal['ocName']) : 'No Name Available'; ?>
                                            </div>
                                        </div>

                                        <div class="col-12 col-lg-5 d-flex flex-column align-items-end">
                                            <div class="orgHead d-flex align-items-center mb-3">
                                                <?php if ($userDetails): ?>
                                                    <p class="ms-3 pt-3">
                                                        <?= htmlspecialchars($userDetails['userFirstName'] . ' ' . $userDetails['userLastName']); ?>
                                                    </p>
                                                <?php else: ?>
                                                    <p class="ms-3 pt-3">No User Name Available</p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="proposalCreatedAT">
                                                <?= isset($proposal['proposalCreatedAt']) ? date('M d, Y h:i A', strtotime($proposal['proposalCreatedAt'])) : 'No Date Available'; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="proposalDescription">
                                        <?= isset($proposal['proposalDescription']) ? nl2br(htmlspecialchars($proposal['proposalDescription'])) : 'No Description Available'; ?>
                                    </div>
                                    
                                    <div class="proposalFile mt-5">
                                        <p class="d-flex align-items-center label-attached-file">
                                            <i class='bx bx-paperclip icon me-2'></i>Attached Files
                                        </p>
                                        <div class="files ms-2">
                                            <?php if (!empty($proposalFiles)): ?>
                                                <?php foreach ($proposalFiles as $file): ?>
                                                    <div class="file-card mb-2">
                                                        <?php
                                                        $filename = basename($file['proposalFile']);
                                                        $web_path = '/User/images/proposal_files/' . $filename;
                                                        $full_url = 'https://zclibrary.site' . $web_path;
                                                        $server_path = $_SERVER['DOCUMENT_ROOT'] . $web_path;
                                                        
                                                        if (file_exists($server_path)) {
                                                            echo '<a href="' . htmlspecialchars($full_url) . '" target="_blank" class="file-link">' . 
                                                                 htmlspecialchars($filename) . '</a>';
                                                        } else {
                                                            echo '<span class="text-danger">File not found: ' . 
                                                                 htmlspecialchars($filename) . '</span>';
                                                        }
                                                        ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <div class="file-card">No files attached</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Status display moved here -->
                                    <div class="status-display mt-4 mb-4">
                                        <?php if (!empty($statusText)): ?>
                                            <p class="mb-0"><strong>STATUS: </strong>
                                                <span class="<?= $statusColor ?>">
                                                    <?= $statusText ?>
                                                </span>
                                            </p>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-12 d-flex justify-content-lg-end justify-content-sm-center mt-4">
                                        <?php if (empty($statusText)): ?>
                                            <button class="approve-btn me-3" data-org-proposal-id="<?= $proposal['org_proposalID']; ?>">Approve</button>
                                            <button class="reject-btn" data-org-proposal-id="<?= $proposal['org_proposalID']; ?>">Reject</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div id="statusToast" class="toast align-items-center text-white" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="toastMessage"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.approve-btn, .reject-btn').forEach(button => {
        button.addEventListener('click', function () {
            const orgProposalID = this.getAttribute('data-org-proposal-id');
            const action = this.classList.contains('approve-btn') ? 'approve' : 'reject';
            
            if (confirm(`Are you sure you want to ${action} this proposal?`)) {
                // Disable buttons during processing
                document.querySelectorAll('.approve-btn, .reject-btn').forEach(btn => {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
                });
                
                fetch('update_proposal-status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `orgProposalID=${orgProposalID}&action=${action}`
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Show success toast
                        const toast = new bootstrap.Toast(document.getElementById('statusToast'));
                        document.getElementById('toastMessage').textContent = `Proposal ${action}d successfully`;
                        document.getElementById('statusToast').className = 'toast align-items-center text-white bg-success';
                        toast.show();
                        
                        // Reload after 1.5 seconds
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        throw new Error(data.message || 'Failed to update status');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    const toast = new bootstrap.Toast(document.getElementById('statusToast'));
                    document.getElementById('toastMessage').textContent = error.message;
                    document.getElementById('statusToast').className = 'toast align-items-center text-white bg-danger';
                    toast.show();
                    
                    // Re-enable buttons
                    document.querySelectorAll('.approve-btn, .reject-btn').forEach(btn => {
                        btn.disabled = false;
                        btn.innerHTML = btn.classList.contains('approve-btn') ? 'Approve' : 'Reject';
                    });
                });
            }
        });
    });
});
</script>

<?php require_once('../include/js.php'); ?>
</body>
</html>