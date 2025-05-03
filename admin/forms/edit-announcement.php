<?php
require_once '../tools/adminfunctions.php';
require_once '../classes/announcement.class.php';

session_start();

// Restrict access if not admin
if (!isset($_SESSION['user']) || $_SESSION['user'] != 'admin') {
    header('location: ./index.php');
    exit;
}

$errors = [];
$announcement = new Announcement();

// Fetch existing announcement data
if (isset($_GET['id'])) {
    $record = $announcement->fetch($_GET['id']);
    if ($record) {
        $announcement->eventAnnouncementID = $record['eventAnnouncementID'];
        $announcement->eaTitle = $record['eaTitle'];
        $announcement->eaDescription = $record['eaDescription'];
        $announcement->eaStartDate = $record['eaStartDate'];
        $announcement->eaEndDate = $record['eaEndDate'];
        $announcement->eaStartTime = $record['eaStartTime'];
        $announcement->eaEndTime = $record['eaEndTime'];
    } else {
        header('location: ../webpages/events.php#announcements');
        exit;
    }
}

if (isset($_POST['save'])) {
    $announcement->eventAnnouncementID = $_GET['id'];
    $announcement->eaTitle = htmlentities($_POST['eaTitle']);
    $announcement->eaDescription = htmlentities($_POST['eaDescription']);
    $announcement->eaStartDate = htmlentities($_POST['eaStartDate']);
    $announcement->eaEndDate = htmlentities($_POST['eaEndDate']);
    $announcement->eaStartTime = htmlentities($_POST['eaStartTime']);
    $announcement->eaEndTime = htmlentities($_POST['eaEndTime']);

    // Validate required fields
    if (!validate_field($announcement->eaTitle)) {
        $errors['eaTitle'] = "Title is required.";
    }
    if (!validate_field($announcement->eaStartDate)) {
        $errors['eaStartDate'] = "Start date is required.";
    }
    if (!validate_field($announcement->eaEndDate)) {
        $errors['eaEndDate'] = "End date is required.";
    }

    // Logical validation of dates
    if (strtotime($announcement->eaStartDate) > strtotime($announcement->eaEndDate)) {
        $errors['dateRange'] = "Start date cannot be later than end date.";
    }

    // If dates are equal, compare times if provided
    if (
        strtotime($announcement->eaStartDate) === strtotime($announcement->eaEndDate) &&
        !empty($announcement->eaStartTime) && !empty($announcement->eaEndTime)
    ) {
        if (strtotime($announcement->eaStartTime) >= strtotime($announcement->eaEndTime)) {
            $errors['timeRange'] = "Start time must be earlier than end time.";
        }
    }

    if (empty($errors)) {
        if ($announcement->edit()) {
            header('location: ../webpages/events.php#announcements');
            exit;
        } else {
            $errors['db'] = "An error occurred while updating the announcement in the database.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<?php
  $title = 'Events & Announcements';
  $events = 'active-1';
  require_once('../include/head.php');
?>
<body>
<div class="main">
    <div class="row">
        <?php require_once('../include/nav-panel.php'); ?>

        <div class="col-12 col-md-8 col-lg-9">
            <!-- Edit Announcement Modal -->
            <div class="container mt-4">
                <div class="header-modal d-flex justify-content-between">
                    <h5 class="modal-title mt-4 ms-1" id="editAnnouncementModalLabel">Edit Announcement</h5>
                </div>
                <div class="modal-body mt-2">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger mt-2">
                            <?php foreach ($errors as $error): ?>
                                <div><?= htmlspecialchars($error) ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="">
                        <div class="row d-flex justify-content-center my-1">
                            <div class="input-group flex-column mb-3">
                                <label for="eaTitle" class="label">Announcement Title</label>
                                <input type="text" name="eaTitle" id="eaTitle" class="input-1"
                                       placeholder="Enter Announcement Title" required
                                       value="<?= htmlspecialchars($announcement->eaTitle ?? '') ?>">
                            </div>
                        </div>

                        <div class="row d-flex justify-content-center my-1">
                            <div class="input-group flex-column mb-3">
                                <label for="eaDescription" class="label">Description</label>
                                <input type="text" id="eaDescription" name="eaDescription"
                                       class="input-1 auto-expand-input"
                                       placeholder="Write brief description"
                                       value="<?= htmlspecialchars($announcement->eaDescription ?? '') ?>"
                                       style="min-width: 200px;">
                            </div>
                        </div>

                        <div class="row d-flex justify-content-center my-1">
                            <div class="input-group flex-column mb-3">
                                <label for="eventDate" class="label">Date</label>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <label for="eaStartDate" class="label-2">Start Date</label>
                                        <input type="date" name="eaStartDate" id="eaStartDate" class="input-1 col-lg-12"
                                               required value="<?= htmlspecialchars($announcement->eaStartDate ?? '') ?>">
                                    </div>

                                    <div class="col-6">
                                        <label for="eaEndDate" class="label-2">End Date</label>
                                        <input type="date" name="eaEndDate" id="eaEndDate" class="input-1 col-lg-12"
                                               required value="<?= htmlspecialchars($announcement->eaEndDate ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row d-flex justify-content-center my-1">
                            <div class="input-group flex-column mb-3">
                                <label for="eventTime" class="label">Time</label>
                                <div class="row">
                                    <div class="col-6">
                                        <label for="eaStartTime" class="label-2">Start Time</label>
                                        <input type="time" name="eaStartTime" id="eaStartTime" class="input-1 col-lg-12"
                                               value="<?= htmlspecialchars($announcement->eaStartTime ?? '') ?>">
                                    </div>

                                    <div class="col-6">
                                        <label for="eaEndTime" class="label-2">End Time</label>
                                        <input type="time" name="eaEndTime" id="eaEndTime" class="input-1 col-lg-12"
                                               value="<?= htmlspecialchars($announcement->eaEndTime ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-action-btn d-flex justify-content-end">
                            <button type="button" class="btn cancel-btn mb-4 me-4"
                                    onclick="window.history.back();" aria-label="Close">Cancel
                            </button>
                            <button type="submit" name="save" class="btn request-btn-2 mb-3 me-4">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once('../include/js.php'); ?>
</body>
</html>