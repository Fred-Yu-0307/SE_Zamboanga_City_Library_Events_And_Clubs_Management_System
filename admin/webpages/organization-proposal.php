<?php
require_once('../classes/database.php');
require_once('../classes/orgclub.class.php');
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user'] != 'admin') {
    header('location: ./index.php');
}

$database = new Database();
$db = $database->connect();

$orgClub = new OrgClub();

$orgProposals = $orgClub->getOrganizationProposals();
$proposalStatuses = $orgClub->getProposalStatus();

// ✅ Convert proposalStatuses to associative array
$proposalStatusesAssoc = [];
foreach ($proposalStatuses as $status) {
    $proposalStatusesAssoc[$status['proposalID']] = $status['status'];
}
?>

<!DOCTYPE html>
<html lang="en">

<?php
$title = 'Organization';
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
                        <div class="heading-name">
                            <button class="back-btn me-4" onclick="goBack()">
                                <div class="d-flex align-items-center">
                                    <i class='bx bx-arrow-back pe-3 back-icon'></i>
                                    <span class="back-text">Back</span>
                                </div>
                            </button>
                            <p class="pt-3">Organization Proposals</p>
                        </div>
                    </div>

                    <div class="row ps-2">
                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link tab-label active" id="library-clubs-tab" data-bs-toggle="tab" data-bs-target="#library-clubs" type="button" role="tab" aria-controls="library-clubs" aria-selected="true">Inbox</button>
                            </li>
                        </ul>

                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active pt-3" id="library-clubs" role="tabpanel" aria-labelledby="library-clubs-tab">
                                <div class="row">
                                    <div class="col-12 scrollable-container-request">
                                        <?php foreach ($orgProposals as $proposal): ?>
                                            <?php
                                                $proposalID = $proposal['proposalID'];
                                                $status = $proposalStatusesAssoc[$proposalID] ?? '';
                                            ?>
                                            <a href="proposal-details.php?id=<?= $proposalID; ?>" class="message-card d-flex align-items-center min-w px-3 py-2 mb-2 <?= getStatusClass($status); ?>">
                                                <div class="orgClub-logo me-3">
                                                    <?php
                                                        $imageFile = $proposal['ocEmail'] . ".png";
                                                        $imagePath = "../../User/images/orgClub_pic/" . $imageFile;
                                                    ?>
                                                    <img src="<?php echo $imagePath; ?>" alt="Organization Logo"
                                                         onerror="this.onerror=null;this.src='../../User/images/orgClub_pic/default.png';"
                                                         style="width: 60px; height: 60px; object-fit: cover; border-radius: 50%;">
                                                </div>

                                                <?php if (isset($proposal['proposalCreatedAt'])): ?>
                                                    <div class="message-details me-auto">
                                                        <div class="orgClub-name mb-1">
                                                            <strong>Organization Name: </strong><?= $proposal['ocName'] ?? 'No Name Available'; ?>
                                                        </div>
                                                        <?php if (isset($proposal['proposalSubject'])): ?>
                                                            <div class="proposal-subject mb-1">
                                                                <strong>Subject: </strong><?= $proposal['proposalSubject']; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if (isset($proposal['proposalDescription'])): ?>
                                                            <div class="proposal-description">
                                                                <strong>Description: </strong><?= $proposal['proposalDescription']; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="proposal-createdAt ms-auto">
                                                        <strong>Date Sent: </strong><?= $proposal['proposalCreatedAt']; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <?php
                                function getStatusClass($status) {
                                    switch ($status) {
                                        case 'Approved':
                                            return 'border border-success';
                                        case 'Rejected':
                                            return 'border border-danger';
                                        default:
                                            return 'border border-primary';
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once('../include/js.php'); ?>
</body>

</html>