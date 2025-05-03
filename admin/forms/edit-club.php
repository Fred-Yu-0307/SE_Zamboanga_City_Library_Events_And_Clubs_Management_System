<?php
require_once '../classes/clubs.class.php';
require_once '../classes/librarian.class.php';
require_once '../tools/adminfunctions.php';

session_start();
if (!isset($_SESSION['user']) || $_SESSION['user'] != 'admin') {
    header('location: ./index.php');
}

$clubs = new Clubs();
$librarian = new Librarian();
$librarians = $librarian->getAvailableLibrarian();

if (isset($_GET['id'])) {
    $club = $clubs->fetch($_GET['id']);
    if (!$club) {
        echo "Club not found";
        exit;
    }
} else {
    $club = [
        'clubID' => '',
        'clubName' => '',
        'clubDescription' => '',
        'clubMinAge' => '',
        'clubMaxAge' => '',
        'librarianIDs' => []
    ];
}

if (isset($_POST['save'])) {
    $clubID = $_GET['id'];
    $clubName = htmlentities($_POST['clubName']);
    $clubDescription = htmlentities($_POST['clubDescription']);
    $clubMinAge = htmlentities($_POST['clubMinAge']);
    $clubMaxAge = htmlentities($_POST['clubMaxAge']);
    $librarianIDs = isset($_POST['librarianIDs']) ? $_POST['librarianIDs'] : [];

    // Validate inputs
    $error = '';
    if (empty($clubName)) {
        $error = 'Club name is required';
    } elseif (empty($clubDescription)) {
        $error = 'Club description is required';
    } elseif (empty($librarianIDs)) {
        $error = 'Please select at least one club manager';
    }

    if (empty($error)) {
        if ($clubs->edit($clubID, $clubName, $clubDescription, $librarianIDs, $clubMinAge, $clubMaxAge)) {
            header('location: ../webpages/clubs.php');
            exit;
        } else {
            $error = 'Failed to update club';
        }
    }
    
    if (!empty($error)) {
        echo '<div class="alert alert-danger">' . $error . '</div>';
        // Re-populate form data
        $club = [
            'clubID' => $clubID,
            'clubName' => $clubName,
            'clubDescription' => $clubDescription,
            'clubMinAge' => $clubMinAge,
            'clubMaxAge' => $clubMaxAge,
            'librarianIDs' => $librarianIDs
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<?php
  $title = 'Clubs';
  $clubs = 'active-1';
  require_once('../include/head.php');
?>
<body>
    <div class="main">
        <div class="row">
            <?php require_once('../include/nav-panel.php'); ?>

            <div class="col-12 col-md-8 col-lg-9">
                <div class="container mt-4">
                    <div class="header-modal d-flex justify-content-between">
                        <h5 class="modal-title mt-4 ms-4">Edit Club</h5>
                    </div>
                    <div class="modal-body mx-2 mt-2">
                        <form method="post" action="" id="clubForm">
                            <div class="row d-flex justify-content-center my-1">
                                <div class="input-group flex-column mb-3">
                                    <label for="clubName" class="label">Club Name</label>
                                    <input type="text" name="clubName" id="clubName" class="input-1" required 
                                           value="<?php echo htmlspecialchars($club['clubName']); ?>">
                                </div>
                            </div>

                            <div class="row d-flex justify-content-center my-1">
                                <div class="input-group flex-column mb-3">
                                    <label for="clubDescription" class="label">Description</label>
                                    <textarea id="clubDescription" name="clubDescription" class="input-1 auto-expand-scrollable" 
                                              required><?php echo htmlspecialchars($club['clubDescription']); ?></textarea>
                                </div>
                            </div>

                            <div class="row d-flex justify-content-center my-1">
                                <div class="input-group flex-column mb-3">
                                    <label class="label">Select Club Manager/s</label>
                                    <br>
                                    <?php foreach ($librarians as $lib): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   name="librarianIDs[]" 
                                                   id="librarian<?php echo $lib['librarianID']; ?>" 
                                                   value="<?php echo $lib['librarianID']; ?>"
                                                   <?php echo in_array($lib['librarianID'], $club['librarianIDs']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="librarian<?php echo $lib['librarianID']; ?>">
                                                <?php echo htmlspecialchars($lib['librarianFirstName'] . ' ' . $lib['librarianLastName']); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                    <div class="form-check">
                                        <a href="../forms/add-librarian.php">Add Librarian</a>
                                    </div>
                                </div>
                            </div>

                            <div class="row d-flex justify-content-center my-1">
                                <div class="input-group flex-column mb-3">
                                    <label for="ageRange" class="label">Age Range</label>
                                    <div class="row">
                                        <div class="col-6">
                                            <label for="clubMinAge" class="label-2">Minimum</label>
                                            <input type="number" name="clubMinAge" id="clubMinAge" class="input-1" required value="<?php echo $club['clubMinAge']; ?>">
                                            <?php if (isset($_POST['clubMinAge']) && !validate_field($_POST['clubMinAge'])) : ?>
                                                <p class="text-danger my-1">Minimum age is required</p>
                                            <?php endif; ?>
                                        </div>

                                        <div class="col-6">
                                            <label for="clubMaxAge" class="label-2">Maximum</label>
                                            <input type="number" name="clubMaxAge" id="ageRanclubMaxAgegeMax" class="input-1" required value="<?php echo $club['clubMaxAge']; ?>">
                                            <?php if (isset($_POST['clubMaxAge']) && !validate_field($_POST['clubMaxAge'])) : ?>
                                                <p class="text-danger my-1">Maximum age is required</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="modal-action-btn d-flex justify-content-end">
                                <a href="../webpages/clubs.php" class="btn cancel-btn mb-4 me-4">Cancel</a>
                                <button type="submit" name="save" class="btn add-btn-2 mb-3 me-4">Update Club</button>
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