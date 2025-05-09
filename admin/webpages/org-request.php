<?php
require_once('../classes/database.php');
require_once('../classes/orgclub.class.php');
//resume session here to fetch session values
session_start();
/*
    if the user is not logged in, then redirect to the login page,
    this is to prevent users from accessing pages that require
    authentication such as the dashboard
*/
if (!isset($_SESSION['user']) || $_SESSION['user'] != 'admin') {
    header('location: ./index.php');
}
//if the above code is false then the HTML below will be displayed
// Create an instance of the OrgClub class
$OrgClub = new OrgClub();

// Fetch organization club details with ocStatus = 'Pending'
$organizationClubs = $OrgClub->getOrganizationDetails('Pending');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['Approved']) || isset($_POST['Rejected'])) {
        $organizationClubID = $_POST['organizationClubID'];
        $status = isset($_POST['Approved']) ? 'Approved' : 'Rejected';
        $OrgClub->updateStatus($organizationClubID, $status);
        header("Location: ../webpages/org-request.php");
        exit();
    } else {

    }
}
?>

<!DOCTYPE html>
<html lang="en">

<?php
$title = 'Organization';
$organization = 'active-1';
require_once('../include/head.php');
?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<body>


    <div class="main">
        <div class="row">
            <?php
            require_once('../include/nav-panel.php');
            ?>

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
                            <p class="pt-3">Organization Requests</p>
                        </div>
                    </div>



                    <div class="row">
                        <div class="col-12">
                            <?php
                            // Check if organization clubs are fetched
                            if (!empty($organizationClubs)) {
                                // Loop through organization clubs and display them
                                foreach ($organizationClubs as $OrgClub) {
                                    $from = $OrgClub["userFirstName"] . " " . $OrgClub["userLastName"];
                                    $email = $OrgClub["ocEmail"];
                                    $imageFile = $email . ".png";
                                    $imagePath = "../../User/images/orgClub_pic/" . $imageFile;
                                
                                   echo "<form method='post'>
    <input type='hidden' name='organizationClubID' value='" . $OrgClub["organizationClubID"] . "'>
    <a href=\"#\" class=\"message-card d-flex align-items-center min-w px-3 py-2 mb-2\">
        <div class=\"orgClub-logo me-3\" style=\"display: flex; align-items: center;\">
            <img src=\"$imagePath\" alt=\"Organization Logo\" 
                onerror=\"this.onerror=null;this.src='../../User/images/orgClub_pic/default.png';\" 
                style=\"width: 60px; height: 60px; object-fit: cover; border-radius: 50%;\">
        </div>
        
        <div class=\"message-details me-3\">
            <div class=\"request-title mb-1\">" . $OrgClub["ocName"] . "</div>
            <div class=\"short-description\">From: " . $from . "</div>
            <div class=\"short-description\">Email: " . $email . "</div>
            <div class=\"short-description\">Contact Number: " . $OrgClub["ocContactNumber"] . "</div>
            <div class=\"short-description\">Date: " . $OrgClub["ocCreatedAt"] . "</div>
        </div>
        
        <div class=\"flex-column justify-content-end align-items-end\">
            <button type='submit' name='Approved' class=\"btn btn-success me-2 accept-club\" style=\"margin-left: 700px;\">Accept</button>
            <button type='submit' name='Rejected' class=\"btn btn-danger reject-club\">Reject</button>
        </div>
    </a>
</form>";
                                }


                            } else {
                                echo "<p>No organization request found</p>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <?php require_once('../include/js.php'); ?>


</body>
</html>
