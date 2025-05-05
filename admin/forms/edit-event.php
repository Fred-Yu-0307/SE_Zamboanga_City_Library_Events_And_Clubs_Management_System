<?php
require_once '../classes/events.class.php';
require_once '../classes/librarian.class.php';
require_once '../tools/adminfunctions.php';

session_start();

// Restrict access if not admin
if (!isset($_SESSION['user']) || $_SESSION['user'] != 'admin') {
    header('location: ./index.php');
    exit;
}

$errors = [];
$event = new Events();
$librarian = new Librarian();
$librarians = $librarian->getAvailablelibrarian();
$organizationsClubs = $event->getApprovedOrganizationClubs();

// Fetch existing event data
if (isset($_GET['id'])) {
    $record = $event->fetch($_GET['id']);
    if ($record) {
        $event->eventID = $record['eventID'];
        $event->eventTitle = htmlentities($record['eventTitle']);
        $event->eventDescription = htmlentities($record['eventDescription']);
        $event->eventStartDate = $record['eventStartDate'];
        $event->eventEndDate = $record['eventEndDate'];
        $event->eventStartTime = $record['eventStartTime'];
        $event->eventEndTime = $record['eventEndTime'];
        $event->eventGuestLimit = $record['eventGuestLimit'];
        $event->eventRegion = $record['eventRegion'];
        $event->eventProvince = $record['eventProvince'];
        $event->eventCity = $record['eventCity'];
        $event->eventBarangay = $record['eventBarangay'];
        $event->eventStreetName = $record['eventStreetName'];
        $event->eventBuildingName = $record['eventBuildingName'];
        $event->eventZipCode = $record['eventZipCode'];
        $event->eventStatus = $record['eventStatus'];
        
        // Convert facilitator and organization IDs to arrays
        $event->librarianIDs = isset($record['librarianIDs']) ? 
            (is_array($record['librarianIDs']) ? $record['librarianIDs'] : explode(',', $record['librarianIDs'])) : [];
        $event->organizationClubIDs = isset($record['organizationClubIDs']) ? 
            (is_array($record['organizationClubIDs']) ? $record['organizationClubIDs'] : explode(',', $record['organizationClubIDs'])) : [];
    } else {
        header('location: ../webpages/events.php');
        exit;
    }
}

if (isset($_POST['save'])) {
    // Sanitize inputs
    $event->eventID = $_GET['id'];
    $event->eventTitle = htmlentities($_POST['eventTitle']);
    $event->eventDescription = htmlentities($_POST['eventDescription']);
    $event->eventStartDate = htmlentities($_POST['eventStartDate']);
    $event->eventEndDate = htmlentities($_POST['eventEndDate']);
    $event->eventStartTime = htmlentities($_POST['eventStartTime']);
    $event->eventEndTime = htmlentities($_POST['eventEndTime']);
    $event->eventGuestLimit = htmlentities($_POST['eventGuestLimit']);
    $event->eventRegion = htmlentities($_POST['eventRegion']);
    $event->eventProvince = htmlentities($_POST['eventProvince']);
    $event->eventCity = htmlentities($_POST['eventCity']);
    $event->eventBarangay = htmlentities($_POST['eventBarangay']);
    $event->eventStreetName = htmlentities($_POST['eventStreetName']);
    $event->eventBuildingName = htmlentities($_POST['eventBuildingName']);
    $event->eventZipCode = htmlentities($_POST['eventZipCode']);
    $event->eventStatus = htmlentities($_POST['eventStatus'] ?? 'Active');
    $event->librarianIDs = isset($_POST['librarianIDs']) ? $_POST['librarianIDs'] : [];
    $event->organizationClubIDs = isset($_POST['organizationClubIDs']) ? $_POST['organizationClubIDs'] : [];

    // Validate required fields
    if (!validate_field($event->eventTitle)) {
        $errors['eventTitle'] = "Event title is required.";
    }
    if (!validate_field($event->eventDescription)) {
        $errors['eventDescription'] = "Event description is required.";
    }
    if (!validate_field($event->eventStartDate)) {
        $errors['eventStartDate'] = "Start date is required.";
    }
    if (!validate_field($event->eventEndDate)) {
        $errors['eventEndDate'] = "End date is required.";
    }
    if (!validate_field($event->eventStartTime)) {
        $errors['eventStartTime'] = "Start time is required.";
    }
    if (!validate_field($event->eventEndTime)) {
        $errors['eventEndTime'] = "End time is required.";
    }
    if (!validate_field($event->eventGuestLimit)) {
        $errors['eventGuestLimit'] = "Guest limit is required.";
    }
    if (!validate_field($event->eventRegion)) {
        $errors['eventRegion'] = "Region is required.";
    }
    if (!validate_field($event->eventProvince)) {
        $errors['eventProvince'] = "Province is required.";
    }
    if (!validate_field($event->eventCity)) {
        $errors['eventCity'] = "City is required.";
    }
    if (!validate_field($event->eventBarangay)) {
        $errors['eventBarangay'] = "Barangay is required.";
    }
    if (!validate_field($event->eventStreetName)) {
        $errors['eventStreetName'] = "Street name is required.";
    }
    if (!validate_field($event->eventZipCode)) {
        $errors['eventZipCode'] = "Zip code is required.";
    }
    if (empty($event->librarianIDs)) {
        $errors['librarianIDs'] = "At least one facilitator is required.";
    }

    // Date validation
    if (strtotime($event->eventStartDate) > strtotime($event->eventEndDate)) {
        $errors['dateRange'] = "Start date cannot be later than end date.";
    }
    if (strtotime($event->eventStartDate) === strtotime($event->eventEndDate) && 
        strtotime($event->eventStartTime) >= strtotime($event->eventEndTime)) {
        $errors['timeRange'] = "Start time must be earlier than end time on the same day.";
    }

    if (empty($errors)) {
        // Check for conflicting events only if dates/times have changed
        $currentEvent = $event->fetch($event->eventID);
        $datesChanged = ($event->eventStartDate != $currentEvent['eventStartDate'] ||
                         $event->eventEndDate != $currentEvent['eventEndDate'] ||
                         $event->eventStartTime != $currentEvent['eventStartTime'] ||
                         $event->eventEndTime != $currentEvent['eventEndTime']);

        if ($datesChanged) {
            $otherEvents = $event->getAllEventsExcept($event->eventID);
            $newStart = $event->eventStartDate . ' ' . $event->eventStartTime;
            $newEnd = $event->eventEndDate . ' ' . $event->eventEndTime;

            if ($event->hasConflictingEvent($newStart, $newEnd, $otherEvents)) {
                $errors['conflict'] = "This event conflicts with an existing event at the same location. Please choose a different date or time.";
            }
        }

        if (empty($errors)) {
            if ($event->edit()) {
                header('location: ../webpages/events.php');
                exit;
            } else {
                $errors['db'] = "An error occurred while updating the event in the database.";
            }
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
            <!-- Edit Event Modal -->
            <div class="container mt-4">
                <div class="header-modal d-flex justify-content-between">
                    <h5 class="modal-title mt-4 ms-1" id="editEventModalLabel">Edit Event</h5>
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
                        <input type="hidden" name="eventID" value="<?= htmlspecialchars($event->eventID) ?>">

                        <div class="row d-flex justify-content-center my-1">
                            <div class="input-group flex-column mb-3">
                                <label for="eventTitle" class="label">Event Title</label>
                                <input type="text" name="eventTitle" id="eventTitle" class="input-1"
                                       placeholder="Title of the Event" required
                                       value="<?= htmlspecialchars($event->eventTitle) ?>">
                            </div>
                        </div>

                        <div class="row d-flex justify-content-center my-1">
                            <div class="input-group flex-column mb-3">
                                <label for="eventDescription" class="label">Description</label>
                                <input type="text" id="eventDescription" name="eventDescription" class="input-1 auto-expand-input"
                                       placeholder="Write brief description" required
                                       value="<?= htmlspecialchars($event->eventDescription) ?>">
                            </div>
                        </div>

                        <div class="row d-flex justify-content-center my-1">
                            <div class="input-group flex-column mb-3">
                                <label for="librarianID" class="label">Select Event Facilitators/s</label>
                                <br>
                                <?php foreach ($librarians as $librarian): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" 
                                            name="librarianIDs[]" 
                                            id="librarian<?= $librarian['librarianID'] ?>" 
                                            value="<?= $librarian['librarianID'] ?>"
                                            <?= in_array($librarian['librarianID'], $event->librarianIDs) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="librarian<?= $librarian['librarianID'] ?>">
                                            <?= htmlspecialchars($librarian['librarianFirstName'] . ' ' . $librarian['librarianLastName']) ?>
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
                                <label for="eventDate" class="label">Date of the Event</label>
                                <div class="row">
                                    <div class="col-6">
                                        <label for="eventStartDate" class="label-2">Start Date</label>
                                        <input type="date" name="eventStartDate" id="eventStartDate" class="input-1 col-lg-12"
                                               required value="<?= htmlspecialchars($event->eventStartDate) ?>">
                                    </div>
                                    <div class="col-6">
                                        <label for="eventEndDate" class="label-2">End Date</label>
                                        <input type="date" name="eventEndDate" id="eventEndDate" class="input-1 col-lg-12"
                                               required value="<?= htmlspecialchars($event->eventEndDate) ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row d-flex justify-content-center my-1">
                            <div class="input-group flex-column mb-3">
                                <label for="eventTime" class="label">Time of the Event</label>
                                <div class="row">
                                    <div class="col-6">
                                        <label for="eventStartTime" class="label-2">Start Time</label>
                                        <input type="time" name="eventStartTime" id="eventStartTime" class="input-1 col-lg-12"
                                               required value="<?= htmlspecialchars($event->eventStartTime) ?>">
                                    </div>
                                    <div class="col-6">
                                        <label for="eventEndTime" class="label-2">End Time</label>
                                        <input type="time" name="eventEndTime" id="eventEndTime" class="input-1 col-lg-12"
                                               required value="<?= htmlspecialchars($event->eventEndTime) ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row d-flex justify-content-center my-1">
                            <div class="input-group flex-column mb-3">
                                <label for="eventGuestLimit" class="label">Guest Limit</label>
                                <input type="number" name="eventGuestLimit" id="eventGuestLimit" class="input-1"
                                       required value="<?= htmlspecialchars($event->eventGuestLimit) ?>">
                            </div>
                        </div>

                        <div class="row d-flex justify-content-center mt-1">
                            <label for="eventRegion" class="label">Place of Event</label>
                            <div class="input-group flex-column mb-3">
                                <label for="eventRegion" class="label ps-2">Region</label>
                                <select name="eventRegion" id="eventRegion" class="input-1" required>
                                    <option value="">Select Region</option>
                                    <?php
                                    $regions = ['Region I', 'Region II', 'Region III', 'Region IV', 'Region V', 'Region VI', 
                                               'Region VII', 'Region VIII', 'Region IX', 'Region X', 'Region XI', 'Region XII', 
                                               'Region XIII', 'MIMAROPA', 'NCR', 'CAR', 'BARMM'];
                                    foreach ($regions as $region): ?>
                                        <option value="<?= htmlspecialchars($region) ?>" 
                                            <?= $event->eventRegion == $region ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($region) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="input-group flex-column mb-3">
                                <label for="eventProvince" class="label ps-2">Province</label>
                                <select name="eventProvince" id="eventProvince" class="input-1" required>
                                    <option value="">Select Province</option>
                                    <?php
                                    $provinces = [
                                        'Abra', 'Agusan del Norte', 'Agusan del Sur', 'Aklan', 'Albay', 'Antique', 'Apayao', 
                                        'Aurora', 'Basilan', 'Bataan', 'Batanes', 'Batangas', 'Benguet', 'Biliran', 'Bohol', 
                                        'Bukidnon', 'Bulacan', 'Cagayan', 'Camarines Norte', 'Camarines Sur', 'Camiguin', 
                                        'Capiz', 'Catanduanes', 'Cavite', 'Cebu', 'Cotabato', 'Davao de Oro', 'Davao del Norte', 
                                        'Davao del Sur', 'Davao Occidental', 'Davao Oriental', 'Dinagat Islands', 'Eastern Samar', 
                                        'Guimaras', 'Ifugao', 'Ilocos Norte', 'Ilocos Sur', 'Iloilo', 'Isabela', 'Kalinga', 
                                        'La Union', 'Laguna', 'Lanao del Norte', 'Lanao del Sur', 'Leyte', 'Maguindanao del Norte', 
                                        'Maguindanao del Sur', 'Marinduque', 'Masbate', 'Misamis Occidental', 'Misamis Oriental', 
                                        'Mountain Province', 'Negros Occidental', 'Negros Oriental', 'Northern Samar', 'Nueva Ecija', 
                                        'Nueva Vizcaya', 'Occidental Mindoro', 'Oriental Mindoro', 'Palawan', 'Pampanga', 
                                        'Pangasinan', 'Quezon', 'Quirino', 'Rizal', 'Romblon', 'Samar', 'Sarangani', 'Siquijor', 
                                        'Sorsogon', 'South Cotabato', 'Southern Leyte', 'Sultan Kudarat', 'Sulu', 'Surigao del Norte', 
                                        'Surigao del Sur', 'Tarlac', 'Tawi-Tawi', 'Zambales', 'Zamboanga del Norte', 
                                        'Zamboanga del Sur', 'Zamboanga Sibugay'
                                    ];
                                    foreach ($provinces as $province): ?>
                                        <option value="<?= htmlspecialchars($province) ?>" 
                                            <?= $event->eventProvince == $province ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($province) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="input-group flex-column mb-3">
                                <label for="eventCity" class="label ps-2">City</label>
                                <select name="eventCity" id="eventCity" class="input-1" required>
                                    <option value="">Select City</option>
                                    <?php
                                    $cities = [
                                        "Manila", "Quezon City", "Caloocan", "Las Piñas", "Makati", 
                                        "Malabon", "Mandaluyong", "Marikina", "Muntinlupa", "Navotas", 
                                        "Parañaque", "Pasay", "Pasig", "San Juan", "Taguig", 
                                        "Valenzuela", "Angeles", "Antipolo", "Bacolod", "Bago", 
                                        "Baguio", "Bais", "Balanga", "Batac", "Batangas City", 
                                        "Bayawan", "Baybay", "Bayugan", "Biñan", "Bislig", 
                                        "Bogo", "Borongan", "Butuan", "Cabadbaran", "Cabanatuan", 
                                        "Cabuyao", "Cadiz", "Cagayan de Oro", "Calamba", "Calapan", 
                                        "Calbayog", "Caloocan", "Candon", "Canlaon", "Carcar", 
                                        "Catbalogan", "Cauayan", "Cavite City", "Cebu City", "Cotabato City", 
                                        "Dagupan", "Danao", "Dapitan", "Dasmariñas", "Davao City", 
                                        "Digos", "Dipolog", "Dumaguete", "El Salvador", "Escalante", 
                                        "Gapan", "General Santos", "Gingoog", "Guihulngan", "Himamaylan", 
                                        "Ilagan", "Iligan", "Iloilo City", "Imus", "Iriga", 
                                        "Isabela", "Kabankalan", "Kidapawan", "Koronadal", "La Carlota", 
                                        "Lamitan", "Laoag", "Lapu-Lapu", "Las Piñas", "Laoag", 
                                        "Legazpi", "Ligao", "Lipa", "Lucena", "Maasin", 
                                        "Mabalacat", "Makati", "Malabon", "Malaybalay", "Malolos", 
                                        "Mandaluyong", "Mandaue", "Manila", "Marawi", "Marikina", 
                                        "Masbate City", "Mati", "Meycauayan", "Muñoz", "Muntinlupa", 
                                        "Naga", "Navotas", "Olongapo", "Ormoc", "Oroquieta", 
                                        "Ozamiz", "Pagadian", "Palayan", "Panabo", "Parañaque", 
                                        "Pasay", "Pasig", "Passi", "Puerto Princesa", "Quezon City", 
                                        "Roxas", "Sagay", "Samal", "San Carlos", "San Fernando", 
                                        "San Jose", "San Jose del Monte", "San Juan", "San Pablo", 
                                        "San Pedro", "Santa Rosa", "Santiago", "Silay", "Sipalay", 
                                        "Sorsogon City", "Surigao City", "Tabaco", "Tabuk", "Tacloban", 
                                        "Tacurong", "Tagaytay", "Tagbilaran", "Taguig", "Tagum", 
                                        "Talisay", "Tanauan", "Tandag", "Tangub", "Tanjay", 
                                        "Tarlac City", "Tayabas", "Toledo", "Trece Martires", "Tuguegarao", 
                                        "Urdaneta", "Valencia", "Valenzuela", "Victorias", "Vigan", 
                                        "Zamboanga"
                                    ];
                                    foreach ($cities as $city): ?>
                                        <option value="<?= htmlspecialchars($city) ?>" 
                                            <?= $event->eventCity == $city ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($city) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Replace the existing eventBarangay input field with this dropdown -->
                            <div class="input-group flex-column mb-3">
                                <label for="eventBarangay" class="label ps-2">Barangay</label>
                                <select name="eventBarangay" id="eventBarangay" class="input-1" required>
                                    <option value="">Select Barangay</option>
                                    <?php
                                    if (isset($event->eventCity) && !empty($event->eventCity)) {
                                        // This will be populated by JavaScript, but we include a PHP fallback
                                        // For now, we'll just output the current barangay if it exists
                                        if (!empty($event->eventBarangay)) {
                                            echo '<option value="'.htmlspecialchars($event->eventBarangay).'" selected>'.htmlspecialchars($event->eventBarangay).'</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="row mb-3">
                                <div class="col-6">
                                    <label for="eventStreetName" class="label-2">Street Name</label>
                                    <input type="text" name="eventStreetName" id="eventStreetName" class="input-1 col-lg-12" required
                                           value="<?= htmlspecialchars($event->eventStreetName) ?>">
                                </div>
                                <div class="col-6">
                                    <label for="eventBuildingName" class="label-2">Building Name</label>
                                    <input type="text" name="eventBuildingName" id="eventBuildingName" class="input-1 col-lg-12"
                                           placeholder="Optional" value="<?= htmlspecialchars($event->eventBuildingName) ?>">
                                </div>
                            </div>
                        </div>

                        <div class="input-group flex-column mb-3 my-2">
                            <label for="eventZipCode" class="label">Zip Code</label>
                            <input type="number" name="eventZipCode" id="eventZipCode" class="input-1" required
                                   value="<?= htmlspecialchars($event->eventZipCode) ?>">
                        </div>

                         <div class="row d-flex justify-content-center my-1">
                            <div class="input-group flex-column mb-3">
                                <label for="organizationClubID" class="label">Collaboration with</label>
                                <?php if (empty($organizationsClubs)): ?>
                                    <p>No affiliated organizations or clubs.</p>
                                <?php else: ?>
                                    <?php foreach ($organizationsClubs as $org): ?>
                                        <div class="checkbox-container d-flex align-items-center my-1">
                                            <input type="checkbox" class="form-check-input" 
                                                   name="organizationClubIDs[]" 
                                                   id="orgClub<?= $org['organizationClubID'] ?>" 
                                                   value="<?= $org['organizationClubID'] ?>"
                                                   <?= in_array($org['organizationClubID'], $event->organizationClubIDs) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="orgClub<?= $org['organizationClubID'] ?>">
                                                <?= htmlspecialchars($org['ocName']) ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="modal-action-btn d-flex justify-content-end">
                            <button type="button" class="btn cancel-btn mb-4 me-4"
                                    onclick="window.history.back();" aria-label="Close">Cancel
                            </button>
                            <button type="submit" name="save" class="btn request-btn-2 mb-3 me-4">Update Event</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once('../include/js.php'); ?>
<script>
$(document).ready(function() {
    // Region-Province-City-Barangay mappings
    const regions = {
                            "Region I": ["Ilocos Norte", "Ilocos Sur", "La Union", "Pangasinan"],
                            "Region II": ["Batanes", "Cagayan", "Isabela", "Nueva Vizcaya", "Quirino"],
                            "Region III": ["Aurora", "Bataan", "Bulacan", "Nueva Ecija", "Pampanga", "Tarlac", "Zambales"],
                            "Region IV-A": ["Batangas", "Cavite", "Laguna", "Quezon", "Rizal"],
                            "Region IV-B": ["Marinduque", "Occidental Mindoro", "Oriental Mindoro", "Palawan", "Romblon"],
                            "Region V": ["Albay", "Camarines Norte", "Camarines Sur", "Catanduanes", "Masbate", "Sorsogon"],
                            "Region VI": ["Aklan", "Antique", "Capiz", "Guimaras", "Iloilo", "Negros Occidental"],
                            "Region VII": ["Bohol", "Cebu", "Negros Oriental", "Siquijor"],
                            "Region VIII": ["Biliran", "Eastern Samar", "Leyte", "Northern Samar", "Samar", "Southern Leyte"],
                            "Region IX": ["Zamboanga del Norte", "Zamboanga del Sur", "Zamboanga Sibugay"],
                            "Region X": ["Bukidnon", "Camiguin", "Lanao del Norte", "Misamis Occidental", "Misamis Oriental"],
                            "Region XI": ["Davao de Oro", "Davao del Norte", "Davao del Sur", "Davao Occidental", "Davao Oriental"],
                            "Region XII": ["North Cotabato", "Sarangani", "South Cotabato", "Sultan Kudarat"],
                            "Region XIII": ["Agusan del Norte", "Agusan del Sur", "Surigao del Norte", "Surigao del Sur", "Dinagat Islands"],
                            "CAR": ["Abra", "Apayao", "Benguet", "Ifugao", "Kalinga", "Mountain Province"],
                            "NCR": ["Metro Manila"],
                            "BARMM": ["Basilan", "Lanao del Sur", "Maguindanao del Norte", "Maguindanao del Sur", "Sulu", "Tawi-Tawi"]
                        };

                        const provinceCities = {
                            // REGION I - Ilocos Region
                            "Ilocos Norte": ["Laoag", "Batac", "Paoay", "Currimao", "Burgos", "Pasuquin"],
                            "Ilocos Sur": ["Vigan", "Candon", "Narvacan", "Tagudin", "Santa Maria", "Cabugao"],
                            "La Union": ["San Fernando", "Bauang", "Agoo", "Naguilian", "Caba", "San Juan"],
                            "Pangasinan": ["Alaminos", "Dagupan", "San Carlos", "Urdaneta", "Lingayen", "Binmaley", "Mangaldan", "Calasiao", "Bayambang", "Rosales"],

                            // REGION II - Cagayan Valley
                            "Batanes": ["Basco", "Ivana", "Mahatao", "Sabtang", "Itbayat", "Uyugan"],
                            "Cagayan": ["Tuguegarao", "Aparri", "Ballesteros", "Gonzaga", "Solana", "Peñablanca", "Gattaran", "Lal-lo", "Buguey"],
                            "Isabela": ["Cauayan", "Ilagan", "Santiago", "Alicia", "Roxas", "Cabagan", "San Mateo", "Tumauini", "San Manuel"],
                            "Nueva Vizcaya": ["Bayombong", "Solano", "Bambang", "Aritao", "Dupax del Norte", "Kasibu"],
                            "Quirino": ["Cabarroguis", "Diffun", "Maddela", "Saguday", "Nagtipunan", "Aglipay"],

                            // REGION III - Central Luzon
                            "Aurora": ["Baler", "Casiguran", "Dilasag", "Dinalungan", "Dipaculao", "Maria Aurora", "San Luis"],
                            "Bataan": ["Balanga", "Abucay", "Bagac", "Dinalupihan", "Hermosa", "Limay", "Mariveles", "Morong", "Orani", "Orion", "Pilar", "Samal"],
                            "Bulacan": ["Malolos", "Marilao", "Meycauayan", "San Jose del Monte", "Angat", "Baliuag", "Bocaue", "Bulakan", "Calumpit", "Guiguinto", "Hagonoy", "Norzagaray", "Obando", "Pandi", "Plaridel", "Pulilan", "San Ildefonso", "San Miguel", "San Rafael", "Santa Maria"],
                            "Nueva Ecija": ["Cabanatuan", "Gapan", "Palayan", "San Jose", "Aliaga", "Bongabon", "Cabiao", "Carranglan", "Cuyapo", "Gabaldon", "General Mamerto Natividad", "General Tinio", "Guimba", "Jaen", "Licab", "Llanera", "Lupao", "Nampicuan", "Pantabangan", "Peñaranda", "Quezon", "Rizal", "San Antonio", "San Isidro", "San Leonardo", "Santa Rosa", "Santo Domingo", "Talavera", "Talugtug", "Zaragoza"],
                            "Pampanga": ["Angeles", "San Fernando", "Apalit", "Arayat", "Bacolor", "Candaba", "Floridablanca", "Guagua", "Lubao", "Mabalacat", "Macabebe", "Magalang", "Masantol", "Mexico", "Minalin", "Porac", "San Luis", "San Simon", "Santa Ana", "Santa Rita", "Santo Tomas", "Sasmuan"],
                            "Tarlac": ["Tarlac City", "Anao", "Bamban", "Camiling", "Capas", "Concepcion", "Gerona", "La Paz", "Mayantoc", "Moncada", "Paniqui", "Pura", "Ramos", "San Clemente", "San Jose", "San Manuel", "Santa Ignacia", "Victoria"],
                            "Zambales": ["Olongapo", "Botolan", "Cabangan", "Candelaria", "Castillejos", "Iba", "Masinloc", "Palauig", "San Antonio", "San Felipe", "San Marcelino", "San Narciso", "Santa Cruz", "Subic"],

                            // REGION IV-A - CALABARZON
                            "Batangas": ["Batangas City", "Lipa", "Tanauan", "Balayan", "Bauan", "Calaca", "Calatagan", "Lemery", "Nasugbu", "San Juan", "San Jose", "Taal", "Talisay"],
                            "Cavite": ["Bacoor", "Cavite City", "Dasmariñas", "General Trias", "Imus", "Tagaytay", "Trece Martires", "Amadeo", "Carmona", "Gen. Mariano Alvarez (GMA)", "Indang", "Kawit", "Naic", "Noveleta", "Rosario", "Silang", "Tanza", "Ternate"],
                            "Laguna": ["Biñan", "Calamba", "San Pablo", "San Pedro", "Santa Rosa", "Alaminos", "Bay", "Cabuyao", "Calauan", "Liliw", "Los Baños", "Nagcarlan", "Paete", "Pagsanjan", "Pila", "Siniloan", "Sta. Cruz", "Victoria"],
                            "Quezon": ["Tayabas", "Atimonan", "Candelaria", "Dolores", "Gumaca", "Infanta", "Lopez", "Lucban", "Mauban", "Pagbilao", "Real", "Sariaya", "Unisan"],
                            "Rizal": ["Antipolo", "Angono", "Binangonan", "Cainta", "Cardona", "Jalajala", "Morong", "Pililla", "Rodriguez (Montalban)", "San Mateo", "Tanay", "Taytay", "Teresa"],

                            // REGION IV-B - MIMAROPA
                            "Marinduque": ["Boac", "Gasan", "Santa Cruz"],
                            "Occidental Mindoro": ["San Jose", "Mamburao", "Sablayan"],
                            "Oriental Mindoro": ["Calapan", "Pinamalayan", "Naujan", "Roxas"],
                            "Palawan": ["Puerto Princesa", "Brooke's Point", "Coron", "Roxas", "El Nido"],
                            "Romblon": ["Romblon", "Odiongan", "San Fernando", "Cajidiocan"],

                            // REGION V - Bicol Region
                            "Albay": ["Legazpi", "Ligao", "Tabaco", "Daraga", "Guinobatan", "Camalig", "Polangui", "Oas", "Sto. Domingo", "Tiwi"],
                            "Camarines Norte": ["Daet", "Labo", "Mercedes", "Vinzons", "Basud", "San Vicente", "Talisay", "Jose Panganiban"],
                            "Camarines Sur": ["Iriga", "Naga", "Pili", "Libmanan", "Calabanga", "Tinambac", "Bato", "Buhi", "Baao", "Pasacao"],
                            "Catanduanes": ["Virac", "San Andres", "San Miguel", "Viga", "Baras", "Bato", "Bagamanoc", "Panganiban"],
                            "Masbate": ["Masbate City", "Aroroy", "Baleno", "Balud", "Cataingan", "Milagros", "Mandaon", "Esperanza"],
                            "Sorsogon": ["Sorsogon City", "Bulusan", "Barcelona", "Casiguran", "Gubat", "Irosin", "Juban", "Matnog", "Pilar"],

                            // REGION VI - Western Visayas
                            "Aklan": ["Kalibo", "Lezo", "Libacao", "Makato", "Malinao", "Nabas", "New Washington", "Numancia", "Tangalan"],
                            "Antique": ["San Jose de Buenavista", "Belison", "Bugasong", "Caluya", "Hamtic", "Lumbayanague", "Patnongon", "San Remigio", "Sibalom", "Tibiao", "Valderrama"],
                            "Capiz": ["Roxas City", "Ivisan", "Jamindan", "Maayon", "Mambusao", "Panay", "Pilar", "President Roxas", "Sapian", "Sigma", "Tapaz"],
                            "Guimaras": ["Jordan", "Buenavista", "Nueva Valencia", "San Lorenzo", "Santa Teresa"],
                            "Iloilo": ["Iloilo City", "Passi", "San Jose", "Alimodian", "Anilao", "Badiangan", "Balasan", "Banate", "Barotac Nuevo", "Barotac Viejo", "Bingawan", "Cabatuan", "Calinog", "Carles", "Concepcion", "Dingle", "Duenas", "Estancia", "Guimbal", "Igbaras", "Janiuay", "Leganes", "Lemery", "Leon", "Maasin", "Miagao", "Mina", "New Lucena", "Oton", "Pavia", "Pototan", "San Dionisio", "San Enrique", "San Miguel", "Santa Barbara", "Sara", "Tigbauan", "Tubungan", "Zarraga"],
                            "Negros Occidental": ["Bacolod", "Bago", "Cadiz", "Escalante", "Himamaylan", "Kabankalan", "La Carlota", "Sagay", "San Carlos", "Silay", "Talisay", "Binalbagan", "Cauayan", "Enrique B. Magalona", "Hinigaran", "Ilog", "Isabela", "La Castellana", "Murcia", "Pontevedra", "San Enrique", "San Enrique", "Silay", "Talisay", "Toboso", "Valladolid"],

                            // REGION VII - Central Visayas
                            "Bohol": ["Tagbilaran", "Alburquerque", "Antequera", "Baclayon", "Balilihan", "Bilar", "Boking", "Catigbian", "Carmen", "Danao", "Dauis", "Dimiao", "Duero", "Garcia Hernandez", "Jagna", "Loay", "Loon", "Mabini", "Panglao", "Pilar", "Sierra Bullones", "Tubigon"],
                            "Cebu": ["Bogo", "Carcar", "Cebu City", "Danao", "Lapu-Lapu City", "Mandaue", "Naga", "Talisay", "Toledo", "Cebu City", "Argao", "Barili", "Bogo", "Boljoon", "Buanoy", "Bantayan", "Pinamungahan", "Sogod", "Tuburan", "Catmon", "Dumanjug", "Tabuelan", "Danao", "Liloan", "Balamban"],
                            "Negros Oriental": ["Bais", "Bayawan", "Canlaon", "Dumaguete", "Guihulngan", "Tanjay", "Apo", "Ayungon", "Dauin", "Dumaguete", "La Libertad", "Manjuyod", "Mabinay", "Zamboanguita"],
                            "Siquijor": ["Siquijor", "Larena", "Lazi", "Maria", "San Juan", "Santo Niño"],

                            // REGION VIII - Eastern Visayas
                            "Biliran": ["Naval", "Caibiran", "Almeria", "Kawayan", "Biliran"],
                            "Eastern Samar": ["Borongan", "Guiuan", "Sulat", "Balangkayan", "Oras", "Lawaan"],
                            "Leyte": ["Ormoc", "Tacloban", "Palo", "Tanauan", "Jaro", "Carigara", "Baybay", "Abuyog", "Tolosa", "Julita", "Alangalang"],
                            "Northern Samar": ["Catarman", "Allen", "Bobon", "San Roque", "Palapag", "Las Navas", "Laoang", "Victoria", "San Isidro", "Lapinig"],
                            "Samar": ["Calbayog", "Catbalogan", "Jiabong", "San Jorge", "Matuguinao", "Hinabangan", "Pinabacdao", "Basey", "Gandara", "Paranas", "San Sebastian", "Villareal"],
                            "Southern Leyte": ["Maasin", "Sogod", "Liloan", "San Juan", "Bontoc", "Tomas Oppus", "Silago", "Libagon", "San Ricardo", "Hinunangan", "Anahawan"],

                            // REGION IX - Zamboanga Peninsula
                            "Zamboanga del Norte": ["Dapitan", "Dipolog", "Polanco", "Sindangan", "Roxas", "Manukan", "Liloy", "Siocon", "Labason", "Jose Dalman"],
                            "Zamboanga del Sur": ["Pagadian", "Zamboanga", "Molave", "Tukuran", "Aurora", "Dumingag", "Lakewood", "Ramon Magsaysay", "San Miguel", "Tabina"],
                            "Zamboanga Sibugay": ["Ipil", "Kabasalan", "Imelda", "Buug", "Diplahan", "Payao", "Roseller T. Lim", "Titay", "Mabuhay", "Talusan"],

                            // REGION X - Northern Mindanao
                            "Bukidnon": ["Malaybalay", "Valencia", "Manolo Fortich", "Maramag", "Quezon", "Don Carlos", "Kibawe", "Malitbog", "Talakag"],
                            "Camiguin": ["Mambajao", "Catarman", "Guinsiliban", "Mahinog", "Sagay"],
                            "Lanao del Norte": ["Iligan", "Tubod", "Kapatagan", "Baroy", "Kolambugan", "Lala", "Balo-i", "Linamon"],
                            "Misamis Occidental": ["Oroquieta", "Ozamiz", "Tangub", "Clarin", "Jimenez", "Plaridel", "Calamba", "Bonifacio"],
                            "Misamis Oriental": ["Cagayan de Oro", "El Salvador", "Gingoog", "Tagoloan", "Villanueva", "Jasaan", "Balingasag", "Opol"],

                            // REGION XI - Davao Region
                            "Davao de Oro": ["Nabunturan", "Monkayo", "Maco", "Pantukan", "Compostela"],
                            "Davao del Norte": ["Tagum", "Panabo", "Samal", "Carmen", "Santo Tomas"],
                            "Davao del Sur": ["Davao City", "Digos", "Bansalan", "Hagonoy", "Sta. Cruz"],
                            "Davao Occidental": ["Malita", "Santa Maria", "Jose Abad Santos", "Don Marcelino", "Sarangani"],
                            "Davao Oriental": ["Mati", "Baganga", "Cateel", "Lupon", "Boston"],

                            // REGION XII - SOCCSKSARGEN
                            "Cotabato": ["Kidapawan", "Midsayap", "Pigcawayan", "Kabacan", "Carmen", "President Roxas", "Makilala", "Tulunan"],
                            "Sarangani": ["Alabel", "Glan", "Kiamba", "Maasim", "Maitum", "Malapatan", "Malungon"],
                            "South Cotabato": ["General Santos", "Koronadal", "Polomolok", "Tupi", "Surallah", "Lake Sebu", "Tantangan", "T'boli"],
                            "Sultan Kudarat": ["Tacurong", "Isulan", "President Quirino", "Lambayong", "Columbio", "Lutayan", "Esperanza", "Bagumbayan", "Palimbang", "Sen. Ninoy Aquino"],

                            // REGION XIII - Caraga
                            "Agusan del Norte": ["Butuan", "Cabadbaran", "Nasipit", "Carmen", "Santiago", "Kitcharao"],
                            "Agusan del Sur": ["Bayugan", "Prosperidad", "San Francisco", "Esperanza", "Trento"],
                            "Dinagat Islands": ["San Jose", "Basilisa", "Loreto", "Cagdianao", "Libjo"],
                            "Surigao del Norte": ["Surigao City", "Placer", "Mainit", "Dapa", "Del Carmen", "Socorro"],
                            "Surigao del Sur": ["Bislig", "Tandag", "Lianga", "San Miguel", "Cantilan", "Tagbina"],

                            // NCR - National Capital Region
                            "Metro Manila": ["Caloocan", "Las Piñas", "Makati", "Malabon", "Mandaluyong", "Manila", "Marikina", "Muntinlupa", "Navotas", "Parañaque", "Pasay", "Pasig", "Quezon City", "San Juan", "Taguig", "Valenzuela"],

                            // CAR - Cordillera Administrative Region
                            "Abra": ["Bangued", "Lagayan", "Tayum", "Pilar", "Boliney", "Licuan-Baay"],
                            "Apayao": ["Kabugao", "Conner", "Pudtol", "Flora", "Santa Marcela", "Luna", "Calanasan"],
                            "Benguet": ["Baguio", "La Trinidad", "Itogon", "Tuba", "Tublay", "Bakun", "Kapangan"],
                            "Ifugao": ["Lagawe", "Kiangan", "Lamut", "Mayoyao", "Hungduan", "Banaue", "Aguinaldo"],
                            "Kalinga": ["Tabuk", "Rizal", "Pinukpuk", "Balbalan", "Lubuagan", "Tanudan", "Pasil"],
                            "Mountain Province": ["Bontoc", "Sagada", "Besao", "Barlig", "Sadanga", "Tadian", "Sabangan", "Natonin"],

                            // BARMM - Bangsamoro Autonomous Region in Muslim Mindanao
                            "Basilan": ["Isabela City", "Lamitan", "Maluso", "Tipo-Tipo", "Sumisip"],
                            "Lanao del Sur": ["Marawi", "Balindong", "Bayang", "Malabang", "Wao"],
                            "Maguindanao del Norte": ["Datu Odin Sinsuat", "Barira", "Parang", "Buldon", "Northern Kabuntalan"],
                            "Maguindanao del Sur": ["Shariff Aguak", "Buluan", "Datu Saudi-Ampatuan", "Rajah Buayan", "Datu Paglas"],
                            "Sulu": ["Jolo", "Patikul", "Indanan", "Maimbung", "Parang"],
                            "Tawi-Tawi": ["Bongao", "Panglima Sugala", "Simunul", "Sitangkai", "South Ubian"]
                        };

                        const cityBarangays = {
                            // NCR - Metro Manila
                            "Caloocan": ["Bagong Barrio", "Bagumbong", "Camarin", "Deparo", "Tala"],
                            "Las Piñas": ["Almanza", "BF International", "Pamplona", "Pulang Lupa", "Talon"],
                            "Makati": ["Bangkal", "Bel-Air", "Guadalupe Nuevo", "Poblacion", "San Lorenzo"],
                            "Malabon": ["Baritan", "Concepcion", "Dampalit", "Longos", "Potrero"],
                            "Mandaluyong": ["Addition Hills", "Barangka", "Highway Hills", "Plainview", "Wack-Wack"],
                            "Manila": ["Binondo", "Ermita", "Intramuros", "Malate", "Paco", "Pandacan", "Port Area", "Quiapo", "Sampaloc", "San Andres", "San Miguel", "San Nicolas", "Santa Ana", "Santa Cruz", "Santa Mesa", "Tondo"],
                            "Marikina": ["Barangka", "Concepcion Uno", "Fortune", "Industrial Valley", "Parang"],
                            "Muntinlupa": ["Alabang", "Ayala Alabang", "Bayanan", "Putatan", "Tunasan"],
                            "Navotas": ["Bagumbayan North", "Daanghari", "North Bay Boulevard", "San Jose", "Tangos North"],
                            "Parañaque": ["Baclaran", "Don Bosco", "La Huerta", "San Antonio", "Sun Valley"],
                            "Pasay": ["Barangay 183", "Cartimar", "Malibay", "Maricaban", "San Roque"],
                            "Pasig": ["Bagong Ilog", "Caniogan", "Kapitolyo", "Ortigas Center", "Ugong"],
                            "Quezon City": ["Alicia", "Amihan", "Apolonio Samson", "Aurora", "Baesa", "Bagbag", "Bagong Lipunan ng Crame", "Bagong Pag-asa", "Bagong Silangan", "Batasan Hills", "Commonwealth", "Cubao", "Fairview", "Kamuning", "Novaliches", "Project 4", "Roxas", "Sacred Heart", "Tandang Sora"],
                            "San Juan": ["Balong-Bato", "Greenhills", "Kabayanan", "Little Baguio", "West Crame"],
                            "Taguig": ["Bagumbayan", "Bambang", "Fort Bonifacio", "Napindan", "Tuktukan"],
                            "Valenzuela": ["Bagbaguin", "Gen. T. de Leon", "Karuhatan", "Malinta", "Marulas"],

                            // Region I - Ilocos Region
                            "Laoag": ["San Joaquin", "San Lorenzo", "San Pedro", "San Isidro"],
                            "Batac": ["Calayab", "Balbalungao", "Namarabar", "San Vicente"],
                            "Paoay": ["Paoay", "Nagbacalan", "Barit", "Suba"],
                            "Currimao": ["Bani", "San Antonio", "San Juan", "Quinale"],
                            "Burgos": ["Burgos", "Dumayco", "Balungay", "San Vicente"],
                            "Pasuquin": ["Cacafean", "Quinarayan"],
                            "Vigan": ["Ayusan Norte", "Mindoro", "Pantay Daya", "San Vicente", "Tamag", "Santa Catalina", "San Julian"],
                            "Candon": ["San Antonio", "San Jose", "Bagani Campo"],
                            "Narvacan": ["Namarab", "Caridad", "San Vicente"],
                            "Tagudin": ["San Marcos", "San Isidro"],
                            "Santa Maria": ["San Pedro", "San Isidro", "San Vicente", "Nalvo"],
                            "Cabugao": ["Sto. Niño", "San Felipe", "San Jose", "Bacnang"],
                            "San Fernando": ["Catbangen", "Pagdaraoan", "Poro", "Tanqui", "San Juan", "San Pablo", "San Agustin"],
                            "Bauang": ["Parang", "San Mariano", "Santo Niño", "Bañadero"],
                            "Agoo": ["Baybay", "San Joaquin", "Sto. Niño"],
                            "Naguilian": ["San Carlos"],
                            "Caba": ["Lamesa"],
                            "San Juan": ["San Vicente"],
                            "Dagupan": ["Bani"],
                            "San Carlos": ["Talisay", "San Roque"],
                            "Urdaneta": ["San Jose", "San Isidro"],
                            "Lingayen": ["Canao", "Pias", "San Juan"],
                            "Calasiao": ["San Pedro", "San Agustin", "San Isidro"],
                            "Bayambang": ["Pangulo", "San Antonio", "San Isidro", "San Vicente"],
                            "Rosales": ["San Pedro", "San Jose"],

                            // Region II - Cagayan Valley
                            "Basco": ["Kayvaluganan", "San Antonio", "Chanarian", "San Joaquin", "San Pedro"],
                            "Ivana": ["Radiwan", "San Vicente", "San Felix"],
                            "Mahatao": ["San Vicente", "Hanib", "Kaumbakan"],
                            "Sabtang": ["Sinakan", "Malakdang", "Nakanmuan", "Savidug", "Chavayan"],
                            "Itbayat": ["Raele", "San Rafael", "Santa Lucia", "Santa Rosa", "San Jose"],
                            "Uyugan": ["Kayuganan", "Imnajbu", "Itbud"],
                            "Aparri": ["Maura", "Tallungan", "San Antonio", "San Ignacio", "Centro", "San Jose", "San Manuel"],
                            "Ballesteros": ["Centro East", "Centro West", "Cabalangian", "Dungeg", "San Juan", "Santa Cruz"],
                            "Gonzaga": ["Centro", "Callao", "Calayan", "Caroan", "Irene", "Macutay"],
                            "Solana": ["Andarayan North", "Andarayan South", "Bagay", "Cabaritan", "Centro East", "Malalam", "Padeng", "Rang-Ayan", "San Vicente"],
                            "Peñablanca": ["Bugatay", "Callao", "Centro", "Minanga", "San Roque", "Cabbo"],
                            "Gattaran": ["Tanglagan", "Malibabag", "Malanao", "Centro", "Rang-ayan", "Namuac"],
                            "Lal-lo": ["Centro", "Fugay", "San Lorenzo", "Cabanbanan", "Bagumbayan"],
                            "Buguey": ["Centro", "Minanga", "Paddaya", "Villa Leonora"],
                            "Ilagan": ["Alibagu", "San Vicente", "Centro Poblacion", "Calamagui 1st", "Calamagui 2nd", "Sta. Barbara", "Sta. Isabel"],
                            "Alicia": ["Calaocan", "Centro Poblacion", "Divisoria", "Amungo", "San Fermin"],
                            "Roxas": ["Bantug", "Centro", "San Rafael", "San Antonio", "San Pedro"],
                            "Cabagan": ["Centro", "Luquilu", "San Juan", "San Antonio", "Garita"],
                            "San Mateo": ["Victoria", "Sinippil", "Santo Niño", "Sarrat", "Marasat Grande"],
                            "Tumauini": ["Annafunan", "Centro", "Antagan", "San Pedro", "San Vicente"],
                            "San Manuel": ["Mabatobato", "Purok", "Centro", "Caraniogan", "Nuesa"],
                            "Bayombong": ["Buenavista", "Don Tomas Maddela", "La Torre North", "La Torre South", "Iberica", "Magsaysay"],
                            "Solano": ["Bagahabag", "Caliat", "Poblacion North", "Poblacion South", "Uddiawan"],
                            "Bambang": ["Banggot", "Homestead", "San Fernando", "San Antonio", "Salvacion"],
                            "Aritao": ["Poblacion", "Banganan", "Canabuan", "Bantinan", "Nagcuartelan"],
                            "Dupax del Norte": ["I-ao", "Inaban", "Lamo", "Mambabao", "Parai", "San Isidro"],
                            "Kasibu": ["Antutot", "Capissaan", "Didipio", "Malabing Valley"],
                            "Cabarroguis": ["Gundaway", "Dibibi", "Mangandingay", "Zamora"],
                            "Diffun": ["Aurora East", "Aurora West", "Bonifacio", "Villa Pascua"],
                            "Maddela": ["Divisoria Norte", "Divisoria Sur", "San Salvador", "San Esteban"],
                            "Saguday": ["Cabaruan", "Gamis", "La Paz", "Saguday Poblacion"],
                            "Nagtipunan": ["Dipantan", "Palacian", "San Dionisio II", "Asaklat"],
                            "Aglipay": ["Andarayan", "Dumalneg", "Villa Pagaduan", "San Antonio", "Ragan Norte"],

                            // Region III - Central Luzon
                            "Baler": ["Zabali", "Buhangin", "Calabuanan", "Sabang"],
                            "Casiguran": ["Cohinog", "Culat", "Dibacong", "Esteves"],
                            "Dilasag": ["Diagyan", "Diniog", "Lawang", "Maligaya"],
                            "Dinalungan": ["Dibaraybay", "Diteki", "Mapalad", "Simbahan"],
                            "Dipaculao": ["Bayabas", "Borlongan", "Diaat", "Salay"],
                            "Maria Aurora": ["Bacong", "Diaat", "San Juan", "Sapang Balen"],
                            "San Luis": ["Bacong", "Dibalo", "San Isidro", "San Jose"],
                            "Balanga": ["Bagumbayan", "Camacho", "Cupang Proper", "Tuyo"],
                            "Abucay": ["Bangkal", "Calaylayan", "Laon", "Wawa"],
                            "Bagac": ["Banawang", "Binuangan", "Paysawan", "Quinaoayanan"],
                            "Dinalupihan": ["Bayabas", "Bonifacio", "Pablo Roman", "San Ramon"],
                            "Hermosa": ["Almacen", "Bamban", "San Pedro", "Tipo"],
                            "Limay": ["Kitang", "Sta. Rosa", "Townsite", "Wawa"],
                            "Mariveles": ["Alas-asin", "Balon Anito", "San Isidro", "Townsite"],
                            "Morong": ["Binaritan", "Sabang", "San Pedro", "Tortugas"],
                            "Orani": ["Balut", "Bayan", "Tala", "Tugatog"],
                            "Orion": ["Balut", "Daan Bilolo", "San Vicente", "Wakas"],
                            "Pilar": ["Alas-asin", "Balut", "Pantingan", "Wawa"],
                            "Samal": ["East Calaguiman", "San Juan", "San Roque", "West Calaguiman"],
                            "Malolos": ["Bagna", "Longos", "San Pablo", "Santo Rosario"],
                            "Marilao": ["Abangan Norte", "Lias", "Saog", "Tabing Ilog"],
                            "Meycauayan": ["Bancal", "Caingin", "Langka", "Perez"],
                            "San Jose del Monte": ["Gumaok", "Kaypian", "Sapang Palay", "Tungkong Mangga"],
                            "Angat": ["Banaban", "Marungko", "Niugan", "Sulucan"],
                            "Baliuag": ["Makinarya", "Paitan", "Sabang", "Sulivan"],
                            "Bocaue": ["Antipona", "Biñang 1st", "Caingin", "Turo"],
                            "Bulakan": ["Bambang", "Matungao", "Pitpitan", "Taliptip"],
                            "Calumpit": ["Balite", "Balungao", "Longos", "Meysulao"],
                            "Guiguinto": ["Cut-cut", "Malis", "Pritil", "Tabe"],
                            "Hagonoy": ["Abulalas", "Carillo", "San Agustin", "San Sebastian"],
                            "Norzagaray": ["Bangkal", "Matictic", "San Mateo"],
                            "Obando": ["Paco", "Paliwas", "Salambao", "Tawiran"],
                            "Pandi": ["Bagong Barrio", "Bunsuran", "Masuso", "Siling Bata"],
                            "Plaridel": ["Bangga 1st", "Lalangan", "Sipat", "Tabang"],
                            "Pulilan": ["Balatong A", "Inaon", "Lumbac", "Tibag"],
                            "San Ildefonso": ["Akle", "Alagao", "Anyatam", "Malipampang"],
                            "San Miguel": ["Balaong", "Salacot", "San Juan", "Tartaro"],
                            "San Rafael": ["Bancal", "Caingin", "Sampaloc", "Talacsan"],
                            "Santa Maria": ["Balasing", "Buenavista", "Pulong Buhangin", "Tumana"],
                            "Cabanatuan": ["Aduas", "Bantug", "Sumacab", "Zulueta"],
                            "Gapan": ["Baluarte", "San Lorenzo", "San Vicente", "Sto. Niño"],
                            "Palayan": ["Aulo", "Caimito", "Manaoag", "Santolan"],
                            "San Jose": ["Abar 1st", "Caanawan", "Tayabo", "Tondod"],
                            "Aliaga": ["Betes", "San Carlos", "San Juan", "Sto. Rosario"],
                            "Bongabon": ["Antipolo", "Arias", "Labi", "Tugatog"],
                            "Cabiao": ["Concepcion", "San Fernando", "San Gregorio", "Sta. Rita"],
                            "Carranglan": ["Burgos", "General Luna", "Minuli", "Salazar"],
                            "Cuyapo": ["Bantug", "Colosboa", "Maycaban", "Sta. Clara"],
                            "Gabaldon": ["Bagong Sikat", "Bantug", "Malinao", "South Poblacion"],
                            "General Mamerto Natividad": ["Balangkare", "Picaleon", "Pias"],
                            "General Tinio": ["Concepcion", "Nazareth", "Padolina", "Rio Chico"],
                            "Guimba": ["Agcano", "Manacsac", "Nagpandayan", "Sta. Veronica"],
                            "Jaen": ["Dampulan", "Don Mariano Marcos", "Putlod", "San Jose"],
                            "Licab": ["Aquino", "Linao", "San Casimiro", "Sta. Maria"],
                            "Llanera": ["Caridad Norte", "San Nicolas", "Victoria", "Villa Verde"],
                            "Lupao": ["Bagong Flores", "San Roque", "Sto. Domingo", "Villa Roman"],
                            "Nampicuan": ["Cabaducan", "East Poblacion", "West Poblacion", "Zamora"],
                            "Pantabangan": ["Cadaclan", "Liberty", "Marikit", "Villarica"],
                            "Peñaranda": ["Las Piñas", "San Josef", "Sto. Tomas", "Sinasajan"],
                            "Quezon": ["Bibiclat", "Manogpi", "Minabuyoc", "San Alejo"],
                            "Rizal": ["Agbannawag", "Aglipay", "Bicos", "Estrella"],
                            "San Antonio": ["Julio", "Lawang Kupang", "San Jose", "Sta. Barbara"],
                            "San Isidro": ["Alua", "Calaba", "Malapit", "Sto. Cristo"],
                            "San Leonardo": ["Bonifacio", "Mambangnan", "San Bartolome", "Tabuating"],
                            "Santa Rosa": ["Balingog", "Malaca", "Pulong Maragul", "Rajal Centro"],
                            "Santo Domingo": ["Malaya", "San Agustin", "Sto. Rosario"],
                            "Talavera": ["Andres Bonifacio", "Bantug", "San Pascual", "Santo Tomas"],
                            "Talugtug": ["Alula", "Baybayabas", "Cabayaoasan", "Maasin"],
                            "Zaragoza": ["Batitang", "Carmen", "San Vicente", "Sta. Cruz Old"],
                            "Angeles": ["Balibago", "Cutcut", "Malabanias", "Pampang", "Pulung Maragul"],
                            "San Fernando": ["Dolores", "Del Pilar", "San Agustin", "San Jose", "Sindalan"],
                            "Apalit": ["Balucuc", "Cansinala", "Sucad", "Sulipan"],
                            "Arayat": ["Candating", "La Paz", "San Agustin", "Sto. Niño"],
                            "Bacolor": ["Balas", "Cabalantian", "Maliwalu", "San Vicente"],
                            "Candaba": ["Bambang", "Pulong Gubat", "San Agustin", "Sta. Ana"],
                            "Floridablanca": ["Anon", "Mabical", "Maligaya", "San Jose"],
                            "Guagua": ["Bancal", "San Nicolas", "San Pedro", "Sta. Filomena"],
                            "Lubao": ["San Nicolas", "San Pedro", "Sta. Cruz", "Sta. Teresa"],
                            "Mabalacat": ["Dau", "Dolores", "San Francisco", "Sta. Maria"],
                            "Macabebe": ["Batasan", "San Gabriel", "San Roque", "Sta. Rita"],
                            "Magalang": ["San Agustin", "San Antonio", "San Ildefonso", "San Jose"],
                            "Masantol": ["Alauli", "Balanac", "San Agustin", "Sta. Cruz"],
                            "Mexico": ["Lagundi", "San Antonio", "San Jose", "San Lorenzo"],
                            "Minalin": ["San Francisco", "San Isidro", "Sta. Catalina", "Sta. Maria"],
                            "Porac": ["Babo Pangulo", "Cangatba", "Dolores", "Sta. Cruz"],
                            "San Simon": ["San Agustin", "San Jose", "San Juan", "San Miguel"],
                            "Santa Ana": ["San Agustin", "San Bartolome", "San Jose", "Sta. Maria"],
                            "Santa Rita": ["San Agustin", "San Isidro", "San Jose", "Sta. Monica"],
                            "Santo Tomas": ["Moras de la Paz", "San Bartolome", "San Matias", "Sta. Cruz"],
                            "Sasmuan": ["San Nicolas", "Sta. Lucia", "Sta. Monica", "Sto. Tomas"],
                            "Tarlac City": ["San Vicente", "San Manuel", "Carangian", "Cut-cut", "Lourdes"],
                            "Anao": ["Baguinay", "Camiling", "San Francisco", "Sta. Maria"],
                            "Bamban": ["Banaba", "San Nicolas", "San Pedro", "Sta. Ines"],
                            "Camiling": ["Bilad", "Matubog", "San Isidro", "Sta. Maria"],
                            "Capas": ["Aranguren", "Cristo Rey", "O'Donnell", "Sta. Lucia"],
                            "Concepcion": ["Balutu", "San Nicolas", "San Vicente", "Sta. Monica"],
                            "Gerona": ["Apsayan", "San Agustin", "San Jose", "Sta. Lucia"],
                            "La Paz": ["Balancan", "San Isidro", "San Roque", "Sta. Barbara"],
                            "Mayantoc": ["Bigbiga", "San Bartolome", "San Jose", "Sta. Lucia"],
                            "Moncada": ["Ablang", "San Juan", "San Pedro", "Sta. Lucia"],
                            "Paniqui": ["Acoscoso", "San Carlos", "San Isidro", "Sta. Ines"],
                            "Pura": ["Estipona", "Maasin", "San Jose", "Sta. Barbara"],
                            "Ramos": ["Coral-Iloco", "Guiteb", "Pance", "San Juan"],
                            "San Clemente": ["Balloc", "San Juan", "San Pedro", "Sta. Lucia"],
                            "San Jose": ["Iba", "San Juan", "San Roque", "Sta. Lucia"],
                            "San Manuel": ["Colubot", "San Miguel", "San Vicente", "Sta. Maria"],
                            "Santa Ignacia": ["Botbotones", "San Francisco", "San Vicente", "Sta. Ines"],
                            "Victoria": ["San Agustin", "San Andres", "San Fernando", "Sta. Barbara"],
                            "Olongapo": ["Barreto", "East Bajac-Bajac", "Gordon Heights", "New Cabalan", "West Tapinac"],
                            "Botolan": ["Bangan", "San Juan", "Sta. Barbara", "Villa Principe"],
                            "Cabangan": ["Anonang", "San Juan", "Sta. Rita", "Tondo"],
                            "Candelaria": ["Babancal", "Malimanga", "San Juan", "Uacon"],
                            "Castillejos": ["Balaybay", "San Agustin", "San Pablo", "Sta. Maria"],
                            "Iba": ["Bangantalinga", "San Agustin", "Sta. Barbara", "Zone 1"],
                            "Masinloc": ["Baloganon", "San Lorenzo", "Sta. Rita", "Taltal"],
                            "Palauig": ["Alwa", "Bato", "San Juan", "Sta. Cruz"],
                            "San Antonio": ["San Esteban", "San Gregorio", "San Juan", "Sta. Cruz"],
                            "San Felipe": ["Farañal", "San Rafael", "Sta. Ana", "Sto. Niño"],
                            "San Marcelino": ["Aglao", "Linasin", "San Guillermo", "Sta. Fe"],
                            "San Narciso": ["Alusiis", "San Jose", "San Juan", "Sta. Ana"],
                            "Santa Cruz": ["Babuyan", "Malabago", "Owaog-Nibloc", "Tubotubo"],
                            "Subic": ["Aningway", "Baraca-Camachile", "Mangan-Vaca", "Wawandue"],

                            // Region IV-A - CALABARZON

                            "Batangas City": ["Alangilan", "Balagtas", "Bolbok", "Pallocan", "Sta. Rita"],
                            "Lipa": ["Antipolo Del Norte", "Balintawak", "San Carlos", "Tambo", "Sabang"],
                            "Tanauan": ["Altura", "Boot", "Hidalgo", "Sambat"],
                            "Balayan": ["Calan", "San Juan", "Sta. Clara", "Sampaga"],
                            "Bauan": ["Alagao", "As-is", "San Miguel", "Sta. Maria"],
                            "Calaca": ["Baclas", "Coral Ni Lopez", "Dacanlao", "Sinisian"],
                            "Calatagan": ["Baha", "Balibago", "Bucal", "Talibayog"],
                            "Lemery": ["Arumahan", "Bukal", "Mahabang Dahilig", "Payapa"],
                            "Nasugbu": ["Aga", "Balaytigui", "Banilad", "Lumbangan"],
                            "San Juan": ["Barualte", "Bulsa", "Calicanto", "Talahiban"],
                            "San Jose": ["Aguila", "Anus", "Maugat", "Taysan"],
                            "Taal": ["Apacay", "Bihis", "Bolbok", "Butong"],
                            "Talisay": ["Aya", "Buco", "Caloocan", "Leynes"],
                            "Bacoor": ["Alima", "Banalo", "Camposanto", "Digman"],
                            "Cavite City": ["Caridad", "San Antonio", "San Roque", "Sta. Cruz"],
                            "Dasmariñas": ["Burol", "Langkaan", "Paliparan", "Sampaloc"],
                            "General Trias": ["Alingaro", "Biclatan", "Javalera", "San Francisco"],
                            "Imus": ["Anabu II", "Bayan Luma", "Malagasang", "Toclong"],
                            "Tagaytay": ["Dapdap East", "Kaybagal South", "Maharlika West", "Sungay East"],
                            "Trece Martires": ["De Ocampo", "Luciano", "Osorio", "San Agustin"],
                            "Amadeo": ["Banaybanay", "Dagatan", "Minantok", "Talom"],
                            "Carmona": ["Bancal", "Maduya", "Milagrosa", "Cabilang Baybay"],
                            "Gen. Mariano Alvarez": ["Burgos", "San Gabriel", "San Jose", "Sta. Cruz"],
                            "Indang": ["Agus-os", "Banaba Cerca", "Bancod", "Buna Cerca"],
                            "Kawit": ["Binakayan", "Gahak", "Kaingen", "Marulas"],
                            "Naic": ["Bucana Malaki", "Himalayan", "Labac", "Munting Mapino"],
                            "Noveleta": ["San Antonio", "San Jose", "San Juan", "San Rafael"],
                            "Rosario": ["Bagbag I", "Kanluran", "Ligtong", "Tejeros"],
                            "Silang": ["Balite", "Bucal", "Pulong Bunga", "Tartaria"],
                            "Tanza": ["Amaya", "Biga", "Julugan", "Sahud Ulan"],
                            "Ternate": ["Bucana", "Poblacion I", "San Jose", "San Juan"],
                            "Biñan": ["Canlalay", "De La Paz", "Malaban", "Sto. Tomas"],
                            "Calamba": ["Banlic", "Canlubang", "Halang", "Real"],
                            "San Pablo": ["San Gabriel", "San Vicente", "Del Remedio", "San Roque"],
                            "San Pedro": ["Cuyab", "Landayan", "Narra", "Pacita"],
                            "Santa Rosa": ["Balibago", "Dila", "Dita", "Labas"],
                            "Alaminos": ["Del Carmen", "Palma", "San Agustin", "San Andres"],
                            "Bay": ["Bitin", "Calo", "Dila", "San Antonio"],
                            "Cabuyao": ["Baclaran", "Banaybanay", "Banlic", "Niugan"],
                            "Calauan": ["Balayhangin", "Dayap", "San Isidro", "Santo Tomas"],
                            "Liliw": ["Bagong Anyo", "Bayate", "Bongkol", "San Isidro"],
                            "Los Baños": ["Bambang", "Batong Malake", "Maahas", "Putho"],
                            "Nagcarlan": ["Alibungbungan", "Banilad", "Buboy", "San Diego"],
                            "Paete": ["Ibaba", "Ilaya", "Maytoong", "Quinale"],
                            "Pagsanjan": ["Anibong", "Biñan", "Lambac", "Magdapio"],
                            "Pila": ["Aplaya", "Labuin", "Linga", "San Antonio"],
                            "Siniloan": ["Acevida", "Bagumbar", "Kapatalan", "Macatad"],
                            "Sta. Cruz": ["Alipit", "Bubukal", "Gatid", "San Pablo"],
                            "Victoria": ["Masapang", "San Benito", "San Felix", "San Roque"],
                            "Tayabas": ["Alitao", "Angeles", "Baguio", "Banot"],
                            "Atimonan": ["Angeles", "Balubad", "Buhangin", "Inaclagan"],
                            "Candelaria": ["Buenavista", "Bukal Norte", "Kinatihan I", "Malabanban Norte"],
                            "Dolores": ["Antonino", "Bagong Anyo", "Bayanihan", "Bulakin"],
                            "Gumaca": ["Anonang", "Babatnin", "Bacong", "Banot"],
                            "Infanta": ["Bacong", "Banugao", "Binonoan", "Catambungan"],
                            "Lopez": ["Burgos", "Danlagan", "Hondagua", "Mabini"],
                            "Lucban": ["Ayuti", "Barangay 1", "Barangay 2", "Barangay 3"],
                            "Mauban": ["Alitap", "Cagsiay", "Concepcion", "Daungan"],
                            "Pagbilao": ["Alupaye", "Antipolo", "Ayaas", "Barangay 1"],
                            "Real": ["Bagong Silang", "Capalong", "Cawayan", "Kiloloran"],
                            "Sariaya": ["Antipolo", "Balubal", "Bignay", "Buhay na Tubig"],
                            "Unisan": ["Almacen", "Balagtas", "Bonifacio", "Burgos"],
                            "Antipolo": ["Beverly Hills", "Dela Paz", "Mambugan", "San Jose"],
                            "Angono": ["Bagumbayan", "Kalayaan", "San Isidro", "San Roque"],
                            "Binangonan": ["Batingan", "Kalayaan", "Platero", "Sapang"],
                            "Cainta": ["San Andres", "San Isidro", "San Juan", "San Roque"],
                            "Cardona": ["Balibago", "Del Remedio", "San Roque", "Subay"],
                            "Jalajala": ["Bagumbong", "Bayugo", "Second District", "Third District"],
                            "Morong": ["Bombongan", "Canantong", "Lagundi", "San Pedro"],
                            "Pililla": ["Bagumbayan", "Halayhayin", "Malaya", "Niyugan"],
                            "Rodriguez": ["Balite", "Burgos", "San Jose", "San Rafael"],
                            "San Mateo": ["Ampid I", "Dulong Bayan", "Guinayang", "Malanday"],
                            "Tanay": ["Cayabu", "Daraitan", "Katipunan-Bayani", "Sampaloc"],
                            "Taytay": ["Dolores", "San Juan", "Santa Ana", "Sta. Lucia"],
                            "Teresa": ["Bagumbayan", "Calumpang", "Dalig", "San Gabriel"],        

                            // Region IV-B - MIMAROPA
                            "Boac": ["Balimbing", "Bantad", "Bantay", "Bayuti"],
                            "Gasan": ["Antipolo", "Bahi", "Bahi", "Tiguion"],
                            "Santa Cruz": ["Alobo", "Aturan", "Bambanin", "Hupi"],
                            "San Jose": ["Bagong Sikat", "Central", "Labangan Poblacion", "Murtha"],
                            "Mamburao": ["Fatima", "San Luis", "Talabaan", "Tayamaan"],
                            "Sablayan": ["Burgos", "Claro M. Recto", "San Agustin", "San Nicolas"],
                            "Calapan": ["San Vicente Central", "Sta. Maria Village", "San Rafael", "Sta. Isabel", "Comunal"],
                            "Pinamalayan": ["Anoling", "Bacungan", "Banilad", "Sabang"],
                            "Naujan": ["Andres Ylagan", "Apitong", "Bacungan", "Bancuro"],
                            "Roxas": ["Bagumbayan", "Dangay", "Happy Valley", "San Aquilino"],
                            "Puerto Princesa": ["San Pedro", "San Manuel", "San Miguel", "San Jose", "Bancao-Bancao"],
                            "Brooke's Point": ["Amas", "Barong-barong", "Calasaguen", "Mainit"],
                            "Coron": ["Banuang Daan", "Bintuan", "Decabobo", "San Jose"],
                            "El Nido": ["Bacuit", "Barangonan", "Buena Suerte", "Corong-corong"],
                            "Romblon": ["Agpanabat", "Agbudia", "Banton", "Canton"],
                            "Odiongan": ["Ambulong", "Bangon", "Batiano", "Budiong"],
                            "San Fernando": ["Agtiwa", "Azagra", "Cajidiocan", "Otod"],
                            "Cajidiocan": ["Alibagon", "Cambajao", "Gaudencio", "Lico"],

                            // Region V - Bicol Region
                            "Legazpi": ["Bogtong", "Bonot", "Bgy. 1 Ilawod", "Rawis", "Taysan"],
                            "Ligao": ["Allang", "Batangan", "Bonga", "Busay"],
                            "Tabaco": ["Agnas", "Bacolod", "Bariw", "San Carlos"],
                            "Daraga": ["Alcala", "Banadero", "Bascaran", "Sagpon"],
                            "Guinobatan": ["Balite", "Calzada", "Catomag", "Tandarora"],
                            "Camalig": ["Baligang", "Bariw", "Quirangay", "Sua"],
                            "Polangui": ["Alnay", "Balangibang", "Lanigay", "Magurang"],
                            "Oas": ["Badbad", "Bagsa", "Banao", "San Pascual"],
                            "Sto. Domingo": ["Alimsog", "Bagong San Roque", "Fidel Surtida", "San Andres"],
                            "Tiwi": ["Baybay", "Bolo", "Cararayan", "Naga"],
                            "Daet": ["Alawihao", "Bagasbas", "Gubat", "Lag-on"],
                            "Labo": ["Anameam", "Bagong Silang", "Bagumbayan", "Malaya"],
                            "Mercedes": ["Apuao", "Barangay I", "Tarusan", "San Roque"],
                            "Vinzons": ["Sabang", "San Jose", "San Vicente", "Sta. Cruz"],
                            "Basud": ["Bactas", "Mampili", "San Felipe", "Talisay"],
                            "San Vicente": ["Asdum", "Cabanbanan", "Mangcayo", "San Jose"],
                            "Talisay": ["Binanuaanan", "Caawigan", "Poblacion", "San Jose"],
                            "Jose Panganiban": ["Plaridel", "San Pedro", "Santa Cruz", "Parang"],
                            "Iriga": ["San Nicolas", "San Isidro", "Perpetual Help", "Santiago", "Sta. Isabel"],
                            "Naga": ["Bagumbayan Norte", "Cararayan", "Concepcion Grande", "Peñafrancia", "Tinago"],
                            "Pili": ["Anayan", "Cadlan", "Curry", "San Jose"],
                            "Libmanan": ["Aslong", "Bagacay", "Bahao", "Malansad"],
                            "Calabanga": ["Balongay", "Belgica", "Poblacion", "San Antonio"],
                            "Tinambac": ["Baga", "Baliuag Nuevo", "San Isidro", "San Ramon"],
                            "Bato": ["Balinad", "Bulusan", "San Roque", "Sogod"],
                            "Buhi": ["Antipolo", "Iraya", "San Jose", "San Ramon"],
                            "Baao": ["Antipolo", "Bagumbayan", "San Francisco", "San Jose"],
                            "Pasacao": ["Antipolo", "Bagumbayan", "Balogo", "San Antonio"],
                            "Virac": ["Balite", "Calatagan", "San Isidro", "San Jose"],
                            "San Andres": ["Alibuag", "Batel", "Cabungahan", "Codon"],
                            "San Miguel": ["Balatohan", "Boton", "Kilikilihan", "Tobrehon"],
                            "Viga": ["Almojuela", "Ananong", "Ogbong", "San Jose"],
                            "Baras": ["Agban", "Benticayan", "Guinsaanan", "Putsan"],
                            "Bagamanoc": ["Antipolo", "Bacak", "San Isidro", "San Vicente"],
                            "Panganiban": ["Buenavista", "Cabuyoan", "San Miguel", "Taopon"],
                            "Masbate City": ["Animasola", "B. Titong", "Bantigue", "Bapor"],
                            "Aroroy": ["Ambolong", "Bagsa", "Balawing", "Balete"],
                            "Baleno": ["Caugnan", "Eastern", "Mabini", "San Ramon"],
                            "Balud": ["Baybay", "Bongcanaway", "Jintotolo", "Poblacion"],
                            "Cataingan": ["Abaca", "Bagumbayan", "Cadulawan", "San Jose"],
                            "Milagros": ["Bara", "Bangad", "San Carlos", "Tagbon"],
                            "Mandaon": ["Bat-Ongan", "Buri", "San Juan", "Tumalaytay"],
                            "Esperanza": ["Agoho", "Bella", "Guadalupe", "San Jose"],
                            "Sorsogon City": ["Abuyog", "Balogo", "Burabod", "Salog"],
                            "Bulusan": ["Bagacay", "Central", "San Francisco", "San Isidro"],
                            "Barcelona": ["Alang", "Burgos", "Luneta", "San Ramon"],
                            "Casiguran": ["Boton", "Central", "San Juan", "Trece Martires"],
                            "Gubat": ["Bagacay", "Benguet", "Manook", "Rizal"],
                            "Irosin": ["Bagsangan", "Bolos", "Gabas", "San Julian"],
                            "Juban": ["Anog", "Buraburan", "Lajong", "Maalo"],
                            "Matnog": ["Balocawe", "Barangay I", "Calpi", "Santa Maria"],
                            "Pilar": ["Balantay", "Binanuanan", "San Antonio", "San Jose"],

                            // Region VI - Western Visayas
                            "Kalibo": ["Andagao", "Bakhaw Norte", "Buswang New", "New Buswang"],
                            "Lezo": ["Agcawilan", "Bagto", "Cogon", "Santa Cruz"],
                            "Libacao": ["Agmailig", "Alamihan", "Cabalic", "Guadalupe"],
                            "Makato": ["Agbalogo", "Baybay", "Dumga", "Tigbawan"],
                            "Malinao": ["Banig", "Bigaa", "Cabayugan", "Carpenter Hill"],
                            "Nabas": ["Alimbo", "Bayan Norte", "Gibon", "Tolok"],
                            "New Washington": ["Candelaria", "Cawayan", "Dumlog", "Tambak"],
                            "Numancia": ["Albasan", "Bulwang", "Camanci Norte", "Poblacion"],
                            "Tangalan": ["Afga", "Baybay", "Dumatad", "Lanipga"],
                            "San Jose de Buenavista": ["Atabay", "Badiang", "Barangay 1", "San Pedro"],
                            "Belison": ["Borocboroc", "Del Pilar", "Maradiona", "Sinaja"],
                            "Bugasong": ["Anilawan", "Igsoro", "Jinalinan", "Lapnag"],
                            "Caluya": ["Sibato", "Sibay", "Tinogboc", "Tumarbong"],
                            "Hamtic": ["Apdo", "Igbical", "Pandan", "San Jose"],
                            "Lumbayanague": ["Bongbongan", "Canipayan", "Guintas", "Igpalge"],
                            "Patnongon": ["Alegre", "Bagumbayan", "Villa Cruz"],
                            "San Remigio": ["Aningalan", "Atabay", "Bagumbayan", "General Fullon"],
                            "Sibalom": ["Bari", "España", "Lazareto", "San Juan"],
                            "Tibiao": ["Alegre", "Bandoja", "Malabor", "Tuno"],
                            "Valderrama": ["Bakiang", "Borocboroc", "Cansilayan", "Lublub"],
                            "Roxas City": ["Baybay", "Culasi", "Dayao", "Tiza"],
                            "Ivisan": ["Agmalobo", "Balaring", "Basiao", "Mianay"],
                            "Jamindan": ["Agambulong", "Buri", "Caridad", "San Jose"],
                            "Maayon": ["Aglimocon", "Alangilan", "Cabungahan", "Lampaya"],
                            "Mambusao": ["Bailan", "Balat-an", "Bato", "Burias"],
                            "Panay": ["Bago Chiquito", "Bantique", "Cabugao", "Calitan"],
                            "President Roxas": ["Aranguel", "Badiangon", "Bayuyan", "Carmen"],
                            "Sapian": ["Agsilab", "Bilao", "Dapdapan", "Lonoy"],
                            "Sigma": ["Acbo", "Balucuan", "Dumarao", "Mangoso"],
                            "Tapaz": ["Agcococ", "Bato-bato", "Burirao", "Camburanan"],
                            "Jordan": ["Alaguisoc", "Balcon Maravilla", "Hoskyn", "San Miguel"],
                            "Buenavista": ["Agsanayan", "Avila", "Daragan", "San Roque"],
                            "Nueva Valencia": ["Cabalagnan", "Canhawan", "Igcawayan", "San Roque"],
                            "San Lorenzo": ["Aguilar", "Cabano", "Gabi", "San Enrique"],
                            "Santa Teresa": ["Bani", "Bondulan", "La Paz", "San Antonio"],
                            "Iloilo City": ["Arevalo", "Jaro", "Lapuz", "Mandurriao", "Molo"],
                            "Passi": ["Agdahon", "Agdayao", "Aglalana", "Agtabo"],
                            "San Jose": ["Aglubong", "Atabay", "Badiang"],
                            "Alimodian": ["Agsabutan", "Agsing", "Atabay", "Badiang"],
                            "Anilao": ["Agbatuan", "Badiang", "Barasan", "Cag-an"],
                            "Badiangan": ["Bita-oyan", "Cabanga-an", "Cabaruan", "Cayos"],
                            "Balasan": ["Bacolod", "Buri", "Cabilauan", "Gimamanay"],
                            "Banate": ["Alacaygan", "Bularan", "Carmelo", "De la Paz"],
                            "Barotac Nuevo": ["Acuit", "Agcuyayan", "Bagongbong", "Baras"],
                            "Barotac Viejo": ["Agaro", "Bugnay", "California", "Daja"],
                            "Bingawan": ["Agba-o", "Alabidhan", "Badiangan", "Malitbog"],
                            "Cabatuan": ["Acuit", "Bacan", "Burgos"],
                            "Calinog": ["Agcalaga", "Alibunan", "Badlan", "Binolosan"],
                            "Carles": ["Abong", "Alipata", "Asluman", "Bancal"],
                            "Concepcion": ["Aglalana", "Agnaga", "Bacjawan Norte", "Baliguian"],
                            "Dingle": ["Abangay", "Agsalanan", "Badiang", "Baras"],
                            "Duenas": ["Agcabugao", "Agdahon", "Badiang", "Barasan"],
                            "Estancia": ["Balabag", "Bayas", "Bulaqueña", "Lumboyan"],
                            "Guimbal": ["Badiang", "Bariri", "Bugnay", "Cata-an"],
                            "Igbaras": ["Alameda", "Bari", "Riro-an"],
                            "Janiuay": ["Aguilar", "Atimonan", "Badiang", "Barasalon"],
                            "Leganes": ["Gua-an", "Guihaman", "M.V. Hechanova", "Napnud"],
                            "Lemery": ["Agpipili", "Alcantara", "Anabo", "Badiang"],
                            "Leon": ["Agboy", "Agta", "Alangilan", "Ambulong"],
                            "Maasin": ["Badiang", "Bug-ot", "Bolo", "Cagban"],
                            "Miagao": ["Agdum", "Aguiauan", "Alimodias", "Bacauan"],
                            "Mina": ["Abat", "Agmanaphao", "Amiroy", "Badiang"],
                            "New Lucena": ["Badiang", "Balabag", "Bancal", "Bangcal"],
                            "Oton": ["Abilay Norte", "Abilay Sur", "Bago", "Balabago"],
                            "Pavia": ["Aganan", "Amparo", "Balabag", "Cabugao Norte"],
                            "Pototan": ["Amamaros", "Bacan", "Badiang", "Barasan"],
                            "San Dionisio": ["Agdaliran", "Badiang", "Barosong", "Cudian"],
                            "San Enrique": ["Bantayan", "Bantayan", "Bantayan", "Bantayan"],
                            "San Miguel": ["Agsalanan", "Badiang", "San Jose"],
                            "Santa Barbara": ["Agsungot", "Bariri", "Cabugao", "Cagay"],
                            "Sara": ["Agsalanan", "Badiang", "San Jose"],
                            "Tigbauan": ["Atabayan", "Bagacay", "Baguingin", "Bantud"],
                            "Tubungan": ["Agsalanan", "Badiang", "San Jose"],
                            "Zarraga": ["Agsirab", "Badiang", "Balud", "Binabaan"],
                            "Bacolod": ["Alijis", "Banago", "Taculing", "Tangub"],
                            "Bago": ["Abuanan", "Atipuluan", "Bacong", "Binubuhan"],
                            "Cadiz": ["Andres Bonifacio", "Burgos", "Cabahug", "Daga"],
                            "Escalante": ["Alimango", "Balintawak", "Binaguiohan", "Washington"],
                            "Himamaylan": ["Aguisan", "Buenavista", "Caradio-an", "Libacao"],
                            "Kabankalan": ["Bantayan", "Binicuil", "Camingawan", "Daan Banua"],
                            "La Carlota": ["Ara-al", "Ayungon", "Balabag", "Batuan"],
                            "Sagay": ["Bato", "Fabrica", "General Luna", "Old Sagay"],
                            "San Carlos": ["Bagonbon", "Buluangan", "Codcod", "Quezon"],
                            "Silay": ["Bagtic", "Balaring", "E. Lopez", "Guimbala-on"],
                            "Talisay": ["Amatyon", "Bacolod", "Bata", "Concepcion"],
                            "Binalbagan": ["Amontay", "Bi-ao", "Canmoros", "Enclaro"],
                            "Cauayan": ["Actol", "Bulanon", "Caliling", "Camalanda-an"],
                            "Enrique B. Magalona": ["Alaca", "Batea", "Canlusong", "Gahit"],
                            "Hinigaran": ["Anahaw", "Arroyo", "Bato", "Calapi"],
                            "Ilog": ["Andulauan", "Balicotoc", "Bocana", "Dancalan"],
                            "Isabela": ["Acan"],
                            "La Castellana": ["Ara-al", "Biaknabato", "Cabacungan", "Camandag"],
                            "Murcia": ["Amingan", "Bago", "Blumentritt", "Caliban"],
                            "Pontevedra": ["Antipolo"],
                            "Toboso": ["Bandila", "Bug-ang", "General Luna", "San Isidro"],
                            "Valladolid": ["Alicante", "Ayungon", "Bagumbayan", "Batuan"],

                            // Region VII - Central Visayas
                            "Tagbilaran": ["Bool", "Cogon", "Dampas", "Manga", "Taloto"],
                            "Alburquerque": ["Bahi", "Basacdacu", "Cantiguib", "Dangay"],
                            "Antequera": ["Angilan", "Bantolinao", "Canlaas", "Viga"],
                            "Baclayon": ["Buangan", "Guadalupe", "Laya", "San Isidro"],
                            "Balilihan": ["Baucan Norte", "Hinawanan", "Magsija", "Sagasa"],
                            "Bilar": ["Bonifacio", "Cabacnitan", "Cambigsi", "Villa Aurora"],
                            "Boking": ["Aurora", "San Isidro", "San Jose", "San Roque"],
                            "Catigbian": ["Alang-alang", "Ambuan", "Baang", "Mahayag"],
                            "Carmen": ["Bicao", "Buenavista", "Montesuerte", "Villa Garcia"],
                            "Danao": ["Cabatuan", "Cantubod", "Magtangtang", "San Miguel"],
                            "Dauis": ["Biking", "Mariveles", "Mayacabac", "Songculan"],
                            "Dimiao": ["Abihid", "Alemania", "Dait", "Pagsa"],
                            "Duero": ["Alegria", "Angilan", "Bangwalog", "Imelda"],
                            "Garcia Hernandez": ["Antipolo", "Cambuyo", "Tabuan", "Tawala"],
                            "Jagna": ["Alejal", "Balili", "Bunga Ilaya", "Cantagay"],
                            "Loay": ["Agape", "Bacabac", "Guinacot", "Tayong"],
                            "Loon": ["Basac", "Cogon Norte", "Mocpoc Norte", "Tangnan"],
                            "Mabini": ["Abaca", "Badiang", "Bulawan", "Cabidian"],
                            "Panglao": ["Bil-isan", "Bolod", "Danao", "Tawala"],
                            "Pilar": ["Aurora", "Bagacay", "Bayong", "Estaca"],
                            "Sierra Bullones": ["Abachanan", "Anibongan", "Bugtong", "Danicop"],
                            "Tubigon": ["Buenavista", "Cangawa", "Pooc Occidental", "Tinangnan"],
                            "Bogo": ["Anonang Norte", "Banban", "Cogon", "Nailon"],
                            "Carcar": ["Bolinawan", "Can-asujan", "Guadalupe", "Valladolid"],
                            "Cebu City": ["Banilad", "Guadalupe", "Lahug", "Mabolo", "Talamban"],
                            "Danao": ["Baliang", "Cabungahan", "Guinacot", "Taytay"],
                            "Lapu-Lapu City": ["Gun-ob", "Looc", "Pajo", "Pusok", "Suba-Basbas"],
                            "Mandaue": ["Alang-Alang", "Bakilid", "Banilad", "Casuntingan", "Subangdaku"],
                            "Talisay": ["Biasong", "Bulacao", "Camp IV", "Lawaan"],
                            "Toledo": ["Bato", "Biga", "Bulongan", "Cantabaco"],
                            "Argao": ["Balisong", "Bulasa", "Cansuje", "Langtad"],
                            "Barili": ["Azucena", "Bugnay", "Gunting", "Vito"],
                            "Boljoon": ["Baclayan", "El Pardo", "San Antonio", "Upper Becerril"],
                            "Buanoy": ["Bagtic", "Balamban", "Buanoy", "Lutopan"],
                            "Bantayan": ["Atop-atop", "Baigad", "Kabac", "Sulangan"],
                            "Pinamungahan": ["Anislag", "Balamban", "Cambang-ug", "Tubod"],
                            "Sogod": ["Bagatayam", "Bawo", "Cabangahan", "Damolog"],
                            "Tuburan": ["Antipolo", "Apalan", "Kabangkalan", "Mabunok"],
                            "Catmon": ["Amancion", "Bactas", "Cambangkaya", "San Jose"],
                            "Dumanjug": ["Balaygtiki", "Bitoon", "Bulak", "Kang-actol"],
                            "Tabuelan": ["Kanlunsing", "Maravilla", "Poblacion", "Tabunok"],
                            "Liloan": ["Cabadiangan", "Calero", "Catarman", "Cotcot"],
                            "Balamban": ["Abucayan", "Alihag", "Arpili", "Buanoy"],
                            "Bais": ["Basak", "Biñohon", "Capiñahan", "La Paz"],
                            "Bayawan": ["Ali-is", "Bangon", "Malabugas", "Nangka"],
                            "Canlaon": ["Bayog", "Budlasan", "Luz", "Panubigan"],
                            "Dumaguete": ["Bajumpandan", "Balugo", "Banilad", "Bantayan"],
                            "Guihulngan": ["Balogo", "Binobohan", "Bulado", "Planas"],
                            "Tanjay": ["Apoloy", "Luz", "Poblacion", "San Jose"],
                            "Apo": ["Basak", "Casile", "Mabini", "Talamban"],
                            "Ayungon": ["Amdus", "Jandalamanon", "Tambo", "Tigbawan"],
                            "Dauin": ["Anahawan", "Apo Island", "Bulak", "Masaplod"],
                            "La Libertad": ["Aniniao", "Candabong", "Mandapaton", "Poblacion"],
                            "Manjuyod": ["Alangilanan", "Bantolinao", "Lamogong", "Tupas"],
                            "Mabinay": ["Abanban", "Badiang", "Bulwang"],
                            "Zamboanguita": ["Basak", "Calango", "Luz", "Malongcay"],
                            "Siquijor": ["Cang-alwang", "Caipilan", "Caticugan", "Dumanhog"],
                            "Larena": ["Balolang", "Cangbagsa", "Nonoc", "Sandugan"],
                            "Lazi": ["Campalanas", "Cangclaran", "Gabayan", "Kimba"],
                            "Maria": ["Bogo", "Cabulihan", "Lico-an", "Poblacion Norte"],
                            "San Juan": ["Canasagan", "Cangmunag", "Lala-o", "Timbaon"],
                            "Santo Niño": ["Cang-adieng", "Cang-asa", "Poo", "Talingting"],

                            // Region VIII - Eastern Visayas
                            "Naval": ["Atipolo", "Cabungaan", "Libtong", "San Pablo"],
                            "Caibiran": ["Bari-is", "Binongto-an", "Kawayanon", "Looc"],
                            "Almeria": ["Ipil", "Jamorawon", "Matanga", "Sambawan"],
                            "Kawayan": ["Balacson", "Bilwang", "Masagongsong", "Ungale"],
                            "Biliran": ["Bato", "Busali", "Hugpa", "Pinangomhan"],
                            "Borongan": ["Ando", "Bato", "Cagbonga", "Songco"],
                            "Guiuan": ["Alang-alang", "Banaag", "Cagdara-o", "Pagnamitan"],
                            "Sulat": ["A-et", "Baybay", "Cantugi", "Del Pilar"],
                            "Balangkayan": ["Balogo", "Guinpoliran", "Poblacion", "Salcedo"],
                            "Oras": ["Alang-alang", "Bantayan", "Batangan", "Cadi-an"],
                            "Lawaan": ["Bolinao", "Guinob-an", "Maslog", "Taguite"],
                            "Ormoc": ["Cogon", "Can-adieng", "Linao", "San Pablo", "Valencia"],
                            "Tacloban": ["Abucay", "Anibong", "Basper", "San Jose", "V&G Subdivision"],
                            "Palo": ["Arado", "Baras", "Cogon", "San Fernando"],
                            "Tanauan": ["Arado", "Balo", "Canramos", "San Roque"],
                            "Jaro": ["Bungcas", "Caghalo", "Hiluctogan", "San Roque"],
                            "Carigara": ["Barugohay Norte", "Binibihan", "Hilaba", "Paglaum"],
                            "Baybay": ["Altavista", "Ambacan", "Balao", "Banahao"],
                            "Abuyog": ["Balinsasayao", "Bunga", "Can-uguib", "Pagsang-an"],
                            "Tolosa": ["Bantig", "Cantariwis", "Olot", "San Roque"],
                            "Julita": ["Burgos", "Calbasag", "Liberty", "San Isidro"],
                            "Alangalang": ["Anonang", "Cogon", "Poblacion", "San Antonio"],
                            "Catarman": ["Acedillo", "Bakolod", "Cal-igang", "Dalakit"],
                            "Allen": ["Bonifacio", "Cabacungan", "Erenas", "Victoria"],
                            "Bobon": ["Acereda", "Arellano", "Barobaybay", "Dancalan"],
                            "San Roque": ["Balnasan", "Guba", "Lapinig", "Malobago"],
                            "Palapag": ["Bagacay", "Bangon", "Binay", "Manajao"],
                            "Las Navas": ["Balugo", "Catoto-ogan", "Himaylan", "San Fernando"],
                            "Laoang": ["Abariongan Ruar", "Bulao", "Rawis", "Tarangnan"],
                            "Victoria": ["Acedillo", "Baliguian", "Maxvilla", "San Lazaro"],
                            "San Isidro": ["Happy Valley", "Liberty", "San Miguel", "Taft"],
                            "Lapinig": ["Barobaybay", "Hilaba", "San Miguel", "Tawagan"],
                            "Calbayog": ["Aguinaldo", "Baja", "Cagmanipis", "San Policarpo"],
                            "Catbalogan": ["Albalate", "Banga", "Cagudalo", "San Roque"],
                            "Jiabong": ["Bawang", "Cagmanipis", "Malino", "San Andres"],
                            "San Jorge": ["Albalate", "Bulao", "Cagmanipis", "San Isidro"],
                            "Matuguinao": ["Barobaybay", "Del Rosario", "San Isidro", "Socorro"],
                            "Hinabangan": ["Bagacay", "Canlangit", "San Roque", "Talahid"],
                            "Pinabacdao": ["Barobaybay", "Bugho", "San Roque", "Talahid"],
                            "Basey": ["Bacubac", "Balante", "Guirang", "Tulay"],
                            "Gandara": ["Adela Heights", "Bulao", "San Agustin", "San Roque"],
                            "Paranas": ["Bulao", "Cagmanipis", "San Isidro", "Talahid"],
                            "San Sebastian": ["Bulao", "Hinica-an", "San Isidro", "Talahid"],
                            "Villareal": ["Baliangao", "Banquil", "San Roque", "Talahid"],
                            "Maasin": ["Abgao", "Asuncion", "Bactul I", "Canturing"],
                            "Sogod": ["Bagakay", "Bawo", "Cantag-ok", "San Miguel"],
                            "Liloan": ["Amaga", "Cogon", "San Isidro", "San Roque"],
                            "San Juan": ["Agsayao", "Basak", "San Roque", "Santa Cruz"],
                            "Bontoc": ["Banahao", "Bongawon", "San Roque", "Taa"],
                            "Tomas Oppus": ["Bogo", "Cabulihan", "San Isidro", "San Roque"],
                            "Silago": ["Balagawan", "Catmon", "Hingatungan", "Poblacion"],
                            "Libagon": ["Alegria", "Cali", "San Roque", "Taa"],
                            "San Ricardo": ["Benit", "San Roque", "San Ramon", "Taa"],
                            "Hinunangan": ["Ambacon", "Badiangon", "San Roque", "Taa"],
                            "Anahawan": ["Amagusan", "San Roque", "Taa", "Talahid"],

                            // Region IX - Zamboanga Peninsula
                            "Dapitan": ["Aliguay", "Ba-ao", "Banbanan", "San Pedro"],
                            "Dipolog": ["Central", "Estaka", "Galas", "Minaog", "Turno"],
                            "Polanco": ["Anonang", "Bachawan", "Milad", "New Sicayab"],
                            "Sindangan": ["Bago", "Bantayan", "Buluang", "Dagohoy"],
                            "Roxas": ["Bagong Silang", "Birayan", "Dohinob", "San Miguel"],
                            "Manukan": ["Bunawan", "Don Jose Aguirre", "Poblacion", "Siparok"],
                            "Liloy": ["Baybay", "Cabangcalan", "Goaw", "Tapican"],
                            "Siocon": ["Andres Bonifacio", "Bantayan", "Bulacan", "Maligay"],
                            "Labason": ["Antonino", "Balas", "Imelda", "San Isidro"],
                            "Jose Dalman": ["Bitoon", "Dinasan", "Poblacion", "San Jose"],
                            "Pagadian": ["Balangasan", "Dao", "San Pedro", "Sta. Lucia", "Tiguma"],
                            "Zamboanga": ["Baliwasan", "Camins", "Canelar", "Sta. Maria", "Tetuan", "San Jose Gusu", "Talon-Talon", "Putik", "Tugbungan", "Tumaga", "Boalan", "Mercedes", "Guiwan", "San Roque", "Ayala", "Mampang", "Lunzuran", "Calarian", "Vitali", "Divisoria"],
                            "Molave": ["Buenavista", "Don Eleno", "Gatas", "Tambulig"],
                            "Tukuran": ["Baclay", "Buenasuerte", "Camanga", "Laperian"],
                            "Aurora": ["Bagong Mandaue", "Balas", "Gubaan", "San Jose"],
                            "Dumingag": ["Bag-ong Valencia", "Banban", "Dapiwak", "San Juan"],
                            "Lakewood": ["Bagong Kahayag", "Barko", "Gasa", "Sebucawan"],
                            "Ramon Magsaysay": ["Bagong Oroquieta", "Bambong", "Dipalusan", "San Fernando"],
                            "San Miguel": ["Bucong", "Calube", "Lantawan", "San Roque"],
                            "Tabina": ["Baganian", "Baya-baya", "Capisan", "San Jose"],
                            "Ipil": ["Baliwasan", "Banga", "Buluan", "Sanito"],
                            "Kabasalan": ["Baloan", "Buenavista", "Culasi", "Tamin"],
                            "Imelda": ["Balugo", "Dumpoc", "Lumbog", "Poblacion"],
                            "Buug": ["Baton", "Bulaan", "Guintuloan", "Poblacion"],
                            "Diplahan": ["Balangao", "Butong", "Gandiangan", "Poblacion"],
                            "Payao": ["Balian", "Balogo", "Guintolan", "Poblacion"],
                            "Roseller T. Lim": ["Balagon", "Kaumpurnah", "Little Margos", "Talisayan"],
                            "Titay": ["Balagon", "Bangan", "New Canaan", "Poblacion"],
                            "Mabuhay": ["Bulanay", "Caliran", "Catipan", "Poblacion"],
                            "Talusan": ["Batu", "Bula", "Poblacion", "Santo Niño"],

                            // Region X - Northern Mindanao
                            "Malaybalay": ["Bangcud", "Casisang", "Laguitas", "Sumpong"],
                            "Valencia": ["Bagontaas", "Banlag", "Lilingayon", "San Carlos"],
                            "Manolo Fortich": ["Alae", "Dahilayan", "Kalugmanan", "San Miguel"],
                            "Maramag": ["Base Camp", "Dampil", "North Poblacion", "San Miguel"],
                            "Quezon": ["Butong", "Cebole", "Kiburiao", "Salawagan"],
                            "Don Carlos": ["Don Carlos Sur", "Kalubihon", "Maraymaray", "New Nongnongan"],
                            "Kibawe": ["Balinon", "Pinamula", "Poblacion", "Sagundanon"],
                            "Malitbog": ["Kalingking", "Kiabo", "San Luis", "Siloo"],
                            "Talakag": ["Casisang", "Indulang", "Lantud", "San Miguel"],
                            "Mambajao": ["Agoho", "Benhaan", "Soro-soro", "Yumbing"],
                            "Catarman": ["Bonbon", "Kumparat", "Poblacion", "Sagay"],
                            "Guinsiliban": ["Butay", "Cabuan", "Poblacion", "Tupsan"],
                            "Mahinog": ["Benoni", "Owakan", "Poblacion", "Tupsan Pequeño"],
                            "Sagay": ["Alangilan", "Poblacion", "Tupsan", "Yanangan"],
                            "Iligan": ["Hinaplanon", "Pala-o", "San Miguel", "Tibanga", "Tambacan"],
                            "Tubod": ["Barakanas", "Poblacion", "Simsiman", "Tominobo"],
                            "Kapatagan": ["Poblacion", "San Isidro", "San Vicente", "Sultan Gumander"],
                            "Baroy": ["Baroy Daku", "Poblacion", "Princesa", "San Juan"],
                            "Kolambugan": ["Poblacion", "San Roque", "Santa Fe", "Tabigue"],
                            "Lala": ["Poblacion", "San Isidro", "San Manuel", "Santa Cruz"],
                            "Balo-i": ["Poblacion", "Rogongon", "San Miguel", "Santa Elena"],
                            "Linamon": ["Poblacion", "Robocon", "San Roque", "Santa Cruz"],
                            "Oroquieta": ["Binuangan", "Langcangan", "San Vicente Alto", "Toliyok"],
                            "Ozamiz": ["Bagakay", "Capucao C.", "Gango", "Stimson Abordo"],
                            "Tangub": ["Balatacan", "Mantic", "Santa Maria", "Silanga"],
                            "Clarin": ["Buru-un", "Pan-ay", "Plaridel", "Poblacion"],
                            "Jimenez": ["Butuay", "Dicoloc", "North Poblacion", "San Isidro"],
                            "Plaridel": ["Agunod", "Looc", "Santa Cruz", "Tipolo"],
                            "Calamba": ["Bonifacio", "Southwestern Poblacion", "Sulpoc", "Villacorta"],
                            "Bonifacio": ["Bagumbang", "Baybay", "San Juan", "San Pedro"],
                            "Cagayan de Oro": ["Balulang", "Carmen", "Kauswagan", "Macasandig", "Nazareth"],
                            "El Salvador": ["Cogon", "Himaya", "Kalabaylabay", "Molugan"],
                            "Gingoog": ["Bal-ason", "Daan Lungsod", "Kalipay", "San Juan"],
                            "Tagoloan": ["Baluarte", "Natumbacan", "Poblacion", "Santa Ana"],
                            "Villanueva": ["Dayawan", "Imelda", "Katipunan", "San Martin"],
                            "Jasaan": ["Aplaya", "Bobontugan", "San Antonio", "San Nicolas"],
                            "Balingasag": ["Baliwagan", "San Francisco", "San Juan", "Waterfall"],
                            "Opol": ["Barra", "Poblacion", "Tingalan", "Tuboran"],

                            // Region XI - Davao Region
                            "Nabunturan": ["Anislagan", "Basak", "Magsaysay", "New Sibonga"],
                            "Monkayo": ["Awao", "Babag", "Casoon", "Mount Diwata"],
                            "Maco": ["Elizalde", "Malamodao", "New Leyte", "Panibasan"],
                            "Pantukan": ["Kingking", "Magnaga", "Matiao", "Tibagon"],
                            "Compostela": ["Bagongon", "Cabadiangan", "San Miguel", "Tamia"],
                            "Tagum": ["Apokon", "Magugpo East", "Magugpo West", "Mankilam", "San Miguel"],
                            "Panabo": ["Datu Abdul Dadia", "Gredu", "J.P. Laurel", "San Pedro"],
                            "Samal": ["Adecor", "Balet", "Peñaplata", "San Miguel"],
                            "Carmen": ["Alejal", "Asuncion", "Magsaysay", "Tuganay"],
                            "Santo Tomas": ["Balagunan", "Bobongon", "Kinamayan", "New Katipunan"],
                            "Davao City": ["Bajada", "Buhangin", "Catalunan Grande", "Matina", "Talomo"],
                            "Digos": ["Aplaya", "Cogon", "San Miguel", "Tres de Mayo"],
                            "Bansalan": ["Alegre", "Dugong", "Marber", "Poblacion"],
                            "Hagonoy": ["Balutakay", "Guihing", "San Isidro", "Sinayawan"],
                            "Sta. Cruz": ["Astorga", "Bato", "Coronon"],
                            "Malita": ["Bito", "Bolila", "Culaman", "Talogoy"],
                            "Santa Maria": ["Basiawan", "Bitan-ag", "Poblacion", "San Agustin"],
                            "Jose Abad Santos": ["Balangonan", "Culaman", "Malalan", "Poblacion"],
                            "Don Marcelino": ["Calian", "Lanao", "North Lamidan", "Talaguton"],
                            "Sarangani": ["Batuganding", "Konel", "Mabila", "Tuyan"],
                            "Mati": ["Badas", "Dahican", "Don Martin Marundan", "Sainz"],
                            "Baganga": ["Baculin", "Batiano", "Cateel", "San Victor"],
                            "Cateel": ["Aliwagwag", "Mainit", "San Alfonso", "San Miguel"],
                            "Lupon": ["Bagumbayan", "Don Mariano Marcos", "San Isidro", "Tagboa"],
                            "Boston": ["Cabasagan", "Caiban", "Simulao", "Zaragoza"],

                            // Region XII - SOCCSKSARGEN
                            "Kidapawan": ["Amas", "Balabag", "Ginatilan", "Ilomavis"],
                            "Midsayap": ["Central Glad", "Kapinpilan", "Olandang", "Sadaan"],
                            "Pigcawayan": ["Anick", "Balogo", "Simsiman", "Upper Baguer"],
                            "Kabacan": ["Aringay", "Bangilan", "Cuyapon", "Osias"],
                            "Carmen": ["Bentangan", "Kimadzil", "Lampagang", "Tupig"],
                            "President Roxas": ["Bato-bato", "Cabangbangan", "Datu Indang", "Greenhills"],
                            "Makilala": ["Batasan", "Guangan", "Kisante", "New Bulatukan"],
                            "Tulunan": ["Bagumbayan", "Bituan", "Daig", "Galidan"],
                            "Alabel": ["Bagacay", "Datal Anggas", "Kawas", "Spring"],
                            "Glan": ["Baliton", "Burias", "Cross", "E. Alegado"],
                            "Kiamba": ["Badtasan", "Gasi", "Kling", "Luma"],
                            "Maasim": ["Amsipit", "Kanalo", "Lumasal", "Nomoh"],
                            "Maitum": ["Batian", "Kalaneg", "Kiambing", "Ticulab"],
                            "Malapatan": ["Daan Suyan", "Kinam", "Lun Masla", "Tuyan"],
                            "Malungon": ["Ampon", "Datal Batong", "Kiblat", "Tamban"],
                            "General Santos": ["Bula", "City Heights", "Fatima", "Lagao", "San Isidro"],
                            "Koronadal": ["San Isidro", "General Paulino Santos"],
                            "Polomolok": ["Bentung", "Cannery", "Klinan", "Rubber"],
                            "Tupi": ["Acmonan", "Klinan", "Linan", "Polonuling"],
                            "Surallah": ["Banga", "Centrala", "Colongulo", "Lambontong"],
                            "Lake Sebu": ["Bacdulong", "Datal Tebew", "Halilan", "Lamlifew"],
                            "Tantangan": ["Bukay Pait", "New Cuyapo", "San Felipe", "Tinongcop"],
                            "T'boli": ["Basag", "Datal Bob", "Kematu", "Lambangan"],
                            "Tacurong": ["Baras", "Griño", "New Isabela", "San Pablo"],
                            "Isulan": ["Bambad", "Kolambog", "Sampao", "Tayugo"],
                            "President Quirino": ["Bagumbayan", "Bannawag", "Tual", "Villamor"],
                            "Lambayong": ["Didtaras", "Galigayan", "Poblacion", "Sadsalan"],
                            "Columbio": ["Datalblao", "Eday", "Lasak", "Telafas"],
                            "Lutayan": ["Bayasong", "Lutayan Proper", "Maindang", "Sampao"],
                            "Esperanza": ["Alagao", "Daladap", "Guiamalia", "Margues"],
                            "Bagumbayan": ["Bai Sarifinang", "Biwang", "Chua", "Sison"],
                            "Palimbang": ["Badiangon", "Baranayan", "Kulong-kulong", "Mileb"],
                            "Sen. Ninoy Aquino": ["Banali", "Basag", "Kuden", "Tinalon"],

                            // Region XIII - Caraga
                            "Butuan": ["Ampayon", "Baan Km. 3", "Bading", "Bancasi", "San Vicente"],
                            "Cabadbaran": ["Antonio Luna", "Bay-ang", "Comagascas", "Mahaba"],
                            "Nasipit": ["Aclan", "Amontay", "Culit", "Talamban"],
                            "Carmen": ["Baliangao", "Poblacion", "Rojales", "Tagcatong"],
                            "Santiago": ["Curva", "Jubilan", "La Paz", "Poblacion"],
                            "Kitcharao": ["Bangayan", "Hinimbangan", "San Isidro", "Songkoy"],
                            "Bayugan": ["Bitan-agan", "Claro Cortez", "Poblacion", "Sagmone"],
                            "Prosperidad": ["Awa", "La Caridad", "La Suerte", "Patin-ay"],
                            "San Francisco": ["Alang-alang", "Borbon", "Ebro", "Orquia"],
                            "Esperanza": ["Agusan Pequeño", "Guibonon", "Kinamaybay", "New Gingoog"],
                            "Trento": ["Basag", "Cuevas", "Manat", "Poblacion"],
                            "San Jose": ["Angas", "Pag-asa", "San Juan", "Santa Cruz"],
                            "San Jose": ["Aurelio", "Cuarinta", "Don Ruben", "Justiniana Edera"],
                            "Basilisa": ["Bermudez", "Columbus", "Corazon", "Santa Cruz"],
                            "Loreto": ["Carmen", "Esperanza", "Libertad", "Sta. Cruz"],
                            "Cagdianao": ["Cabunga-an", "Del Pilar", "Laguna", "Poblacion"],
                            "Libjo": ["Albor", "Arellano", "Bayan", "Magsaysay"],
                            "Surigao City": ["Alegria", "Canlanipa", "Lipata", "San Juan", "Taft"],
                            "Placer": ["Bogtong-bogtong", "Esmoran", "Mabini", "Pananay-an"],
                            "Mainit": ["Cantugas", "Dayano", "Magpayang", "Mansayao"],
                            "Dapa": ["Bagakay", "Buenavista", "Union"],
                            "Del Carmen": ["Antipolo", "Bagakay", "Domoyog", "San Fernando"],
                            "Socorro": ["Albino Taruc", "Honrado", "Pamosaingan", "Salog"],
                            "Bislig": ["Bucto", "Caguyao", "Lawigan", "San Fernando"],
                            "Tandag": ["Bag-ong Lungsod", "Buenavista", "Dagocdoc", "Telaje"],
                            "Lianga": ["Anibongan", "Ban-as", "Diatagon", "Ganayon"],
                            "San Miguel": ["Bagyang", "Baras", "Bitaugan", "Libas Sud"],
                            "Cantilan": ["Cabangahan", "Cagwait", "General Island", "Magasang"],
                            "Tagbina": ["Batunan", "Carpenito", "Mabuhay", "San Vicente"],

                            // CAR - Cordillera Administrative Region
                            "Bangued": ["Baliling", "Bangbangar", "Cabuloan", "Calaba"],
                            "Lagayan": ["Collago", "Pang-ot", "Pulot"],
                            "Tayum": ["Bagalay", "Basbasa", "Budac"],
                            "Pilar": ["Bolbolo", "Dintan", "Ocup"],
                            "Boliney": ["Amti", "Ba-ayan", "Danac East"],
                            "Licuan-Baay": ["Bulbulala", "Cawayan", "Domenglay"],
                            "Kabugao": ["Badduat", "Bulut", "Dagara"],
                            "Conner": ["Caglayan", "Karikitan", "Malama"],
                            "Pudtol": ["Emilia", "Lower Maton", "San Jose"],
                            "Flora": ["Allig", "Mallig", "Tamalunog"],
                            "Santa Marcela": ["Barocboc", "Imelda", "Panay"],
                            "Luna": ["Bacsay", "Cagandungan", "Turod"],
                            "Calanasan": ["Butao", "Eleazar", "Kabugawan"],
                            "Baguio": ["Camp Allen", "Engineers' Hill", "Loakan Proper", "Session Road Area", "Upper Quezon Hill"],
                            "La Trinidad": ["Alapang", "Ambiong", "Balili", "Pico"],
                            "Itogon": ["Dalupirip", "Gumatdang", "Tuding"],
                            "Tuba": ["Ansagan", "Camp 1", "Nangalisan"],
                            "Tublay": ["Ambongdolan", "Ba-ayan", "Tuel"],
                            "Bakun": ["Ampusongan", "Dalipey", "Sinacbat"],
                            "Kapangan": ["Balaney", "Beleng-Belis", "Sagubo"],
                            "Lagawe": ["Ambiong", "Bangaan", "Tupaya"],
                            "Kiangan": ["Ambabag", "Nagacadan", "Tuplac"],
                            "Lamut": ["Ambasa", "Hapid", "Tupaya"],
                            "Mayoyao": ["Bongan", "Chaya", "Tulaed"],
                            "Hungduan": ["Abatan", "Bangbang", "Taba-ao"],
                            "Banaue": ["Batad", "Bocos", "Viewpoint"],
                            "Aguinaldo": ["Awayan", "Buwag", "Ubao"],
                            "Tabuk": ["Agbannawag", "Appas", "Bulanao"],
                            "Rizal": ["Babbalag", "Calanan", "San Pascual"],
                            "Pinukpuk": ["Apatan", "Ba-ay", "Taga"],
                            "Balbalan": ["Ababa-an", "Balantoy", "Tawang"],
                            "Lubuagan": ["Dangoy", "Mabilong", "Tanglag"],
                            "Tanudan": ["Anggacan", "Gaang", "Tucucan"],
                            "Pasil": ["Balatoc", "Dangtalan", "Talga"],
                            "Bontoc": ["Alab Proper", "Gonogon", "Samoki"],
                            "Sagada": ["Ambasing", "Demang", "Taccong"],
                            "Besao": ["Agawa", "Banguitan", "Kin-iway"],
                            "Barlig": ["Chupac", "Lias", "Tocucan"],
                            "Sadanga": ["Belwang", "Saclit", "Sagada"],
                            "Tadian": ["Balaoa", "Sumadel", "Tue"],
                            "Sabangan": ["Bauko", "Camatagan", "Supang"],
                            "Natonin": ["Banao", "Saliok", "Tucucan"],

                            // BARMM - Bangsamoro Autonomous Region in Muslim Mindanao
                            "Isabela City": ["Balatanay", "Carbon", "Maligue", "Panigayan", "Santa Barbara"],
                            "Lamitan": ["Balobo", "Colonia", "Maligaya", "Ubit"],
                            "Maluso": ["Bato", "Gusu", "Townsite"],
                            "Tipo-Tipo": ["Baguindan", "Banah", "Silangkum"],
                            "Sumisip": ["Bangkaw-Bangkaw", "Sulloh", "Tumahubong"],
                            "Marawi": ["Bangon", "Kapantaran", "Marinaut", "Sagonsongan", "Saduc Proper"],
                            "Balindong": ["Bantoga", "Cadayonan", "Salam"],
                            "Bayang": ["Bagoaingud", "Raya", "Sapad"],
                            "Malabang": ["Banday", "Betayan", "Rebocon"],
                            "Wao": ["Balatin", "Western Wao", "Extension"],
                            "Datu Odin Sinsuat": ["Badak", "Capiton", "Dalican", "Semba"],
                            "Barira": ["Bagoaingud", "Korondal", "Lipawan"],
                            "Parang": ["Bongo Island", "Polloc", "Socorro"],
                            "Buldon": ["Calaan", "Mataya", "Neriong"],
                            "Northern Kabuntalan": ["Balong", "Dadtumog", "Tumbao"],
                            "Shariff Aguak": ["Labu-Labu", "Sina-it", "Timbangan"],
                            "Buluan": ["Digal", "Takepan", "Tumbao"],
                            "Datu Saudi-Ampatuan": ["Dapiawan", "Elian", "Sapakan"],
                            "Rajah Buayan": ["Damabalas", "Sampao", "Tuka"],
                            "Datu Paglas": ["Damawato", "Sapakan", "Tuka"],
                            "Jolo": ["Alat", "Chinese Pier", "San Raymundo", "Tulay"],
                            "Patikul": ["Danag", "Kabbon Takas", "Tandu-Bagua"],
                            "Indanan": ["Buanza", "Tubig Dakulah", "Tubig Parang"],
                            "Maimbung": ["Bualo Lahi", "Tandu Patong", "Tubig Indangan"],
                            "Parang": ["Bato-Bato", "Tandu Banak", "Tubig Gantang"],
                            "Bongao": ["Lamion", "Pahut", "Simandagit"],
                            "Panglima Sugala": ["Balimbing Proper", "Sipangkot", "Tongbangkaw"],
                            "Simunul": ["Bakong", "Tonggasang", "Tubig Indangan"],
                            "Sitangkai": ["Sipangkot", "Tongmageng", "Tongusong"],
                            "South Ubian": ["Pandakan", "Tandubas", "Tong"],
                            "Cotabato City": ["Bagua I", "Bagua II", "Rosary Heights I", "Rosary Heights VII"]
                        };


    // Initialize the province dropdown as disabled
    $('#eventProvince').prop('disabled', true);
    $('#eventCity').prop('disabled', true);
    $('#eventBarangay').prop('disabled', true);

    // Region change handler
    $('#eventRegion').change(function() {
        const selectedRegion = $(this).val();
        const provinces = regions[selectedRegion] || [];

        $('#eventProvince').empty().append('<option value="">Select Province</option>');

        if (provinces.length > 0) {
            provinces.forEach(function(province) {
                $('#eventProvince').append($('<option>', { value: province, text: province }));
            });
            $('#eventProvince').prop('disabled', false);
            
            // Reset city and barangay when region changes
            $('#eventCity').empty().append('<option value="">Select City</option>').prop('disabled', true);
            $('#eventBarangay').empty().append('<option value="">Select Barangay</option>').prop('disabled', true);
        } else {
            $('#eventProvince').prop('disabled', true);
            $('#eventCity').prop('disabled', true);
            $('#eventBarangay').prop('disabled', true);
        }
    });

    // Province change handler
    $('#eventProvince').change(function() {
        const selectedProvince = $(this).val();
        const cities = provinceCities[selectedProvince] || [];

        $('#eventCity').empty().append('<option value="">Select City</option>');

        if (cities.length > 0) {
            cities.forEach(function(city) {
                $('#eventCity').append($('<option>', { value: city, text: city }));
            });
            $('#eventCity').prop('disabled', false);
            
            // Reset barangay when province changes
            $('#eventBarangay').empty().append('<option value="">Select Barangay</option>').prop('disabled', true);
        } else {
            $('#eventCity').prop('disabled', true);
            $('#eventBarangay').prop('disabled', true);
        }
    });

    // City change handler
    $('#eventCity').change(function() {
        const selectedCity = $(this).val();
        const barangays = cityBarangays[selectedCity] || [];

        $('#eventBarangay').empty().append('<option value="">Select Barangay</option>');

        if (barangays.length > 0) {
            barangays.forEach(function(barangay) {
                $('#eventBarangay').append($('<option>', { value: barangay, text: barangay }));
            });
            $('#eventBarangay').prop('disabled', false);
        } else {
            $('#eventBarangay').prop('disabled', true);
        }
    });

    // Initialize form based on existing values (for edit mode)
    const currentRegion = '<?= $event->eventRegion ?>';
    if (currentRegion) {
        $('#eventRegion').val(currentRegion).trigger('change');
        
        // Wait a bit for province dropdown to populate
        setTimeout(function() {
            const currentProvince = '<?= $event->eventProvince ?>';
            if (currentProvince) {
                $('#eventProvince').val(currentProvince).trigger('change');
                
                // Wait a bit for city dropdown to populate
                setTimeout(function() {
                    const currentCity = '<?= $event->eventCity ?>';
                    if (currentCity) {
                        $('#eventCity').val(currentCity).trigger('change');
                        
                        // Wait a bit for barangay dropdown to populate
                        setTimeout(function() {
                            const currentBarangay = '<?= $event->eventBarangay ?>';
                            if (currentBarangay) {
                                $('#eventBarangay').val(currentBarangay);
                            }
                        }, 100);
                    }
                }, 100);
            }
        }, 100);
    }
});

function validateForm() {
    var selectedLibrarians = document.querySelectorAll('input[name="librarianIDs[]"]:checked');
    if (selectedLibrarians.length === 0) {
        alert('Please select at least one event facilitator.');
        return false;
    }
    return true;
}
</script>
</body>
</html>