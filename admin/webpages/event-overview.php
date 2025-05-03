<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user'] != 'admin'){
    header('location: ./index.php');
    exit; // Make sure to exit after a header redirect
}

require_once '../classes/eventoverview.class.php';

// Check if eventID is set in the GET parameters
if(isset($_GET['eventID'])){
    $event =  new Eventoverview();
    $eventRecord = $event->fetch($_GET['eventID']);
    if ($eventRecord) { // Check if event record is fetched successfully
        $event->eventID = $eventRecord['eventID'];
        $event->eventTitle= $eventRecord['eventTitle'];
        $event->eventStartDate= $eventRecord['eventStartDate'];
        $event->eventEndDate= $eventRecord['eventEndDate'];
        $event->eventStartTime= $eventRecord['eventStartTime'];
        $event->eventEndTime= $eventRecord['eventEndTime'];
        $event->eventGuestLimit= $eventRecord['eventGuestLimit'];      
        $event->eventBuildingName= $eventRecord['eventBuildingName'];
        $event->eventStreetName= $eventRecord['eventStreetName'];
        $event->eventBarangay= $eventRecord['eventBarangay'];
        $event->eventCity= $eventRecord['eventCity'];
        $event->eventProvince= $eventRecord['eventProvince'];
        $event->eventRegion= $eventRecord['eventRegion'];
        $event->eventZipCode= $eventRecord['eventZipCode'];
        $eventFacilitators = $event->getEventFacilitator($_GET['eventID']);
        $event->eventStatus= $eventRecord['eventStatus'];
        $event->eventDescription= $eventRecord['eventDescription'];
        $registrants = $event->getEventRegistrant($_GET['eventID']);
        $participants = $event->getEventParticipant($_GET['eventID']);
        $volunteers = $event->getEventVolunteers($_GET['eventID']);
    } else {
        // Handle the case where the event record is not found
        echo "Event not found.";
        exit; // Stop execution
    }
} else {
    // Handle the case where eventID is not set
    echo "Event ID not provided.";
    exit; // Stop execution
}
?>

<!DOCTYPE html>
<html lang="en">

<?php
  $title = 'Event Overview'; // Set the correct title here
  $events = 'active-1';
  require_once('../include/head.php');
?>

<body>


    <div class="main">
        <div class="row">
            <?php
                require_once('../include/nav-panel.php');
            ?>

            <div class="col-12 col-md-7 col-lg-9">
                
                <div class="row pt-4 ps-4">
                    <div class="col-12 dashboard-header d-flex align-items-center justify-content-between">
                            <div class="heading-name d-flex">
                            <button class="back-btn me-4">
                            <a href="../webpages/events.php" class="d-flex align-items-center">
                                    <i class='bx bx-arrow-back pe-3 back-icon'></i>
                                    <span class="back-text">Back</span>
                                </a>
                            </button>

                                <p class="pt-3">Event Overview</p>
                            </div>

                           

                    </div>

                    <div class="row ps-2">
                        
                    <div class="col-12">
                        <?php if (isset($_GET['eventID'])): ?>
                            <div class="accordion compact-accordion" id="eventAccordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingEventInfo">
                                        <button class="accordion-button py-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEventInfo" aria-expanded="true" aria-controls="collapseEventInfo">
                                            <i class="bi bi-calendar-event me-2"></i> Event Details
                                        </button>
                                    </h2>
                                    <div id="collapseEventInfo" class="accordion-collapse collapse show" aria-labelledby="headingEventInfo" data-bs-parent="#eventAccordion">
                                        <div class="accordion-body p-3">
                                            <div class="row">
                                                <!-- Left Column - Event Details -->
                                                <div class="col-md-6 pe-3">
                                                    <!-- Event Title -->
                                                    <div class="row mb-2">
                                                        <div class="col-12">
                                                            <div class="d-flex align-items-start">
                                                                <span class="label-club pe-2">Title:</span>
                                                                <span><?php echo htmlspecialchars($event->eventTitle); ?></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Date & Time -->
                                                    <div class="row mb-2">
                                                        <div class="col-12">
                                                            <div class="d-flex align-items-start">
                                                                <span class="label-club pe-2">When:</span>
                                                                <span><?php 
                                                                    $startDate = date('M j, Y', strtotime($event->eventStartDate));
                                                                    $endDate = date('M j, Y', strtotime($event->eventEndDate));
                                                                    $startTime = date('g:i A', strtotime($event->eventStartTime));
                                                                    $endTime = date('g:i A', strtotime($event->eventEndTime));
                                                                    echo htmlspecialchars($startDate);
                                                                    if ($startDate != $endDate) {
                                                                        echo ' - ' . htmlspecialchars($endDate);
                                                                    }
                                                                    echo ' • ' . htmlspecialchars($startTime) . ' - ' . htmlspecialchars($endTime); 
                                                                ?></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Venue -->
                                                    <div class="row mb-2">
                                                        <div class="col-12">
                                                            <div class="d-flex align-items-start">
                                                                <span class="label-club pe-2">Where:</span>
                                                                <span><?php
                                                                    $venueParts = array();
                                                                    if ($event->eventBuildingName) $venueParts[] = htmlspecialchars($event->eventBuildingName);
                                                                    if ($event->eventStreetName) $venueParts[] = htmlspecialchars($event->eventStreetName);
                                                                    if ($event->eventBarangay) $venueParts[] = htmlspecialchars($event->eventBarangay);
                                                                    if ($event->eventCity) $venueParts[] = htmlspecialchars($event->eventCity);
                                                                    if ($event->eventProvince) $venueParts[] = htmlspecialchars($event->eventProvince);
                                                                    if ($event->eventRegion) $venueParts[] = htmlspecialchars($event->eventRegion);
                                                                    if ($event->eventZipCode) $venueParts[] = htmlspecialchars($event->eventZipCode);
                                                                    echo implode(', ', $venueParts);
                                                                ?></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Status -->
                                                    <div class="row mb-2">
                                                        <div class="col-12">
                                                            <div class="d-flex align-items-center">
                                                                <span class="label-club pe-2">Status:</span>
                                                                <span class="badge bg-<?php 
                                                                    switch($event->eventStatus) {
                                                                        case 'Approved': echo 'success'; break;
                                                                        case 'Pending': echo 'warning'; break;
                                                                        case 'Rejected': echo 'danger'; break;
                                                                        default: echo 'secondary';
                                                                    }
                                                                ?>"><?php echo htmlspecialchars($event->eventStatus); ?></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Facilitators -->
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <div class="d-flex align-items-start">
                                                                <span class="label-club pe-2">Facilitators:</span>
                                                                <div class="d-flex flex-wrap">
                                                                    <?php foreach ($eventFacilitators as $i => $facilitator): ?>
                                                                        <?php 
                                                                            $middleInitial = $facilitator['librarianMiddleName'] ? substr($facilitator['librarianMiddleName'], 0, 1) . '.' : '';
                                                                            $name = htmlspecialchars($facilitator['librarianFirstName'] . ' ' . $middleInitial . ' ' . $facilitator['librarianLastName']);
                                                                        ?>
                                                                        <span class="me-2"><?php echo $name; ?></span>
                                                                        <?php if ($i < count($eventFacilitators) - 1): ?><span class="me-2">•</span><?php endif; ?>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Right Column - Description -->
                                                <div class="col-md-6 ps-3 border-start">
                                                    <div class="d-flex flex-column h-100">
                                                        <h6 class="mb-2 fw-bold text-start">Event Description</h6>
                                                        <div class="event-description small flex-grow-1 text-start">
                                                            <?php echo nl2br(htmlspecialchars($event->eventDescription)); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning" role="alert">
                                Event ID not provided.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link tab-label active" id="event-registrants-tab" data-bs-toggle="tab" data-bs-target="#event-registrants" type="button" role="tab" aria-controls="event-registrants" aria-selected="true">Registrants</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link tab-label" id="event-participants-tab" data-bs-toggle="tab" data-bs-target="#event-participants" type="button" role="tab" aria-controls="event-participants" aria-selected="false">Participants</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link tab-label" id="event-volunteers-tab" data-bs-toggle="tab" data-bs-target="#event-volunteers" type="button" role="tab" aria-controls="event-volunteers" aria-selected="false">Volunteers</button>
                        </li>
                    </ul> 

                    <div class="tab-content" id="myTabContent">
                        <!-- Registrants -->
                        <div class="tab-pane fade show active pt-3" id="event-registrants" role="tabpanel" aria-labelledby="event-registrants-tab">
                        <div class="dropdown d-flex">
                                <button class="btn download-btn dropdown-toggle" type="button" id="downloadDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    Download
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="downloadDropdown">
                                    <li><a class="dropdown-item" href="#" onclick="downloadAsPdfEvents()">Download as PDF</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="downloadAsExcelEvents()">Download as Excel</a></li>
                                </ul>
                            </div>
                            <div class="table-responsive mt-2 me-4">
                                
                                <table id="kt_datatable_both_scrolls" class="table table-striped table-row-bordered gy-5 gs-7 club-member">
                                    <thead>
                                        <tr class="fw-semibold fs-6 text-gray-800">
                                        <?php $counter = 1;?>
                                            <th class="min-w-50px" id="number-row">#</th> <!-- Add a column for the list numbers -->
                                            <th class="min-w-250px">Name</th>
                                            <th class="min-w-150px">Email Address</th>
                                            <th class="min-w-300px">Contact Number</th>
                                            <th class="min-w-200px">Gender</th>
                                            <th class="min-w-200px">Address</th>
                                            <th class="min-w-200px">Age</th>
                                            <th class="min-w-100px">Date Registered</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    foreach ($registrants as $registrant) {
                                        ?>
                                        <tr>
                                            <td><?= $counter ?></td>
                                            <td><?php echo $registrant['fullName']; ?></td>
                                            <td><?php echo $registrant['userEmail']; ?></td>
                                            <td><?php echo $registrant['userContactNo']; ?></td>
                                            <td><?php echo $registrant['userGender']; ?></td>
                                            <td><?php echo $registrant['address']; ?></td>
                                            <td><?php echo $registrant['userAge']; ?></td>
                                            <td><?php echo $registrant['dateJoined']; ?></td>
                                        </tr>

                                        <?php
                                        $counter++;
                                    }
                                    ?>
                                    </tbody>
                                </table>
            
                                </div>
                        </div>

                        <!-- Participants -->
                        <div class="tab-pane fade  active pt-3" id="event-participants" role="tabpanel" aria-labelledby="event-participants-tab">
                            <div class=" ps-0 mb-2 d-flex justify-content-between">
                                
                                <div class="d-flex">
                                    
                                    <button type="button" class="btn add-btn justify-content-center align-items-center me-2" onclick="window.location.href = 'event-certificate.php?eventID=<?php echo $event->eventID; ?>';">
                                        
                                    <div class="d-flex align-items-center">
                                            <i class='bx bx-certification button-action-icon me-2'></i>
                                            Certificate
                                    </div>
                                    </button>

                                    <button type="button" class="btn add-btn justify-content-center align-items-center">
                                        <a href="event-gallery.php?eventID=<?php echo $event->eventID; ?>" class="d-flex align-items-center" style="text-decoration: none; color: inherit;">
                                            <i class='bx bx-photo-album button-action-icon me-2'></i>
                                            Gallery
                                        </a>
                                    </button>

                                    <button type="button" class="btn add-btn justify-content-center align-items-center ms-2">
                                    <a href="event-feedbacks.php?eventID=<?php echo $event->eventID; ?>" class="d-flex align-items-center" style="text-decoration: none; color: inherit;">

                                            <i class='bx bx-message-alt-detail button-action-icon me-2'></i>
                                            Feedback
                                    </a>
                                    </button>
                                   
                                </div>
                            </div>

                            <div class="table-responsive mt-2 me-4">
                                
                                <table id="kt_datatable_horizontal_scroll" class="table table-striped table-row-bordered gy-5 gs-7 club-member">
                                    <thead>
                                    
                                        <tr class="fw-semibold fs-6 text-gray-800">
                                            <th class="min-w-250px">Name</th>
                                            <th class="min-w-150px">Email Address</th>
                                            <th class="min-w-300px">Contact Number</th>
                                            <th class="min-w-200px">Gender</th>
                                            <th class="min-w-200px">Address</th>
                                            <th class="min-w-200px">Age</th>
                                            <th class="min-w-100px">Date Particpated</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Loop through each member
                                        foreach ($participants as $participant) {
                                            ?>
                                        <tr>
                                        
                                            <td><?php echo $participant['fullName']; ?></td>
                                            <td><?php echo $participant['userEmail']; ?></td>
                                            <td><?php echo $participant['userContactNo']; ?></td>
                                            <td><?php echo $participant['userGender']; ?></td>
                                            <td><?php echo $participant['address']; ?></td>
                                            <td><?php echo $participant['userAge']; ?></td>
                                            <td><?php echo $participant['dateJoined']; ?></td>
                                        </tr>

                                            <?php
                                        
                                        }
                                        ?>
                                    </tbody>
                                </table>
            
                                </div>
                        </div>
                
                       <!-- Volunteers -->
                       <div class="tab-pane fade show active pt-3" id="event-volunteers" role="tabpanel" aria-labelledby="event-volunteers-tab">
                            <div class="table-responsive mt-2 me-4">
                                
                                <table id="kt_datatable_both_scrolls" class="table table-striped table-row-bordered gy-5 gs-7 club-member">
                                    <thead>
                                        <tr class="fw-semibold fs-6 text-gray-800">
                                        <?php $counter = 1;?>
                                            <th class="min-w-50px" id="number-row">#</th> <!-- Add a column for the list numbers -->
                                            <th class="min-w-250px">Name</th>
                                            <th class="min-w-150px">Email Address</th>
                                            <th class="min-w-300px">Contact Number</th>
                                            <th class="min-w-200px">Gender</th>
                                            <th class="min-w-200px">Address</th>
                                            <th class="min-w-200px">Age</th>
                                            <th class="min-w-100px">Date Volunteered</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        foreach ($volunteers as $volunteer) {
                                            ?>
                                            <tr>
                                                <td><?= $counter ?></td>
                                                <td><?php echo $volunteer['fullName']; ?></td>
                                                <td><?php echo $volunteer['userEmail']; ?></td>
                                                <td><?php echo $volunteer['userContactNo']; ?></td>
                                                <td><?php echo $volunteer['userGender']; ?></td>
                                                <td><?php echo $volunteer['address']; ?></td>
                                                <td><?php echo $volunteer['userAge']; ?></td>
                                                <td><?php echo $volunteer['dateJoined']; ?></td>
                                            </tr>

                                            <?php
                                            $counter++;
                                        }
                                        ?>
                                    </tbody>
                                </table>
            
                            </div>
                        </div>

                    </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    

    <?php require_once('../include/js3.php'); ?>

</body>