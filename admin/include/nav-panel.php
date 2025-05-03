<div class="side-panel col-11 col-md-4 col-lg-3 flex-column align-items-center" id="sidebar">
    <div class="logo pt-4">
        <div class="row d-flex justify-content-center align-items-center">
            <div class="col-3 d-flex ps-5"><img src="../images/zc_lib_seal.png" alt=""></div>
            <div class="col-lg-9 col-md-9 logo-name ps-4">Zamboanga City Library </div>
        </div>
    </div>

    <div class="user d-flex flex-column justify-content-center mt-4">
        <div class="user-pic col-12 d-flex justify-content-center"><img src="../images/user.png" alt=""></div>
        <div class="user-name pt-3 text-center"><strong>Admin</strong></div>
        <div class="position text-center"></div>
    </div>

    <div class="navigation-list pt-5 ">
        <ul class="text-start">
            <li><a class="ms-3 <?= $dashboard ?>" href="../webpages/dashboard.php"><img src="../images/dashboard.png" alt="Dashboard icon" width="25" height="25" style="margin-right:10px;">Dashboard</a></li>
            <li><a class="ms-3 <?= $clubs ?>" href="../webpages/clubs.php"><img src="../images/group.png" alt="Dashboard icon" width="25" height="25" style="margin-right:10px;">Clubs</a></li>
            <li><a class="ms-3 <?= $events ?>" href="../webpages/events.php"><img src="../images/calendar-check.png" alt="Dashboard icon" width="25" height="25" style="margin-right:10px;">Events & Announcements</a></li>
            <li><a class="ms-3 <?= $librarians ?>" href="../webpages/librarians.php"><img src="../images/librarian.png" alt="Dashboard icon" width="25" height="25" style="margin-right:10px;">Librarians</a></li>
            <li><a class="ms-3 <?= $users ?>" href="../webpages/users.php"><img src="../images/userside.png" alt="Dashboard icon" width="25" height="25" style="margin-right:10px;">Users</a></li>
            <li><a class="ms-3 <?= $attendance ?>" href="../webpages/attendance.php"><img src="../images/attendance.png" alt="Dashboard icon" width="25" height="25" style="margin-right:10px;">Attendance</a></li>
            <li><a class="ms-3 <?= $checker ?>" href="../webpages/attendance-checker.php"><img src="../images/scanner.png" alt="Dashboard icon" width="25" height="25" style="margin-right:10px;">Attendance Checker</a></li>
            <li><a class="ms-3 <?= $organization ?>" href="../webpages/organizations.php"><img src="../images/organization-chart.png" alt="Dashboard icon" width="25" height="25" style="margin-right:10px;">Organization</a></li>
        </ul>
    </div>

    <div class="d-flex justify-content-center">
        <div class="logout-btn text-center"><a href="../webpages/logout.php">Log out</a></div>
    </div>
</div>
